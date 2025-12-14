<?php
// backend/api/faculty_get_pending.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once '../db.php';

try {
    // 查找 documents 表中 status 为 'pending' 的所有记录
    $query = "SELECT * FROM documents WHERE status = 'pending' ORDER BY upload_date ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $data]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>