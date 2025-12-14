<?php
require_once __DIR__ . '/../bootstrap.php';
require_login();

include_once '../db.php';

try {
    $stmt = $pdo->prepare('SELECT * FROM core_courses ORDER BY course_code ASC');
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    send_json(['status' => 'success', 'data' => $courses]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

