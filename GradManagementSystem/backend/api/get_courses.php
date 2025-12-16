<?php
require_once __DIR__ . '/../bootstrap.php';
require_login();

include_once '../db.php';
require_once __DIR__ . '/majors_common.php';

try {
    $user = current_user() ?: [];
    $uid = (string)($user['id'] ?? '');
    $majorCode = $uid !== '' ? get_user_major_code($pdo, $uid) : majors_default_code();

    $hasMajor = majors_has_column($pdo, 'core_courses', 'major_code');
    if ($hasMajor) {
        $stmt = $pdo->prepare('SELECT * FROM core_courses WHERE major_code = :m ORDER BY course_code ASC');
        $stmt->bindParam(':m', $majorCode);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM core_courses ORDER BY course_code ASC');
    }
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    send_json(['status' => 'success', 'data' => $courses]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
