<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');

include_once '../db.php';

$data = get_json_input();
$username = (string)($data['username'] ?? '');
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    send_json(['status' => 'error', 'message' => 'Incomplete data. Please provide username and password.'], 400);
}

$stmt = $pdo->prepare('SELECT user_id, username, password, role FROM users WHERE username = :username LIMIT 1');
$stmt->bindParam(':username', $username);
$stmt->execute();

if ($stmt->rowCount() <= 0) {
    send_json(['status' => 'error', 'message' => 'Invalid username or password.'], 401);
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);
$stored = (string)($row['password'] ?? '');
$storedInfo = password_get_info($stored);

$ok = false;
if (($storedInfo['algo'] ?? 0) !== 0) {
    $ok = password_verify($password, $stored);
} else {
    $ok = hash_equals($stored, $password);
    if ($ok) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $update = $pdo->prepare('UPDATE users SET password = :hash WHERE user_id = :uid');
        $update->bindParam(':hash', $newHash);
        $update->bindParam(':uid', $row['user_id']);
        $update->execute();
    }
}

if (!$ok) {
    send_json(['status' => 'error', 'message' => 'Invalid username or password.'], 401);
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'id' => (string)$row['user_id'],
    'username' => (string)$row['username'],
    'role' => normalize_role((string)$row['role']),
];

send_json([
    'status' => 'success',
    'message' => 'Login successful.',
    'user' => $_SESSION['user'],
]);
