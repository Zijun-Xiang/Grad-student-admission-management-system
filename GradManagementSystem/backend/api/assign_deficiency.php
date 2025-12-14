<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->student_id) && is_array($data->courses)) {
    $student_id = $data->student_id;
    $courses = $data->courses; // 这是一个数组，例如 ['CS360', 'CS401']

    try {
        $pdo->beginTransaction();

        // 1. 先清空该学生之前的分配 (为了简单，采用“先删后加”策略，或者你可以只加新的)
        // 这里我们假设老师每次提交都是“最终决定”，所以先清除旧的assigned记录
        $delete_sql = "DELETE FROM student_deficiencies WHERE student_id = :sid AND status = 'assigned'";
        $stmt_del = $pdo->prepare($delete_sql);
        $stmt_del->bindParam(':sid', $student_id);
        $stmt_del->execute();

        // 2. 插入新选择的课程
        $insert_sql = "INSERT INTO student_deficiencies (student_id, course_code, status) VALUES (:sid, :code, 'assigned')";
        $stmt_ins = $pdo->prepare($insert_sql);

        foreach ($courses as $course_code) {
            $stmt_ins->bindParam(':sid', $student_id);
            $stmt_ins->bindParam(':code', $course_code);
            $stmt_ins->execute();
        }

        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "Deficiencies assigned successfully."]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid data."]);
}
?>