<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['student']);
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/course_actions_common.php';
require_once __DIR__ . '/majors_common.php';

$studentId = (string)(current_user()['id'] ?? '');
$data = get_json_input();
$actionId = trim((string)($data['action_id'] ?? ''));
if ($actionId === '' || !ctype_digit($actionId) || (int)$actionId <= 0) {
    send_json(['status' => 'error', 'message' => 'Invalid action_id.'], 400);
}

$researchMethodCourseCode = getenv('RESEARCH_METHOD_COURSE_CODE') ?: 'CS690';
$minCredits = (int)(getenv('TERM_MIN_CREDITS') ?: 12);
$maxCredits = (int)(getenv('TERM_MAX_CREDITS') ?: 20);

function table_exists(PDO $pdo, string $table): bool
{
    try {
        return (bool)$pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

try {
    ensure_advisee_course_actions_table($pdo);

    if (!table_exists($pdo, 'student_registrations')) {
        send_json(['status' => 'error', 'message' => 'student_registrations table not found.'], 500);
    }

    $stmt = $pdo->prepare(
        "SELECT id, faculty_id, action_type, course_code, comment
         FROM advisee_course_actions
         WHERE id = :id AND student_id = :sid AND status = 'pending'
         LIMIT 1"
    );
    $stmt->bindValue(':id', (int)$actionId, PDO::PARAM_INT);
    $stmt->bindParam(':sid', $studentId);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        send_json(['status' => 'error', 'message' => 'Request not found.'], 404);
    }

    $actionType = (string)($row['action_type'] ?? '');
    $courseCode = strtoupper(trim((string)($row['course_code'] ?? '')));
    if (!in_array($actionType, ['add', 'drop'], true) || $courseCode === '') {
        send_json(['status' => 'error', 'message' => 'Invalid request.'], 400);
    }

    // Enforce major-based course visibility (best-effort).
    try {
        ensure_majors_schema($pdo);
        $studentMajor = get_user_major_code($pdo, $studentId);
        $hasMajor = majors_has_column($pdo, 'core_courses', 'major_code');
        if ($hasMajor) {
            $stmtM = $pdo->prepare("SELECT 1 FROM core_courses WHERE course_code = :code AND major_code = :m LIMIT 1");
            $stmtM->bindParam(':code', $courseCode);
            $stmtM->bindParam(':m', $studentMajor);
            $stmtM->execute();
            if (!$stmtM->fetchColumn()) {
                send_json(['status' => 'error', 'message' => 'Course is not available for your major.'], 403);
            }
        }
    } catch (Exception $e) {
        // ignore
    }

    if ($actionType === 'add') {
        // Holds gating (same policy as register_course.php)
        $stmtHolds = $pdo->prepare('SELECT hold_type FROM holds WHERE student_id = :sid AND is_active = TRUE');
        $stmtHolds->bindParam(':sid', $studentId);
        $stmtHolds->execute();
        $activeHoldTypes = $stmtHolds->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($activeHoldTypes)) {
            $isResearchMethodCourse = strcasecmp($courseCode, $researchMethodCourseCode) === 0;
            $onlyResearchMethodHold =
                count(array_unique(array_map('strval', $activeHoldTypes))) === 1 && (string)$activeHoldTypes[0] === 'research_method';
            if (!($isResearchMethodCourse && $onlyResearchMethodHold)) {
                send_json(['status' => 'error', 'message' => 'You have active holds. Registration is disabled.'], 403);
            }
        }

        // Check duplicate registration.
        $check = $pdo->prepare('SELECT id FROM student_registrations WHERE student_id = :sid AND course_code = :code LIMIT 1');
        $check->bindParam(':sid', $studentId);
        $check->bindParam(':code', $courseCode);
        $check->execute();
        $already = (bool)$check->fetchColumn();

        if (!$already) {
            // Enforce max credits per term (best-effort; assumes current registrations are for current term).
            $stmtNew = $pdo->prepare('SELECT credits FROM core_courses WHERE course_code = :code LIMIT 1');
            $stmtNew->bindParam(':code', $courseCode);
            $stmtNew->execute();
            $newCredits = $stmtNew->fetchColumn();
            if ($newCredits === false || $newCredits === null) {
                send_json(['status' => 'error', 'message' => 'Course not found.'], 404);
            }
            $newCredits = (int)$newCredits;

            $stmtSum = $pdo->prepare(
                "SELECT COALESCE(SUM(cc.credits), 0)
                 FROM student_registrations sr
                 JOIN core_courses cc ON cc.course_code = sr.course_code
                 WHERE sr.student_id = :sid"
            );
            $stmtSum->bindParam(':sid', $studentId);
            $stmtSum->execute();
            $currentCredits = (int)$stmtSum->fetchColumn();

            if (($currentCredits + $newCredits) > $maxCredits) {
                send_json([
                    'status' => 'error',
                    'message' => "Credit limit exceeded. Max is $maxCredits credits per term.",
                    'max_credits' => $maxCredits,
                    'current_credits' => $currentCredits,
                    'attempt_add' => $newCredits,
                    'min_credits' => $minCredits,
                ], 403);
            }

            // Deficiency gating (best-effort).
            if (table_exists($pdo, 'student_deficiencies')) {
                $sqlUnregDef = "SELECT sd.course_code
                                FROM student_deficiencies sd
                                LEFT JOIN student_registrations sr
                                  ON sr.student_id = sd.student_id AND sr.course_code = sd.course_code
                                WHERE sd.student_id = :sid AND sd.status = 'assigned' AND sr.id IS NULL";
                $stmtDef = $pdo->prepare($sqlUnregDef);
                $stmtDef->bindParam(':sid', $studentId);
                $stmtDef->execute();
                $unregisteredDef = $stmtDef->fetchAll(PDO::FETCH_COLUMN);
                if (!empty($unregisteredDef) && !in_array($courseCode, $unregisteredDef, true)) {
                    send_json(['status' => 'error', 'message' => 'You must register for deficiency courses first.'], 403);
                }
            }

            $insert = $pdo->prepare('INSERT INTO student_registrations (student_id, course_code) VALUES (:sid, :code)');
            $insert->bindParam(':sid', $studentId);
            $insert->bindParam(':code', $courseCode);
            $ok = $insert->execute();
            if (!$ok) {
                send_json(['status' => 'error', 'message' => 'Registration failed.'], 500);
            }
        }
    } else {
        // drop
        $del = $pdo->prepare('DELETE FROM student_registrations WHERE student_id = :sid AND course_code = :code');
        $del->bindParam(':sid', $studentId);
        $del->bindParam(':code', $courseCode);
        $del->execute();
    }

    $stmtUp = $pdo->prepare("UPDATE advisee_course_actions SET status = 'applied', applied_at = NOW() WHERE id = :id AND student_id = :sid");
    $stmtUp->bindValue(':id', (int)$actionId, PDO::PARAM_INT);
    $stmtUp->bindParam(':sid', $studentId);
    $stmtUp->execute();

    $msg = $actionType === 'add' ? 'Course added.' : 'Course dropped.';
    send_json(['status' => 'success', 'message' => $msg, 'course_code' => $courseCode, 'action_type' => $actionType]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
