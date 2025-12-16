<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');

include_once '../db.php';

$user = require_login(['student']);

// Term gating: Thesis/Project upload is available only in Term 3 and Term 4.
try {
    $studentIdForGate = (string)($user['id'] ?? '');
    $currentTerm = grad_current_term_code();
    $entryTerm = '';
    $entryDate = '';

    try {
        $stmtP = $pdo->prepare("SELECT entry_date, entry_term_code FROM user_profiles WHERE user_id = :uid LIMIT 1");
        $stmtP->bindParam(':uid', $studentIdForGate);
        $stmtP->execute();
        $row = $stmtP->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $entryDate = (string)($row['entry_date'] ?? '');
            $entryTerm = (string)($row['entry_term_code'] ?? '');
        }
    } catch (Exception $e) {
        // ignore
    }

    if ($entryTerm === '' && $entryDate !== '') {
        $entryTerm = grad_term_code_from_date($entryDate) ?: '';
    }

    if ($entryTerm === '') $entryTerm = $currentTerm;

    $tn = grad_term_number($entryTerm, $currentTerm);
    if ($tn < 3 || $tn > 4) {
        send_json(['status' => 'error', 'message' => 'Thesis/Project upload is available only in Term 3 and Term 4.'], 403);
    }
} catch (Exception $e) {
    // If term data is unavailable, default to allowing.
}

function ensure_documents_doc_type_support(PDO $pdo): void
{
    try {
        $row = $pdo->query("SHOW COLUMNS FROM documents LIKE 'doc_type'")->fetch(PDO::FETCH_ASSOC);
        if (!$row) return;

        $type = strtolower((string)($row['Type'] ?? ''));
        $nullable = ((string)($row['Null'] ?? 'YES')) === 'YES';
        $nullSql = $nullable ? 'NULL' : 'NOT NULL';

        $requiredValue = 'thesis_project';
        $needsUpgrade = false;

        if (strpos($type, 'enum(') === 0) {
            $needsUpgrade = true;
        } elseif (preg_match('/^varchar\\((\\d+)\\)/', $type, $m)) {
            $len = (int)$m[1];
            if ($len > 0 && $len < strlen($requiredValue)) $needsUpgrade = true;
        } elseif (preg_match('/^char\\((\\d+)\\)/', $type, $m)) {
            $len = (int)$m[1];
            if ($len > 0 && $len < strlen($requiredValue)) $needsUpgrade = true;
        }

        if ($needsUpgrade) {
            $pdo->exec("ALTER TABLE documents MODIFY COLUMN doc_type VARCHAR(64) $nullSql");
        }
    } catch (Exception $e) {
        // Best-effort only.
    }
}

if (!isset($_FILES['file'])) {
    send_json(['status' => 'error', 'message' => 'No file received.'], 400);
}

$file = $_FILES['file'];
$studentId = (string)($user['id'] ?? '');

if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    send_json(['status' => 'error', 'message' => 'File upload error code: ' . (string)($file['error'] ?? '')], 400);
}

$maxBytes = (int)(getenv('UPLOAD_MAX_BYTES') ?: (10 * 1024 * 1024));
if ((int)($file['size'] ?? 0) > $maxBytes) {
    send_json(['status' => 'error', 'message' => 'File too large.'], 400);
}

$originalName = (string)($file['name'] ?? 'upload');
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedExt = ['pdf'];
if (!in_array($ext, $allowedExt, true)) {
    send_json(['status' => 'error', 'message' => 'Unsupported file type. Please upload a PDF.'], 400);
}

$tmpPath = (string)($file['tmp_name'] ?? '');
if ($tmpPath === '' || !is_file($tmpPath)) {
    send_json(['status' => 'error', 'message' => 'Invalid upload.'], 400);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($tmpPath);
$allowedMime = ['application/pdf'];
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
    ensure_documents_doc_type_support($pdo);

    $stmt = $pdo->prepare(
        "INSERT INTO documents (student_id, doc_type, file_path, status)
         VALUES (:sid, 'thesis_project', :fpath, 'pending')"
    );
    $stmt->bindParam(':sid', $studentId);
    $stmt->bindParam(':fpath', $filename);

    if (!$stmt->execute()) {
        send_json(['status' => 'error', 'message' => 'Database save failed.'], 500);
    }

    send_json(['status' => 'success', 'message' => 'Thesis/Project file uploaded successfully!', 'file' => $filename]);
} catch (Exception $e) {
    if (isset($targetFile) && is_string($targetFile) && $targetFile !== '' && is_file($targetFile)) {
        @unlink($targetFile);
    }
    send_json(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()], 500);
}

