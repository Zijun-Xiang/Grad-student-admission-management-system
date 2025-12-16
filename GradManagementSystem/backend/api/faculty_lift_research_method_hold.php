<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);
require_method('POST');

include_once '../db.php';

$facultyId = (string)(current_user()['id'] ?? '');
$researchMethodCourseCode = getenv('RESEARCH_METHOD_COURSE_CODE') ?: 'CS690';

$data = get_json_input();
$studentId = (string)($data['student_id'] ?? '');
if ($studentId === '') {
    send_json(['status' => 'error', 'message' => 'Missing student_id.'], 400);
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

    // Requirement: student must have registered/taken research method.
    $stmtOk = $pdo->prepare(
        "SELECT 1 FROM student_registrations
         WHERE student_id = :sid AND course_code = :code
         LIMIT 1"
    );
    $stmtOk->bindParam(':sid', $studentId);
    $stmtOk->bindParam(':code', $researchMethodCourseCode);
    $stmtOk->execute();
    if (!$stmtOk->fetchColumn()) {
        send_json(['status' => 'error', 'message' => 'Student has not registered Research Method yet.'], 409);
    }

    // Capture term_code from the active hold (if present).
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

    $stmtHold = $pdo->prepare(
        "UPDATE holds SET is_active = FALSE, resolved_at = NOW()
         WHERE student_id = :sid AND hold_type = 'research_method' AND is_active = TRUE"
    );
    $stmtHold->bindParam(':sid', $studentId);
    $stmtHold->execute();
    if ($stmtHold->rowCount() <= 0) {
        send_json(['status' => 'error', 'message' => 'No active research_method hold to lift.'], 404);
    }

    $registrarCode = null;
    try {
        $registrarCode = generate_registrar_code();
        $createdBy = (string)(current_user()['id'] ?? '');
        $payload = json_encode([
            'action' => 'lift_hold',
            'hold_type' => 'research_method',
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

    send_json([
        'status' => 'success',
        'message' => 'Research Method hold lifted.',
        'student_id' => $studentId,
        'term_code' => $termCode,
        'registrar_code' => $registrarCode,
    ]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

