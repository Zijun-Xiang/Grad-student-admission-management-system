<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['student']);
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/course_actions_common.php';

$studentId = (string)(current_user()['id'] ?? '');
$data = get_json_input();
$actionId = trim((string)($data['action_id'] ?? ''));
if ($actionId === '' || !ctype_digit($actionId) || (int)$actionId <= 0) {
    send_json(['status' => 'error', 'message' => 'Invalid action_id.'], 400);
}

function ensure_reads_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS advisee_course_action_reads (
            action_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (action_id, student_id),
            KEY idx_acar_student (student_id, read_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

try {
    ensure_advisee_course_actions_table($pdo);
    ensure_reads_table($pdo);

    // Verify the action exists and belongs to this student.
    $stmtChk = $pdo->prepare("SELECT 1 FROM advisee_course_actions WHERE id = :id AND student_id = :sid LIMIT 1");
    $stmtChk->bindValue(':id', (int)$actionId, PDO::PARAM_INT);
    $stmtChk->bindParam(':sid', $studentId);
    $stmtChk->execute();
    if (!$stmtChk->fetchColumn()) {
        send_json(['status' => 'error', 'message' => 'Not found.'], 404);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO advisee_course_action_reads (action_id, student_id, read_at)
         VALUES (:aid, :sid, NOW())
         ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)"
    );
    $stmt->bindValue(':aid', (int)$actionId, PDO::PARAM_INT);
    $stmt->bindParam(':sid', $studentId);
    $stmt->execute();

    send_json(['status' => 'success', 'message' => 'Dismissed.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

