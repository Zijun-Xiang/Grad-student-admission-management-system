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
$assignmentId = (int)($data['assignment_id'] ?? 0);
if ($assignmentId <= 0) {
    send_json(['status' => 'error', 'message' => 'Missing assignment_id.'], 400);
}

try {
    // Verify ownership + gather files to delete.
    $stmtA = $pdo->prepare("SELECT id, attachment_path FROM assignments WHERE id = :aid AND created_by = :fid LIMIT 1");
    $stmtA->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
    $stmtA->bindParam(':fid', $facultyId);
    $stmtA->execute();
    $a = $stmtA->fetch(PDO::FETCH_ASSOC);
    if (!$a) {
        send_json(['status' => 'error', 'message' => 'Forbidden or assignment not found.'], 403);
    }

    $attachment = (string)($a['attachment_path'] ?? '');
    $submissionFiles = [];
    try {
        $stmtS = $pdo->prepare("SELECT file_path FROM assignment_submissions WHERE assignment_id = :aid");
        $stmtS->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
        $stmtS->execute();
        $submissionFiles = array_map(fn($r) => (string)($r['file_path'] ?? ''), $stmtS->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        $submissionFiles = [];
    }

    // Delete assignment; FK cascade removes targets/submissions/comments.
    $del = $pdo->prepare("DELETE FROM assignments WHERE id = :aid AND created_by = :fid");
    $del->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
    $del->bindParam(':fid', $facultyId);
    $del->execute();

    // Best-effort file cleanup.
    $uploadsRoot = realpath(__DIR__ . '/../uploads');
    if ($uploadsRoot) {
        $paths = [];
        if ($attachment !== '') $paths[] = $attachment;
        foreach ($submissionFiles as $p) {
            if ($p !== '') $paths[] = $p;
        }

        foreach ($paths as $rel) {
            $full = realpath($uploadsRoot . DIRECTORY_SEPARATOR . $rel);
            if ($full && strncmp($full, $uploadsRoot, strlen($uploadsRoot)) === 0 && is_file($full)) {
                @unlink($full);
            }
        }
    }

    send_json(['status' => 'success', 'message' => 'Assignment deleted.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

