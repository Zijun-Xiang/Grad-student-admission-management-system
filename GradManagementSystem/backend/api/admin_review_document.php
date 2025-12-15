<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['admin']);
require_method('POST');

include_once '../db.php';

$data = get_json_input();
$docId = (string)($data['doc_id'] ?? '');
$action = (string)($data['action'] ?? '');
$comment = isset($data['comment']) ? (string)$data['comment'] : null;

if ($docId === '' || ($action !== 'approve' && $action !== 'reject')) {
    send_json(['status' => 'error', 'message' => 'Missing/invalid data.'], 400);
}

try {
    $stmtDocRow = $pdo->prepare("SELECT doc_id, student_id, doc_type FROM documents WHERE doc_id = :did LIMIT 1");
    $stmtDocRow->bindParam(':did', $docId);
    $stmtDocRow->execute();
    $doc = $stmtDocRow->fetch(PDO::FETCH_ASSOC);
    if (!$doc) {
        send_json(['status' => 'error', 'message' => 'Document not found.'], 404);
    }

    $studentId = (string)$doc['student_id'];
    $docType = (string)$doc['doc_type'];

    if ($action === 'reject') {
        $stmtDoc = $pdo->prepare("UPDATE documents SET status = 'rejected', admin_comment = :comment WHERE doc_id = :did");
        $stmtDoc->bindParam(':comment', $comment);
        $stmtDoc->bindParam(':did', $docId);
        $stmtDoc->execute();
        send_json(['status' => 'success', 'message' => 'Document rejected.', 'doc_type' => $docType]);
    }

    $stmtDoc = $pdo->prepare("UPDATE documents SET status = 'approved', admin_comment = :comment WHERE doc_id = :did");
    $stmtDoc->bindParam(':comment', $comment);
    $stmtDoc->bindParam(':did', $docId);
    $stmtDoc->execute();

    send_json([
        'status' => 'success',
        'message' => 'Document approved. You can now lift the corresponding Hold in the Active Holds table (if present).',
        'doc_type' => $docType,
        'student_id' => $studentId,
    ]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
