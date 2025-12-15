<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['student']);
require_method('POST');

include_once '../db.php';

$data = get_json_input();
$docId = (string)($data['doc_id'] ?? '');
if ($docId === '') {
    send_json(['status' => 'error', 'message' => 'Missing doc_id.'], 400);
}

$studentId = (string)(current_user()['id'] ?? '');

try {
    $stmt = $pdo->prepare("SELECT doc_id, student_id, file_path, status FROM documents WHERE doc_id = :did LIMIT 1");
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
        send_json(['status' => 'error', 'message' => 'Approved documents cannot be deleted.'], 400);
    }

    $filePath = (string)($doc['file_path'] ?? '');

    $del = $pdo->prepare("DELETE FROM documents WHERE doc_id = :did AND student_id = :sid");
    $del->bindParam(':did', $docId);
    $del->bindParam(':sid', $studentId);
    $del->execute();

    // Best-effort cleanup related comments (no FK in dev DB).
    try {
        $pdo->prepare("DELETE FROM document_comments WHERE doc_id = :did")->execute([':did' => $docId]);
    } catch (Exception $e) {
        // ignore
    }

    // Best-effort file cleanup.
    $basename = basename($filePath);
    if ($basename !== '') {
        $uploadsRoot = realpath(__DIR__ . '/../uploads');
        $fullPath = $uploadsRoot ? realpath($uploadsRoot . DIRECTORY_SEPARATOR . $basename) : false;
        if ($uploadsRoot && $fullPath && strncmp($fullPath, $uploadsRoot, strlen($uploadsRoot)) === 0 && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    send_json(['status' => 'success', 'message' => 'Document deleted.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
