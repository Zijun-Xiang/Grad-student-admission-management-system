<?php
require_once __DIR__ . '/../bootstrap.php';
require_login();

include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';

if (!assignments_tables_ready($pdo)) {
    send_json(['status' => 'error', 'message' => 'Assignments tables not found. Run backend/sql/09_assignments.sql first.'], 500);
}

$user = current_user();
$role = normalize_role((string)($user['role'] ?? ''));
$userId = (string)($user['id'] ?? '');

$submissionId = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;
if ($submissionId <= 0) {
    send_json(['status' => 'error', 'message' => 'Missing submission_id.'], 400);
}

try {
    $stmt = $pdo->prepare(
        "SELECT s.id, s.student_id, s.assignment_id, a.created_by
         FROM assignment_submissions s
         JOIN assignments a ON a.id = s.assignment_id
         WHERE s.id = :sid
         LIMIT 1"
    );
    $stmt->bindParam(':sid', $submissionId, PDO::PARAM_INT);
    $stmt->execute();
    $meta = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$meta) send_json(['status' => 'error', 'message' => 'Submission not found.'], 404);

    $studentId = (string)($meta['student_id'] ?? '');
    $createdBy = (string)($meta['created_by'] ?? '');

    $allowed = false;
    if ($role === 'admin') $allowed = true;
    elseif ($role === 'faculty' && $createdBy === $userId) $allowed = true;
    elseif ($role === 'student' && $studentId === $userId) $allowed = true;
    if (!$allowed) send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);

    $stmtC = $pdo->prepare(
        "SELECT c.id,
                c.comment,
                c.created_at,
                u.username AS author_username,
                u.role AS author_role
         FROM assignment_submission_comments c
         LEFT JOIN users u ON u.user_id = c.author_id
         WHERE c.submission_id = :sid
         ORDER BY c.created_at ASC, c.id ASC"
    );
    $stmtC->bindParam(':sid', $submissionId, PDO::PARAM_INT);
    $stmtC->execute();
    $rows = $stmtC->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

