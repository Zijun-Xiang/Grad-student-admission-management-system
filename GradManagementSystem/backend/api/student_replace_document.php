<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['student']);
require_method('POST');

include_once '../db.php';

function has_column(PDO $pdo, string $table, string $column): bool
{
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            if ((string)($c['Field'] ?? '') === $column) return true;
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

function allowed_for_doc_type(string $docType): array
{
    $t = strtolower(trim($docType));
    if ($t === 'thesis_project') {
        return [
            'ext' => ['pdf'],
            'mime' => ['application/pdf'],
        ];
    }
    // default (admission / mp form)
    return [
        'ext' => ['pdf', 'jpg', 'jpeg', 'png'],
        'mime' => ['application/pdf', 'image/jpeg', 'image/png'],
    ];
}

$docId = isset($_POST['doc_id']) ? trim((string)$_POST['doc_id']) : '';
if ($docId === '') {
    send_json(['status' => 'error', 'message' => 'Missing doc_id.'], 400);
}
if (!isset($_FILES['file'])) {
    send_json(['status' => 'error', 'message' => 'No file received.'], 400);
}

$studentId = (string)(current_user()['id'] ?? '');

try {
    $stmt = $pdo->prepare("SELECT doc_id, student_id, doc_type, file_path, status FROM documents WHERE doc_id = :did LIMIT 1");
    $stmt->bindParam(':did', $docId);
    $stmt->execute();
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc) {
        send_json(['status' => 'error', 'message' => 'Document not found.'], 404);
    }
    if ((string)($doc['student_id'] ?? '') !== $studentId) {
        send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);
    }

    $status = strtolower(trim((string)($doc['status'] ?? '')));
    if ($status === 'approved') {
        send_json(['status' => 'error', 'message' => 'Approved documents cannot be replaced.'], 400);
    }

    $docType = (string)($doc['doc_type'] ?? '');
    $rules = allowed_for_doc_type($docType);

    $file = $_FILES['file'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        send_json(['status' => 'error', 'message' => 'File upload error code: ' . (string)($file['error'] ?? '')], 400);
    }
    $maxBytes = (int)(getenv('UPLOAD_MAX_BYTES') ?: (10 * 1024 * 1024));
    if ((int)($file['size'] ?? 0) > $maxBytes) {
        send_json(['status' => 'error', 'message' => 'File too large.'], 400);
    }

    $originalName = (string)($file['name'] ?? 'upload');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, $rules['ext'], true)) {
        send_json(['status' => 'error', 'message' => 'Unsupported file type.'], 400);
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_file($tmpPath)) {
        send_json(['status' => 'error', 'message' => 'Invalid upload.'], 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath);
    if ($mime === false || !in_array($mime, $rules['mime'], true)) {
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

    $oldPath = (string)($doc['file_path'] ?? '');

    $setParts = [
        "file_path = :fp",
        "status = 'pending'",
        "admin_comment = NULL",
    ];
    if (has_column($pdo, 'documents', 'upload_date')) {
        $setParts[] = "upload_date = NOW()";
    } elseif (has_column($pdo, 'documents', 'created_at')) {
        $setParts[] = "created_at = NOW()";
    }

    $sql = "UPDATE documents SET " . implode(', ', $setParts) . " WHERE doc_id = :did AND student_id = :sid";
    $up = $pdo->prepare($sql);
    $up->bindParam(':fp', $filename);
    $up->bindParam(':did', $docId);
    $up->bindParam(':sid', $studentId);
    $up->execute();

    // Best-effort cleanup old file.
    $basename = basename($oldPath);
    if ($basename !== '' && $basename !== $filename) {
        $uploadsRoot = realpath(__DIR__ . '/../uploads');
        $fullPath = $uploadsRoot ? realpath($uploadsRoot . DIRECTORY_SEPARATOR . $basename) : false;
        if ($uploadsRoot && $fullPath && strncmp($fullPath, $uploadsRoot, strlen($uploadsRoot)) === 0 && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    send_json(['status' => 'success', 'message' => 'Document replaced. Waiting for review.', 'file' => $filename]);
} catch (Exception $e) {
    if (isset($targetFile) && is_string($targetFile) && $targetFile !== '' && is_file($targetFile)) {
        @unlink($targetFile);
    }
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

