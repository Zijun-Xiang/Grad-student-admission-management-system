<?php
define('BOOTSTRAP_NO_JSON', true);
require_once __DIR__ . '/../bootstrap.php';

$user = require_login();
include_once '../db.php';

$docId = isset($_GET['doc_id']) ? (string)$_GET['doc_id'] : '';
if ($docId === '') {
    http_response_code(400);
    echo 'Missing doc_id.';
    exit();
}

$stmt = $pdo->prepare('SELECT doc_id, student_id, file_path FROM documents WHERE doc_id = :did LIMIT 1');
$stmt->bindParam(':did', $docId);
$stmt->execute();
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) {
    http_response_code(404);
    echo 'Not found.';
    exit();
}

$role = normalize_role(isset($user['role']) ? (string)$user['role'] : '');
$userId = (string)($user['id'] ?? '');

// Extra safety: if session role is stale/missing, verify role from DB.
if ($role !== 'faculty' && $role !== 'admin' && $userId !== '') {
    try {
        $stmtRole = $pdo->prepare('SELECT role FROM users WHERE user_id = :uid LIMIT 1');
        $stmtRole->bindParam(':uid', $userId);
        $stmtRole->execute();
        $dbRole = $stmtRole->fetchColumn();
        if ($dbRole !== false && $dbRole !== null) {
            $role = normalize_role((string)$dbRole);
            $_SESSION['user']['role'] = $role;
        }
    } catch (Exception $e) {
        // ignore
    }
}

if ($role !== 'faculty' && $role !== 'admin' && (string)$doc['student_id'] !== $userId) {
    http_response_code(403);
    echo 'Forbidden.';
    exit();
}

$basename = basename((string)$doc['file_path']);
$uploadsRoot = realpath(__DIR__ . '/../uploads');
$fullPath = $uploadsRoot ? realpath($uploadsRoot . DIRECTORY_SEPARATOR . $basename) : false;

if ($uploadsRoot === false || $fullPath === false) {
    http_response_code(404);
    echo 'Not found.';
    exit();
}

if (strncmp($fullPath, $uploadsRoot, strlen($uploadsRoot)) !== 0 || !is_file($fullPath)) {
    http_response_code(404);
    echo 'Not found.';
    exit();
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($fullPath) ?: 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . $basename . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit();
