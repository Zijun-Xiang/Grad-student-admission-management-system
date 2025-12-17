<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/course_actions_common.php';

$facultyId = (string)(current_user()['id'] ?? '');

$data = get_json_input();
$studentId = trim((string)($data['student_id'] ?? ''));
$actionType = strtolower(trim((string)($data['action_type'] ?? '')));
$courseCode = strtoupper(trim((string)($data['course_code'] ?? '')));
$comment = trim((string)($data['comment'] ?? ''));

if ($studentId === '' || $courseCode === '' || !in_array($actionType, ['add', 'drop'], true)) {
    send_json(['status' => 'error', 'message' => 'Missing or invalid data.'], 400);
}

try {
    ensure_advisee_course_actions_table($pdo);

    if (!faculty_owns_advisee($pdo, $facultyId, $studentId)) {
        send_json(['status' => 'error', 'message' => 'Forbidden (not your advisee).'], 403);
    }

    // Course must exist.
    $stmtC = $pdo->prepare("SELECT 1 FROM core_courses WHERE course_code = :cc LIMIT 1");
    $stmtC->bindParam(':cc', $courseCode);
    $stmtC->execute();
    if (!$stmtC->fetchColumn()) {
        send_json(['status' => 'error', 'message' => 'Course not found.'], 404);
    }

    // Avoid duplicate pending request.
    $stmtDup = $pdo->prepare(
        "SELECT id
         FROM advisee_course_actions
         WHERE faculty_id = :fid
           AND student_id = :sid
           AND action_type = :at
           AND course_code = :cc
           AND status = 'pending'
         LIMIT 1"
    );
    $stmtDup->bindParam(':fid', $facultyId);
    $stmtDup->bindParam(':sid', $studentId);
    $stmtDup->bindParam(':at', $actionType);
    $stmtDup->bindParam(':cc', $courseCode);
    $stmtDup->execute();
    $existingId = $stmtDup->fetchColumn();
    if ($existingId) {
        send_json(['status' => 'error', 'message' => 'A pending request for this course already exists.'], 409);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO advisee_course_actions (faculty_id, student_id, action_type, course_code, comment, status)
         VALUES (:fid, :sid, :at, :cc, :cmt, 'pending')"
    );
    $stmt->bindParam(':fid', $facultyId);
    $stmt->bindParam(':sid', $studentId);
    $stmt->bindParam(':at', $actionType);
    $stmt->bindParam(':cc', $courseCode);
    $stmt->bindValue(':cmt', $comment !== '' ? $comment : null, $comment !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->execute();

    send_json(['status' => 'success', 'message' => 'Request sent to student.', 'id' => (int)$pdo->lastInsertId()]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

