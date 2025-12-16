<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['admin']);
require_method('POST');

include_once '../db.php';

$data = get_json_input();
$studentId = (string)($data['student_id'] ?? '');
$holdType = (string)($data['hold_type'] ?? '');
$termCode = isset($data['term_code']) ? (string)$data['term_code'] : null;
$force = (bool)($data['force'] ?? false);

if ($studentId === '' || $holdType === '') {
    send_json(['status' => 'error', 'message' => 'Missing student_id/hold_type.'], 400);
}

$researchMethodCourseCode = getenv('RESEARCH_METHOD_COURSE_CODE') ?: 'CS690';

try {
    // Policy: Term 3 (research_method) is lifted by faculty after verification.
    if (!$force && $holdType === 'research_method') {
        send_json(['status' => 'error', 'message' => 'Research Method hold must be lifted by Faculty.'], 403);
    }

    if (!$force) {
        $ok = false;
        if ($holdType === 'admission_letter') {
            $stmtOk = $pdo->prepare(
                "SELECT 1 FROM documents
                 WHERE student_id = :sid AND doc_type = 'admission_letter' AND status = 'approved'
                 LIMIT 1"
            );
            $stmtOk->bindParam(':sid', $studentId);
            $stmtOk->execute();
            $ok = (bool)$stmtOk->fetchColumn();
        } elseif ($holdType === 'major_professor_form') {
            $stmtOk = $pdo->prepare(
                "SELECT 1 FROM documents
                 WHERE student_id = :sid AND doc_type = 'major_professor_form' AND status = 'approved'
                 LIMIT 1"
            );
            $stmtOk->bindParam(':sid', $studentId);
            $stmtOk->execute();
            $ok = (bool)$stmtOk->fetchColumn();
        } elseif ($holdType === 'research_method') {
            $stmtOk = $pdo->prepare(
                "SELECT 1 FROM student_registrations
                 WHERE student_id = :sid AND course_code = :code
                 LIMIT 1"
            );
            $stmtOk->bindParam(':sid', $studentId);
            $stmtOk->bindParam(':code', $researchMethodCourseCode);
            $stmtOk->execute();
            $ok = (bool)$stmtOk->fetchColumn();
        } else {
            $ok = true;
        }

        if (!$ok) {
            send_json(['status' => 'error', 'message' => 'Requirements not satisfied for this hold type.'], 409);
        }
    }

    // Best-effort term_code if not provided.
    if ($termCode === null || $termCode === '') {
        try {
            $stmtHoldTerm = $pdo->prepare(
                "SELECT term_code FROM holds
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
            // ignore
        }
    }

    $stmtHold = $pdo->prepare(
        "UPDATE holds SET is_active = FALSE, resolved_at = NOW()
         WHERE student_id = :sid AND hold_type = :ht AND is_active = TRUE"
    );
    $stmtHold->bindParam(':sid', $studentId);
    $stmtHold->bindParam(':ht', $holdType);
    $stmtHold->execute();

    if ($stmtHold->rowCount() <= 0) {
        send_json(['status' => 'error', 'message' => 'No active hold to lift.'], 404);
    }

    $registrarCode = null;
    try {
        $registrarCode = generate_registrar_code();
        $createdBy = (string)(current_user()['id'] ?? '');
        $payload = json_encode(['action' => 'lift_hold', 'hold_type' => $holdType]);
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

    send_json([
        'status' => 'success',
        'message' => 'Hold lifted.',
        'student_id' => $studentId,
        'hold_type' => $holdType,
        'term_code' => $termCode,
        'registrar_code' => $registrarCode,
    ]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
