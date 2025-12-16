<?php
require_once __DIR__ . '/../bootstrap.php';

$user = require_login(['faculty']);
require_method('POST');
include_once '../db.php';

$data = get_json_input();
$studentId = (string)($data['student_id'] ?? '');
$action = (string)($data['action'] ?? '');
$profId = (string)$user['id'];

if ($studentId === '' || ($action !== 'accept' && $action !== 'reject')) {
    send_json(['status' => 'error', 'message' => 'Missing/invalid data.'], 400);
}

try {
    if ($action === 'accept') {
        $stmt = $pdo->prepare("UPDATE student_details
                               SET mp_status = 'approved'
                               WHERE student_id = :sid AND major_professor_id = :pid AND mp_status = 'pending'");
        $stmt->bindParam(':sid', $studentId);
        $stmt->bindParam(':pid', $profId);
        $stmt->execute();

        if ($stmt->rowCount() <= 0) {
            send_json(['status' => 'error', 'message' => 'No pending request found for you.'], 409);
        }

        $stmtHold = $pdo->prepare("UPDATE holds SET is_active = FALSE, resolved_at = NOW()
                                   WHERE student_id = :sid AND hold_type = 'major_professor'");
        $stmtHold->bindParam(':sid', $studentId);
        $stmtHold->execute();

        $releaseCode = null;
        try {
            $releaseCode = generate_registrar_code();
            $createdBy = (string)($profId ?: '');
            $payload = json_encode(['action' => 'accept', 'major_professor_id' => $profId]);
            $stmtSig = $pdo->prepare(
                "INSERT INTO registrar_signals (student_id, hold_type, code, created_by, payload)
                 VALUES (:sid, 'major_professor', :code, :by, :payload)"
            );
            $stmtSig->bindParam(':sid', $studentId);
            $stmtSig->bindParam(':code', $releaseCode);
            $stmtSig->bindParam(':by', $createdBy);
            $stmtSig->bindParam(':payload', $payload);
            $stmtSig->execute();
        } catch (Exception $e) {
            $releaseCode = null;
        }

        send_json([
            'status' => 'success',
            'message' => 'Student accepted. Hold lifted.',
            'registrar_code' => $releaseCode,
        ]);
    }

    $stmt = $pdo->prepare("UPDATE student_details
                           SET mp_status = 'none', major_professor_id = NULL
                           WHERE student_id = :sid AND major_professor_id = :pid AND mp_status = 'pending'");
    $stmt->bindParam(':sid', $studentId);
    $stmt->bindParam(':pid', $profId);
    $stmt->execute();

    if ($stmt->rowCount() <= 0) {
        send_json(['status' => 'error', 'message' => 'No pending request found for you.'], 409);
    }

    send_json(['status' => 'success', 'message' => 'Request rejected.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
