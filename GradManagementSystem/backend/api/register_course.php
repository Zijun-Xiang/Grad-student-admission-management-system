<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->student_id) && !empty($data->course_code)) {
    try {
        // 检查是否已经注册过
        $check = "SELECT id FROM student_registrations WHERE student_id = :sid AND course_code = :code";
        $stmt_check = $pdo->prepare($check);
        $stmt_check->bindParam(':sid', $data->student_id);
        $stmt_check->bindParam(':code', $data->course_code);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            echo json_encode(["status" => "error", "message" => "Already registered for this course."]);
            exit;
        }

        // 插入注册记录
        $query = "INSERT INTO student_registrations (student_id, course_code) VALUES (:sid, :code)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':sid', $data->student_id);
        $stmt->bindParam(':code', $data->course_code);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Course registered successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Registration failed."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing data."]);
}
?>