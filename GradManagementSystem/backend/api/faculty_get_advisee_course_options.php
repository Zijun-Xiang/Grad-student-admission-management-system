<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';
require_once __DIR__ . '/majors_common.php';
require_once __DIR__ . '/course_actions_common.php';

$facultyId = (string)(current_user()['id'] ?? '');
$studentId = (string)($_GET['student_id'] ?? '');
if ($studentId === '') {
    send_json(['status' => 'error', 'message' => 'Missing student_id.'], 400);
}

try {
    if (!faculty_owns_advisee($pdo, $facultyId, $studentId)) {
        send_json(['status' => 'error', 'message' => 'Forbidden (not your advisee).'], 403);
    }

    ensure_majors_schema($pdo);
    $majorCode = get_user_major_code($pdo, $studentId);
    $hasMajor = majors_has_column($pdo, 'core_courses', 'major_code');

    if ($hasMajor) {
        $stmt = $pdo->prepare(
            "SELECT course_code, course_name, credits, level, is_required
             FROM core_courses
             WHERE major_code = :m
             ORDER BY course_code ASC"
        );
        $stmt->bindParam(':m', $majorCode);
    } else {
        $stmt = $pdo->prepare(
            "SELECT course_code, course_name, credits, level, is_required
             FROM core_courses
             ORDER BY course_code ASC"
        );
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    send_json(['status' => 'success', 'major_code' => $majorCode, 'data' => $rows]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

