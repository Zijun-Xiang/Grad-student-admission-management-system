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

try {
    $del = $pdo->prepare('DELETE FROM student_registrations WHERE student_id = :sid AND course_code = :code');
    $del->bindParam(':sid', $studentId);
    $del->bindParam(':code', $courseCode);
    $del->execute();

    if ($del->rowCount() === 0) {
        send_json(['status' => 'error', 'message' => 'Not registered for this course.'], 404);
    }

    // If the student drops Research Method, re-activate the related hold (best-effort).
    if (strcasecmp($courseCode, $researchMethodCourseCode) === 0) {
        try {
            $stmtUpd = $pdo->prepare(
                "UPDATE holds
                 SET is_active = TRUE, resolved_at = NULL
                 WHERE student_id = :sid AND hold_type = 'research_method'
                 LIMIT 1"
            );
            $stmtUpd->bindParam(':sid', $studentId);
            $stmtUpd->execute();

            if ($stmtUpd->rowCount() === 0) {
                $hasTermCodeCol = false;
                try {
                    $holdCols = $pdo->query('SHOW COLUMNS FROM holds')->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($holdCols as $c) {
                        if ((string)($c['Field'] ?? '') === 'term_code') {
                            $hasTermCodeCol = true;
                            break;
                        }
                    }
                } catch (Exception $e) {
                    $hasTermCodeCol = false;
                }

                if ($hasTermCodeCol) {
                    $stmtIns = $pdo->prepare(
                        "INSERT INTO holds (student_id, hold_type, is_active, term_code)
                         VALUES (:sid, 'research_method', TRUE, NULL)"
                    );
                } else {
                    $stmtIns = $pdo->prepare(
                        "INSERT INTO holds (student_id, hold_type, is_active)
                         VALUES (:sid, 'research_method', TRUE)"
                    );
                }
                $stmtIns->bindParam(':sid', $studentId);
                $stmtIns->execute();
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    send_json(['status' => 'success', 'message' => 'Course unregistered successfully.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

