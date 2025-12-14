<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once '../db.php';

$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : die();

try {
    // 获取学生已注册的课程
    $query = "SELECT sr.course_code, cc.course_name 
              FROM student_registrations sr
              JOIN core_courses cc ON sr.course_code = cc.course_code
              WHERE sr.student_id = :sid";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':sid', $student_id);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $data]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>