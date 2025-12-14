<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->student_id) && !empty($data->professor_id)) {
    try {
        // 更新学生表：填入教授ID，并将状态设为 'pending' (待批准)
        $query = "UPDATE student_details 
                  SET major_professor_id = :pid, mp_status = 'pending' 
                  WHERE student_id = :sid";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':pid', $data->professor_id);
        $stmt->bindParam(':sid', $data->student_id);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Request sent to professor."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Update failed."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing data."]);
}
?>