<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);
require_method('POST');

include_once '../db.php';

$facultyId = (string)(current_user()['id'] ?? '');
$data = get_json_input();
$courseCode = strtoupper(trim((string)($data['course_code'] ?? '')));

if ($courseCode === '') {
    send_json(['status' => 'error', 'message' => 'Missing course_code.'], 400);
}

try {
    $stmt = $pdo->prepare("DELETE FROM faculty_courses WHERE faculty_id = :fid AND course_code = :code");
    $stmt->bindParam(':fid', $facultyId);
    $stmt->bindParam(':code', $courseCode);
    $stmt->execute();
    send_json(['status' => 'success', 'message' => 'Course removed.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

