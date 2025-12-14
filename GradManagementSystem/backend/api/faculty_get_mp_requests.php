<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once '../db.php';

// 获取当前登录的教授 ID
$prof_id = isset($_GET['prof_id']) ? $_GET['prof_id'] : die();

try {
    // 查询申请了该教授 (major_professor_id = prof_id) 且状态为 pending 的学生
    $query = "SELECT sd.student_id, sd.first_name, sd.last_name, u.email 
              FROM student_details sd
              JOIN users u ON sd.student_id = u.user_id
              WHERE sd.major_professor_id = :pid AND sd.mp_status = 'pending'";
              
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':pid', $prof_id);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $data]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>