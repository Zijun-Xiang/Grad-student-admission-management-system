<?php
// backend/api/upload_letter.php

// 1. 允许跨域 (CORS) - 增强版
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
// 允许的方法包括 POST 和 OPTIONS (预检)
header("Access-Control-Allow-Methods: POST, OPTIONS");
// 允许的头信息
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 2. 处理 OPTIONS 预检请求 (解决 CORS 报错的关键!)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../db.php';

// 3. 检查是否有文件上传
if (isset($_FILES['file']) && isset($_POST['student_id'])) {
    $file = $_FILES['file'];
    $student_id = $_POST['student_id'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["status" => "error", "message" => "File upload error code: " . $file['error']]);
        exit;
    }

    // 4. 准备保存路径
    $timestamp = time();
    $filename = $timestamp . "_" . basename($file['name']);
    
    // 确保 uploads 文件夹存在
    $target_dir = "../uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . $filename;

    // 5. 移动文件
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        
        try {
            // 6. 写入数据库
            $query = "INSERT INTO documents (student_id, doc_type, file_path, status) VALUES (:sid, 'admission_letter', :fpath, 'pending')";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':sid', $student_id);
            $stmt->bindParam(':fpath', $filename);
            
            if ($stmt->execute()) {
                echo json_encode([
                    "status" => "success", 
                    "message" => "Admission letter uploaded successfully!",
                    "file" => $filename
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Database save failed."]);
            }
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "DB Error: " . $e->getMessage()]);
        }

    } else {
        echo json_encode(["status" => "error", "message" => "Failed to move file. Permission denied?"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "No file received."]);
}
?>