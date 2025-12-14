<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');

include_once '../db.php';

$user = require_login();
if (($user['role'] ?? '') === 'faculty') {
    send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);
}

if (!isset($_FILES['file'])) {
    send_json(['status' => 'error', 'message' => 'No file received.'], 400);
}

$file = $_FILES['file'];
$studentId = (string)$user['id'];

if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    send_json(['status' => 'error', 'message' => 'File upload error code: ' . (string)$file['error']], 400);
}

$maxBytes = (int)(getenv('UPLOAD_MAX_BYTES') ?: (10 * 1024 * 1024));
if ((int)($file['size'] ?? 0) > $maxBytes) {
    send_json(['status' => 'error', 'message' => 'File too large.'], 400);
}

$originalName = (string)($file['name'] ?? 'upload');
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
if (!in_array($ext, $allowedExt, true)) {
    send_json(['status' => 'error', 'message' => 'Unsupported file type.'], 400);
}

$tmpPath = (string)($file['tmp_name'] ?? '');
if ($tmpPath === '' || !is_file($tmpPath)) {
    send_json(['status' => 'error', 'message' => 'Invalid upload.'], 400);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($tmpPath);
$allowedMime = ['application/pdf', 'image/jpeg', 'image/png'];
if ($mime === false || !in_array($mime, $allowedMime, true)) {
    send_json(['status' => 'error', 'message' => 'Invalid file content type.'], 400);
}

$filename = bin2hex(random_bytes(16)) . '.' . $ext;
$targetDir = __DIR__ . '/../uploads';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}
$targetFile = $targetDir . '/' . $filename;

if (!move_uploaded_file($tmpPath, $targetFile)) {
    send_json(['status' => 'error', 'message' => 'Failed to move file. Permission denied?'], 500);
}

try {
    $stmt = $pdo->prepare("INSERT INTO documents (student_id, doc_type, file_path, status)
                           VALUES (:sid, 'major_professor_form', :fpath, 'pending')");
    $stmt->bindParam(':sid', $studentId);
    $stmt->bindParam(':fpath', $filename);

    if (!$stmt->execute()) {
        send_json(['status' => 'error', 'message' => 'Database save failed.'], 500);
    }

    send_json(['status' => 'success', 'message' => 'Major Professor Form uploaded successfully!', 'file' => $filename]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()], 500);
}

