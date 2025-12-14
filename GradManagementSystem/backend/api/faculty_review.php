<?php
// backend/api/faculty_review.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->doc_id) && !empty($data->student_id) && !empty($data->action)) {
    
    $doc_id = $data->doc_id;
    $student_id = $data->student_id;
    $action = $data->action; // 'approve' 或 'reject'
    $comment = isset($data->comment) ? $data->comment : null; // 批注

    try {
        if ($action === 'approve') {
            // === 动作 A: 批准 ===
            // 1. 更新文档状态为 approved
            $query_doc = "UPDATE documents SET status = 'approved', admin_comment = :comment WHERE doc_id = :did";
            $stmt = $pdo->prepare($query_doc);
            $stmt->bindParam(':comment', $comment); // 批准也可以写“Good job”之类的
            $stmt->bindParam(':did', $doc_id);
            $stmt->execute();

            // 2. 解除该学生的 Admission Hold
            // 假设 Hold 类型是 'admission_letter'
            $query_hold = "UPDATE holds SET is_active = FALSE, resolved_at = NOW() 
                           WHERE student_id = :sid AND hold_type = 'admission_letter'";
            $stmt_hold = $pdo->prepare($query_hold);
            $stmt_hold->bindParam(':sid', $student_id);
            $stmt_hold->execute();

            echo json_encode(["status" => "success", "message" => "Document approved and Hold lifted."]);

        } elseif ($action === 'reject') {
            // === 动作 B: 拒绝 ===
            // 1. 更新文档状态为 rejected，并记下批注
            $query_doc = "UPDATE documents SET status = 'rejected', admin_comment = :comment WHERE doc_id = :did";
            $stmt = $pdo->prepare($query_doc);
            $stmt->bindParam(':comment', $comment);
            $stmt->bindParam(':did', $doc_id);
            $stmt->execute();
            
            // 注意：拒绝时不解除 Hold，Hold 依然是 TRUE
            echo json_encode(["status" => "success", "message" => "Document rejected."]);
        }

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "Missing data."]);
}
?>