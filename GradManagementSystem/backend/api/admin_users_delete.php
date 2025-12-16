<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['admin']);
require_method('POST');

include_once '../db.php';

$data = get_json_input();
$userId = (string)($data['user_id'] ?? '');
if ($userId === '') {
    send_json(['status' => 'error', 'message' => 'Missing user_id.'], 400);
}

try {
    $stmtUser = $pdo->prepare('SELECT user_id, username, role FROM users WHERE user_id = :uid LIMIT 1');
    $stmtUser->bindParam(':uid', $userId);
    $stmtUser->execute();
    $u = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        send_json(['status' => 'error', 'message' => 'User not found.'], 404);
    }
    if ((string)$u['role'] === 'admin') {
        send_json(['status' => 'error', 'message' => 'Deleting admin users is disabled.'], 403);
    }

    $pdo->beginTransaction();

    // If deleting a faculty, detach from students.
    if ((string)$u['role'] === 'faculty') {
        try {
            $stmt = $pdo->prepare("UPDATE student_details SET major_professor_id = NULL, mp_status = 'none' WHERE major_professor_id = :fid");
            $stmt->bindParam(':fid', $userId);
            $stmt->execute();
        } catch (Exception $e) {
            // ignore
        }
    }

    // Documents: delete files + rows (best-effort)
    try {
        $stmtDocs = $pdo->prepare('SELECT file_path FROM documents WHERE student_id = :sid');
        $stmtDocs->bindParam(':sid', $userId);
        $stmtDocs->execute();
        $files = $stmtDocs->fetchAll(PDO::FETCH_COLUMN);

        $uploadsRoot = realpath(__DIR__ . '/../uploads');
        if ($uploadsRoot) {
            foreach ($files as $f) {
                $basename = basename((string)$f);
                $fullPath = realpath($uploadsRoot . DIRECTORY_SEPARATOR . $basename);
                if ($fullPath && strncmp($fullPath, $uploadsRoot, strlen($uploadsRoot)) === 0 && is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }

        $stmt = $pdo->prepare('DELETE FROM documents WHERE student_id = :sid');
        $stmt->bindParam(':sid', $userId);
        $stmt->execute();
    } catch (Exception $e) {
        // ignore
    }

    // Related student tables (best-effort)
    foreach (['holds' => 'student_id', 'student_registrations' => 'student_id', 'student_deficiencies' => 'student_id', 'registrar_signals' => 'student_id', 'thesis_projects' => 'student_id', 'student_details' => 'student_id'] as $table => $col) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `$table` WHERE `$col` = :sid");
            $stmt->bindParam(':sid', $userId);
            $stmt->execute();
        } catch (Exception $e) {
            // ignore if table/column doesn't exist
        }
    }

    $stmtDel = $pdo->prepare('DELETE FROM users WHERE user_id = :uid');
    $stmtDel->bindParam(':uid', $userId);
    $stmtDel->execute();

    if ($stmtDel->rowCount() <= 0) {
        throw new Exception('Failed to delete user.');
    }

    $pdo->commit();

    send_json(['status' => 'success', 'message' => 'User deleted.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

