<?php
require_once __DIR__ . '/../bootstrap.php';

$user = require_login(['faculty']);
include_once '../db.php';

$profId = (string)$user['id'];

try {
    $query = "SELECT sd.student_id, sd.first_name, sd.last_name, u.username AS student_username, u.email
              FROM student_details sd
              JOIN users u ON sd.student_id = u.user_id
              WHERE sd.major_professor_id = :pid AND sd.mp_status = 'pending'";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':pid', $profId);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
