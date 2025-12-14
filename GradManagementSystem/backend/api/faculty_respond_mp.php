<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->student_id) && !empty($data->action)) {
    $student_id = $data->student_id;
    $action = $data->action; // 'accept' 或 'reject'

    try {
        if ($action === 'accept') {
            // === 接受 ===
            // 1. 更新学生状态为 approved
            $sql = "UPDATE student_details SET mp_status = 'approved' WHERE student_id = :sid";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':sid', $student_id);
            $stmt->execute();

            // 2. 解除 Major Professor Hold
            $sql_hold = "UPDATE holds SET is_active = FALSE, resolved_at = NOW() 
                         WHERE student_id = :sid AND hold_type = 'major_professor'";
            $stmt_hold = $pdo->prepare($sql_hold);
            $stmt_hold->bindParam(':sid', $student_id);
            $stmt_hold->execute();

            echo json_encode(["status" => "success", "message" => "Student accepted. Hold lifted."]);

        } elseif ($action === 'reject') {
            // === 拒绝 ===
            // 重置学生状态，让他能重新选别人
            $sql = "UPDATE student_details SET mp_status = 'none', major_professor_id = NULL WHERE student_id = :sid";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':sid', $student_id);
            $stmt->execute();

            echo json_encode(["status" => "success", "message" => "Request rejected."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing data."]);
}
?>