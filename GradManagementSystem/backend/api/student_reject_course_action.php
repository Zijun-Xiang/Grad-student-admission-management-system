<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['student']);
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/course_actions_common.php';

$studentId = (string)(current_user()['id'] ?? '');
$data = get_json_input();
$actionId = trim((string)($data['action_id'] ?? ''));
$comment = trim((string)($data['comment'] ?? ''));

if ($actionId === '' || !ctype_digit($actionId) || (int)$actionId <= 0) {
    send_json(['status' => 'error', 'message' => 'Invalid action_id.'], 400);
}

try {
    ensure_advisee_course_actions_table($pdo);

    $stmt = $pdo->prepare(
        "UPDATE advisee_course_actions
         SET status = 'rejected',
             rejected_at = NOW(),
             student_comment = :cmt
         WHERE id = :id
           AND student_id = :sid
           AND status = 'pending'"
    );
    $stmt->bindValue(':id', (int)$actionId, PDO::PARAM_INT);
    $stmt->bindParam(':sid', $studentId);
    $stmt->bindValue(':cmt', $comment !== '' ? $comment : null, $comment !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        send_json(['status' => 'error', 'message' => 'Request not found or not rejectable.'], 404);
    }

    send_json(['status' => 'success', 'message' => 'Rejected.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

