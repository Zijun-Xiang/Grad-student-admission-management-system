<?php
// backend/api/get_status.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include_once '../db.php';

$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : die();

try {
    // 1. 查询 Active Holds
    $query_holds = "SELECT * FROM holds WHERE student_id = :sid AND is_active = TRUE";
    $stmt_holds = $pdo->prepare($query_holds);
    $stmt_holds->bindParam(':sid', $student_id);
    $stmt_holds->execute();
    $holds = $stmt_holds->fetchAll(PDO::FETCH_ASSOC);

    // 2. 查询 Documents
    $query_docs = "SELECT * FROM documents WHERE student_id = :sid ORDER BY upload_date DESC";
    $stmt_docs = $pdo->prepare($query_docs);
    $stmt_docs->bindParam(':sid', $student_id);
    $stmt_docs->execute();
    $documents = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);

    // 3. 查询 Deficiencies
    $query_def = "SELECT sd.course_code, cc.course_name, sd.status 
                  FROM student_deficiencies sd
                  JOIN core_courses cc ON sd.course_code = cc.course_code
                  WHERE sd.student_id = :sid";
    $stmt_def = $pdo->prepare($query_def);
    $stmt_def->bindParam(':sid', $student_id);
    $stmt_def->execute();
    $deficiencies = $stmt_def->fetchAll(PDO::FETCH_ASSOC);

    // 4. (新增) 查询导师状态和导师名字
    // 我们需要连表查询 users 表来获取导师的名字
    $query_mp = "SELECT sd.mp_status, u.username as prof_name 
                 FROM student_details sd
                 LEFT JOIN users u ON sd.major_professor_id = u.user_id
                 WHERE sd.student_id = :sid";
    $stmt_mp = $pdo->prepare($query_mp);
    $stmt_mp->bindParam(':sid', $student_id);
    $stmt_mp->execute();
    $mp_info = $stmt_mp->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "holds" => $holds,
        "documents" => $documents,
        "deficiencies" => $deficiencies,
        "mp_info" => $mp_info // 把导师信息发给前端
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>