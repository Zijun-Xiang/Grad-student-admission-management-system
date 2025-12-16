<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);
require_method('POST');

include_once '../db.php';

$facultyId = (string)(current_user()['id'] ?? '');
$researchMethodCourseCode = getenv('RESEARCH_METHOD_COURSE_CODE') ?: 'CS690';

$data = get_json_input();
$studentId = (string)($data['student_id'] ?? '');
$docId = isset($data['doc_id']) ? (string)$data['doc_id'] : '';
$action = strtolower(trim((string)($data['action'] ?? '')));
$comment = isset($data['comment']) ? trim((string)$data['comment']) : '';

if ($studentId === '' || ($action !== 'approve' && $action !== 'reject')) {
    send_json(['status' => 'error', 'message' => 'Missing/invalid data.'], 400);
}

function ensure_document_comments_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS document_comments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            doc_id BIGINT UNSIGNED NOT NULL,
            author_id BIGINT UNSIGNED NOT NULL,
            author_role VARCHAR(32) NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_doc_comments_doc (doc_id),
            KEY idx_doc_comments_author (author_id)
        )"
    );
}

function pick_first_existing_column(PDO $pdo, string $table, array $candidates): ?string
{
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        $available = [];
        foreach ($cols as $c) $available[] = (string)($c['Field'] ?? '');
        foreach ($candidates as $cand) {
            if (in_array($cand, $available, true)) return $cand;
        }
    } catch (Exception $e) {
        return null;
    }
    return null;
}

try {
    // Must be faculty's advisee.
    $stmtAdv = $pdo->prepare(
        "SELECT 1
         FROM student_details sd
         WHERE sd.student_id = :sid
           AND sd.major_professor_id = :fid
           AND (sd.mp_status = 'approved' OR sd.mp_status = 'accepted')
         LIMIT 1"
    );
    $stmtAdv->bindParam(':sid', $studentId);
    $stmtAdv->bindParam(':fid', $facultyId);
    $stmtAdv->execute();
    if (!$stmtAdv->fetchColumn()) {
        send_json(['status' => 'error', 'message' => 'Forbidden (not your advisee).'], 403);
    }

    // Find the proof document (latest by upload_date/created_at/doc_id) unless doc_id is specified.
    $docsDateCol = pick_first_existing_column($pdo, 'documents', ['upload_date', 'created_at']) ?: 'doc_id';
    $proof = null;

    if ($docId !== '') {
        $stmtDoc = $pdo->prepare(
            "SELECT doc_id, student_id, doc_type, status
             FROM documents
             WHERE doc_id = :did AND student_id = :sid AND doc_type = 'research_method_proof'
             LIMIT 1"
        );
        $stmtDoc->bindParam(':did', $docId);
        $stmtDoc->bindParam(':sid', $studentId);
        $stmtDoc->execute();
        $proof = $stmtDoc->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmtDoc = $pdo->prepare(
            "SELECT doc_id, student_id, doc_type, status
             FROM documents
             WHERE student_id = :sid AND doc_type = 'research_method_proof'
             ORDER BY `$docsDateCol` DESC, doc_id DESC
             LIMIT 1"
        );
        $stmtDoc->bindParam(':sid', $studentId);
        $stmtDoc->execute();
        $proof = $stmtDoc->fetch(PDO::FETCH_ASSOC);
    }

    if (!$proof) {
        send_json(['status' => 'error', 'message' => 'No Research Method proof found for this student.'], 404);
    }

    $proofDocId = (string)($proof['doc_id'] ?? '');
    if ($proofDocId === '') {
        send_json(['status' => 'error', 'message' => 'Invalid proof document.'], 500);
    }

    // Approval is based on faculty verification of the uploaded proof.
    // (We do not require a matching course registration row here.)

    // Ensure comment thread table exists (DDL before transaction).
    try {
        ensure_document_comments_table($pdo);
    } catch (Exception $e) {
        // ignore
    }

    // Capture term_code from active hold (if present).
    $termCode = null;
    try {
        $stmtTerm = $pdo->prepare(
            "SELECT term_code FROM holds
             WHERE student_id = :sid AND hold_type = 'research_method' AND is_active = TRUE
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmtTerm->bindParam(':sid', $studentId);
        $stmtTerm->execute();
        $tc = $stmtTerm->fetchColumn();
        if ($tc !== false && $tc !== null && $tc !== '') $termCode = (string)$tc;
    } catch (Exception $e) {
        $termCode = null;
    }

    $pdo->beginTransaction();

    $commentOrNull = $comment !== '' ? $comment : null;
    if ($action === 'approve') {
        $stmtUp = $pdo->prepare("UPDATE documents SET status = 'approved', admin_comment = :c WHERE doc_id = :did");
        $stmtUp->bindValue(':c', $commentOrNull, $commentOrNull === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmtUp->bindParam(':did', $proofDocId);
        $stmtUp->execute();

        // Lift the research_method hold (if any).
        $stmtHold = $pdo->prepare(
            "UPDATE holds SET is_active = FALSE, resolved_at = NOW()
             WHERE student_id = :sid AND hold_type = 'research_method' AND is_active = TRUE"
        );
        $stmtHold->bindParam(':sid', $studentId);
        $stmtHold->execute();

        $registrarCode = null;
        try {
            $registrarCode = generate_registrar_code();
            $createdBy = (string)(current_user()['id'] ?? '');
            $payload = json_encode([
                'action' => 'faculty_approve_research_method_proof',
                'doc_id' => $proofDocId,
                'course_code' => $researchMethodCourseCode,
            ]);
            $stmtSig = $pdo->prepare(
                "INSERT INTO registrar_signals (student_id, hold_type, term_code, code, created_by, payload)
                 VALUES (:sid, 'research_method', :term, :code, :by, :payload)"
            );
            $stmtSig->bindParam(':sid', $studentId);
            $stmtSig->bindParam(':term', $termCode);
            $stmtSig->bindParam(':code', $registrarCode);
            $stmtSig->bindParam(':by', $createdBy);
            $stmtSig->bindParam(':payload', $payload);
            $stmtSig->execute();
        } catch (Exception $e) {
            $registrarCode = null;
        }

        if ($comment !== '') {
            try {
                $role = 'faculty';
                $stmtC = $pdo->prepare(
                    "INSERT INTO document_comments (doc_id, author_id, author_role, comment)
                     VALUES (:did, :aid, :ar, :c)"
                );
                $stmtC->bindParam(':did', $proofDocId);
                $stmtC->bindParam(':aid', $facultyId);
                $stmtC->bindParam(':ar', $role);
                $stmtC->bindParam(':c', $comment);
                $stmtC->execute();
            } catch (Exception $e) {
                // ignore
            }
        }

        $pdo->commit();
        send_json([
            'status' => 'success',
            'message' => 'Approved. Research Method hold lifted.',
            'doc_id' => $proofDocId,
            'doc_status' => 'approved',
            'registrar_code' => $registrarCode,
        ]);
    }

    // Reject
    $stmtUp = $pdo->prepare("UPDATE documents SET status = 'rejected', admin_comment = :c WHERE doc_id = :did");
    $stmtUp->bindValue(':c', $commentOrNull, $commentOrNull === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmtUp->bindParam(':did', $proofDocId);
    $stmtUp->execute();

    if ($comment !== '') {
        try {
            $role = 'faculty';
            $stmtC = $pdo->prepare(
                "INSERT INTO document_comments (doc_id, author_id, author_role, comment)
                 VALUES (:did, :aid, :ar, :c)"
            );
            $stmtC->bindParam(':did', $proofDocId);
            $stmtC->bindParam(':aid', $facultyId);
            $stmtC->bindParam(':ar', $role);
            $stmtC->bindParam(':c', $comment);
            $stmtC->execute();
        } catch (Exception $e) {
            // ignore
        }
    }

    $pdo->commit();
    send_json([
        'status' => 'success',
        'message' => 'Rejected. Student must re-upload proof.',
        'doc_id' => $proofDocId,
        'doc_status' => 'rejected',
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
