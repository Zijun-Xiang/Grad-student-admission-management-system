<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/course_actions_common.php';

$facultyId = (string)(current_user()['id'] ?? '');
$data = get_json_input();
$actionId = trim((string)($data['action_id'] ?? ''));
if ($actionId === '' || !ctype_digit($actionId) || (int)$actionId <= 0) {
    send_json(['status' => 'error', 'message' => 'Invalid action_id.'], 400);
}

try {
    ensure_advisee_course_actions_table($pdo);

    $stmt = $pdo->prepare(
        "UPDATE advisee_course_actions
         SET status = 'cancelled', cancelled_at = NOW()
         WHERE id = :id AND faculty_id = :fid AND status = 'pending'"
    );
    $stmt->bindValue(':id', (int)$actionId, PDO::PARAM_INT);
    $stmt->bindParam(':fid', $facultyId);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        send_json(['status' => 'error', 'message' => 'Not found or not cancellable.'], 404);
    }

    send_json(['status' => 'success', 'message' => 'Cancelled.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

