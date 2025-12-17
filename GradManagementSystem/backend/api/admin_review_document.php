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

    $holdLifted = false;
    $registrarCode = null;
    $holdType = null;
    $termCode = null;

    // Convenience: approving Admission Letter immediately lifts the Term 1 hold so students can register.
    if ($docType === 'admission_letter') {
        $holdType = 'admission_letter';
        try {
            // Best-effort get term_code for registrar signal.
            $stmtHoldTerm = $pdo->prepare(
                "SELECT term_code
                 FROM holds
                 WHERE student_id = :sid AND hold_type = :ht AND is_active = TRUE
                 ORDER BY id DESC
                 LIMIT 1"
            );
            $stmtHoldTerm->bindParam(':sid', $studentId);
            $stmtHoldTerm->bindParam(':ht', $holdType);
            $stmtHoldTerm->execute();
            $tc = $stmtHoldTerm->fetchColumn();
            if ($tc !== false && $tc !== null && $tc !== '') $termCode = (string)$tc;
        } catch (Exception $e) {
            $termCode = null;
        }

        try {
            $stmtHold = $pdo->prepare(
                "UPDATE holds
                 SET is_active = FALSE, resolved_at = NOW()
                 WHERE student_id = :sid AND hold_type = :ht AND is_active = TRUE"
            );
            $stmtHold->bindParam(':sid', $studentId);
            $stmtHold->bindParam(':ht', $holdType);
            $stmtHold->execute();
            $holdLifted = $stmtHold->rowCount() > 0;
        } catch (Exception $e) {
            $holdLifted = false;
        }

        if ($holdLifted) {
            try {
                $registrarCode = generate_registrar_code();
                $createdBy = (string)(current_user()['id'] ?? '');
                $payload = json_encode(['action' => 'lift_hold', 'hold_type' => $holdType, 'source' => 'admin_review_document']);
                $stmtSig = $pdo->prepare(
                    "INSERT INTO registrar_signals (student_id, hold_type, term_code, code, created_by, payload)
                     VALUES (:sid, :ht, :term, :code, :by, :payload)"
                );
                $stmtSig->bindParam(':sid', $studentId);
                $stmtSig->bindParam(':ht', $holdType);
                $stmtSig->bindParam(':term', $termCode);
                $stmtSig->bindParam(':code', $registrarCode);
                $stmtSig->bindParam(':by', $createdBy);
                $stmtSig->bindParam(':payload', $payload);
                $stmtSig->execute();
            } catch (Exception $e) {
                $registrarCode = null;
            }
        }
    }

    send_json([
        'status' => 'success',
        'message' => $docType === 'admission_letter'
            ? ($holdLifted ? 'Document approved and hold lifted.' : 'Document approved. (No active admission_letter hold found to lift.)')
            : 'Document approved. You can now lift the corresponding Hold in the Active Holds table (if present).',
        'doc_type' => $docType,
        'student_id' => $studentId,
        'hold_lifted' => $holdLifted,
        'hold_type' => $holdType,
        'term_code' => $termCode,
        'registrar_code' => $registrarCode,
    ]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
