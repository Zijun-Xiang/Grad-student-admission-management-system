<?php
// backend/api/login.php

// 1. 设置头部，允许 Vue (localhost:5173) 访问此接口 (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 2. 引入数据库连接
include_once '../db.php';

// 3. 获取前端发来的 JSON 数据
$data = json_decode(file_get_contents("php://input"));

// 检查是否接收到了 username 和 password
if (!empty($data->username) && !empty($data->password)) {

    // 4. 准备 SQL 查询语句 (使用预处理语句防止 SQL 注入)
    // 我们要查用户是否存在，以及他的角色
    $query = "SELECT user_id, username, password, role FROM users WHERE username = :username LIMIT 1";
    
    // 准备并执行
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':username', $data->username);
    $stmt->execute();
    
    // 获取结果
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 5. 验证密码
        // 注意：实际项目中密码应该是加密的 (password_verify)，但我们的种子数据是明文 '123456'，
        // 所以这里先用简单的字符串比较。
        if ($data->password == $row['password']) {
            
            // 登录成功！返回用户信息
            echo json_encode(array(
                "status" => "success",
                "message" => "Login successful.",
                "user" => array(
                    "id" => $row['user_id'],
                    "username" => $row['username'],
                    "role" => $row['role']
                )
            ));
        } else {
            // 密码错误
            echo json_encode(array("status" => "error", "message" => "Invalid password."));
        }
    } else {
        // 用户名不存在
        echo json_encode(array("status" => "error", "message" => "User not found."));
    }
} else {
    // 数据不完整
    echo json_encode(array("status" => "error", "message" => "Incomplete data. Please provide username and password."));
}
?>