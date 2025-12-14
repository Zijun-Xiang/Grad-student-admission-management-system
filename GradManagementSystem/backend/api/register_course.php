<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');

include_once '../db.php';

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
    $stmtHolds = $pdo->prepare('SELECT COUNT(*) FROM holds WHERE student_id = :sid AND is_active = TRUE');
    $stmtHolds->bindParam(':sid', $studentId);
    $stmtHolds->execute();
    if ((int)$stmtHolds->fetchColumn() > 0) {
        send_json(['status' => 'error', 'message' => 'You have active holds. Registration is disabled.'], 403);
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

    // If this is the Research Method course, automatically lift the related hold.
    if (strcasecmp($courseCode, $researchMethodCourseCode) === 0) {
        try {
            $stmtHold = $pdo->prepare("UPDATE holds SET is_active = FALSE, resolved_at = NOW()
                                       WHERE student_id = :sid AND hold_type = 'research_method' AND is_active = TRUE");
            $stmtHold->bindParam(':sid', $studentId);
            $stmtHold->execute();

            if ($stmtHold->rowCount() > 0) {
                $releaseCode = generate_registrar_code();
                $createdBy = (string)(current_user()['id'] ?? $studentId);
                $payload = json_encode(['course_code' => $courseCode, 'action' => 'registered']);
                $stmtSig = $pdo->prepare(
                    "INSERT INTO registrar_signals (student_id, hold_type, term_code, code, created_by, payload)
                     VALUES (:sid, 'research_method', NULL, :code, :by, :payload)"
                );
                $stmtSig->bindParam(':sid', $studentId);
                $stmtSig->bindParam(':code', $releaseCode);
                $stmtSig->bindParam(':by', $createdBy);
                $stmtSig->bindParam(':payload', $payload);
                $stmtSig->execute();
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    send_json(['status' => 'success', 'message' => 'Course registered successfully.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
