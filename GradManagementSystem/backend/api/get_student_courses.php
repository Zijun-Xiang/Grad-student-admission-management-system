<?php
require_once __DIR__ . '/../bootstrap.php';

include_once '../db.php';

$user = require_login();
$studentId = effective_student_id_for_request($user, isset($_GET['student_id']) ? (string)$_GET['student_id'] : null);

try {
    $query = "SELECT sr.course_code, cc.course_name, cc.credits
              FROM student_registrations sr
              JOIN core_courses cc ON sr.course_code = cc.course_code
              WHERE sr.student_id = :sid";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':sid', $studentId);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
