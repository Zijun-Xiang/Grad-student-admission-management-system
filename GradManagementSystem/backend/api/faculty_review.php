<?php
require_once __DIR__ . '/../bootstrap.php';
// Document approvals are handled in the Admin portal.
send_json(['status' => 'error', 'message' => 'Deprecated. Use Admin portal to approve/reject documents.'], 410);
require_method('POST');

include_once '../db.php';

$data = get_json_input();
$docId = (string)($data['doc_id'] ?? '');
$action = (string)($data['action'] ?? '');
$comment = isset($data['comment']) ? (string)$data['comment'] : null;
$termCode = isset($data['term_code']) ? (string)$data['term_code'] : null;

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

    if ($action === 'approve') {
        $stmtDoc = $pdo->prepare("UPDATE documents SET status = 'approved', admin_comment = :comment WHERE doc_id = :did");
        $stmtDoc->bindParam(':comment', $comment);
        $stmtDoc->bindParam(':did', $docId);
        $stmtDoc->execute();

        $holdType = null;
        if ($docType === 'admission_letter') {
            $holdType = 'admission_letter';
        } elseif ($docType === 'major_professor_form') {
            $holdType = 'major_professor_form';
        }

        if ($holdType !== null) {
            $stmtHold = $pdo->prepare("UPDATE holds SET is_active = FALSE, resolved_at = NOW()
                                       WHERE student_id = :sid AND hold_type = :ht AND is_active = TRUE");
            $stmtHold->bindParam(':sid', $studentId);
            $stmtHold->bindParam(':ht', $holdType);
            $stmtHold->execute();

            $releaseCode = null;
            try {
                $releaseCode = generate_registrar_code();
                $createdBy = (string)(current_user()['id'] ?? '');
                $payload = json_encode(['doc_id' => $docId, 'doc_type' => $docType, 'action' => 'approve']);
                $stmtSig = $pdo->prepare(
                    "INSERT INTO registrar_signals (student_id, hold_type, term_code, code, created_by, payload)
                     VALUES (:sid, :ht, :term, :code, :by, :payload)"
                );
                $stmtSig->bindParam(':sid', $studentId);
                $stmtSig->bindParam(':ht', $holdType);
                $stmtSig->bindParam(':term', $termCode);
                $stmtSig->bindParam(':code', $releaseCode);
                $stmtSig->bindParam(':by', $createdBy);
                $stmtSig->bindParam(':payload', $payload);
                $stmtSig->execute();
            } catch (Exception $e) {
                $releaseCode = null;
            }

            send_json([
                'status' => 'success',
                'message' => 'Document approved and Hold lifted.',
                'registrar_code' => $releaseCode,
                'doc_type' => $docType,
            ]);
        }

        send_json([
            'status' => 'success',
            'message' => 'Document approved.',
            'doc_type' => $docType,
        ]);
    }

    $stmtDoc = $pdo->prepare("UPDATE documents SET status = 'rejected', admin_comment = :comment WHERE doc_id = :did");
    $stmtDoc->bindParam(':comment', $comment);
    $stmtDoc->bindParam(':did', $docId);
    $stmtDoc->execute();

    send_json(['status' => 'success', 'message' => 'Document rejected.', 'doc_type' => $docType]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
