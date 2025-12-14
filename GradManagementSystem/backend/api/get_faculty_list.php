<?php
require_once __DIR__ . '/../bootstrap.php';
require_login();

include_once '../db.php';

try {
    $stmt = $pdo->prepare("SELECT user_id, username, email FROM users WHERE role = 'faculty' ORDER BY username ASC");
    $stmt->execute();
    $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $faculty]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

