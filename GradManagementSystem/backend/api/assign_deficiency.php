<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);
require_method('POST');

include_once '../db.php';

$data = get_json_input();
$studentId = (string)($data['student_id'] ?? '');
$courses = $data['courses'] ?? null;

if ($studentId === '' || !is_array($courses)) {
    send_json(['status' => 'error', 'message' => 'Invalid data.'], 400);
}

try {
    $pdo->beginTransaction();

    $stmtDel = $pdo->prepare("DELETE FROM student_deficiencies WHERE student_id = :sid AND status = 'assigned'");
    $stmtDel->bindParam(':sid', $studentId);
    $stmtDel->execute();

    $stmtIns = $pdo->prepare("INSERT INTO student_deficiencies (student_id, course_code, status)
                              VALUES (:sid, :code, 'assigned')");

    foreach ($courses as $courseCode) {
        $courseCode = (string)$courseCode;
        if ($courseCode === '') {
            continue;
        }
        $stmtIns->bindParam(':sid', $studentId);
        $stmtIns->bindParam(':code', $courseCode);
        $stmtIns->execute();
    }

    $pdo->commit();
    send_json(['status' => 'success', 'message' => 'Deficiencies assigned successfully.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

