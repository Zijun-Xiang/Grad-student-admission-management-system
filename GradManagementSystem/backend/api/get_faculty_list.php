<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once '../db.php';

try {
    // 获取所有 role 为 'faculty' 的用户
    $query = "SELECT user_id, username, email FROM users WHERE role = 'faculty' ORDER BY username ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $faculty]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>