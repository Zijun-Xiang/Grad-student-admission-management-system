<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';

if (!assignments_tables_ready($pdo)) {
    send_json(['status' => 'error', 'message' => 'Assignments tables not found. Run backend/sql/09_assignments.sql first.'], 500);
}

$facultyId = (string)(current_user()['id'] ?? '');
$data = get_json_input();
$submissionId = (int)($data['submission_id'] ?? 0);
$comment = trim((string)($data['comment'] ?? ''));

if ($submissionId <= 0 || $comment === '') {
    send_json(['status' => 'error', 'message' => 'Missing submission_id/comment.'], 400);
}

try {
    $stmt = $pdo->prepare(
        "SELECT s.id, s.assignment_id, a.created_by
         FROM assignment_submissions s
         JOIN assignments a ON a.id = s.assignment_id
         WHERE s.id = :sid
         LIMIT 1"
    );
    $stmt->bindParam(':sid', $submissionId, PDO::PARAM_INT);
    $stmt->execute();
    $meta = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$meta) send_json(['status' => 'error', 'message' => 'Submission not found.'], 404);

    if ((string)($meta['created_by'] ?? '') !== $facultyId) {
        send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);
    }

    $stmtI = $pdo->prepare(
        "INSERT INTO assignment_submission_comments (submission_id, author_id, comment)
         VALUES (:sid, :aid, :c)"
    );
    $stmtI->bindParam(':sid', $submissionId, PDO::PARAM_INT);
    $stmtI->bindParam(':aid', $facultyId);
    $stmtI->bindParam(':c', $comment);
    $stmtI->execute();

    send_json(['status' => 'success', 'message' => 'Comment added.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

