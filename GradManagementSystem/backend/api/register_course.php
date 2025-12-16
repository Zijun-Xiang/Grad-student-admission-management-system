<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/majors_common.php';

$user = require_login();
if (($user['role'] ?? '') === 'faculty') {
    send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);
}

$data = get_json_input();
$courseCode = (string)($data['course_code'] ?? '');
if ($courseCode === '') {
    send_json(['status' => 'error', 'message' => 'Missing data.'], 400);
}

$studentId = effective_student_id_for_request($user, isset($data['student_id']) ? (string)$data['student_id'] : null);
$researchMethodCourseCode = getenv('RESEARCH_METHOD_COURSE_CODE') ?: 'CS690';
$minCredits = (int)(getenv('TERM_MIN_CREDITS') ?: 12);
$maxCredits = (int)(getenv('TERM_MAX_CREDITS') ?: 20);

try {
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

    // Holds gating:
    // - In general, any active hold blocks registration.
    // - Exception: if the ONLY active hold(s) are "research_method" and the student is registering for the Research Method course,
    //   allow registering that course so faculty can verify and lift the hold.
    $stmtHolds = $pdo->prepare('SELECT hold_type FROM holds WHERE student_id = :sid AND is_active = TRUE');
    $stmtHolds->bindParam(':sid', $studentId);
    $stmtHolds->execute();
    $activeHoldTypes = $stmtHolds->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($activeHoldTypes)) {
        $isResearchMethodCourse = strcasecmp($courseCode, $researchMethodCourseCode) === 0;
        $onlyResearchMethodHold = count(array_unique(array_map('strval', $activeHoldTypes))) === 1 && (string)$activeHoldTypes[0] === 'research_method';

        if (!($isResearchMethodCourse && $onlyResearchMethodHold)) {
            send_json(['status' => 'error', 'message' => 'You have active holds. Registration is disabled.'], 403);
        }
    }

    // Enforce max credits per term (best-effort; assumes current registrations are for current term).
    try {
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
    } catch (Exception $e) {
        // Ignore if schema doesn't match; UI still provides guidance.
    }

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

    $check = $pdo->prepare('SELECT id FROM student_registrations WHERE student_id = :sid AND course_code = :code');
    $check->bindParam(':sid', $studentId);
    $check->bindParam(':code', $courseCode);
    $check->execute();
    if ($check->rowCount() > 0) {
        send_json(['status' => 'error', 'message' => 'Already registered for this course.'], 409);
    }

    $insert = $pdo->prepare('INSERT INTO student_registrations (student_id, course_code) VALUES (:sid, :code)');
    $insert->bindParam(':sid', $studentId);
    $insert->bindParam(':code', $courseCode);
    $ok = $insert->execute();

    if (!$ok) {
        send_json(['status' => 'error', 'message' => 'Registration failed.'], 500);
    }

    send_json(['status' => 'success', 'message' => 'Course registered successfully.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
