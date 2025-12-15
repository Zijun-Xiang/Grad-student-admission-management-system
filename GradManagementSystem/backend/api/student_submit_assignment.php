<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['student']);
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';

if (!assignments_tables_ready($pdo)) {
    send_json(['status' => 'error', 'message' => 'Assignments tables not found. Run backend/sql/09_assignments.sql first.'], 500);
}

$studentId = (string)(current_user()['id'] ?? '');
$assignmentId = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;

if ($assignmentId <= 0) {
    send_json(['status' => 'error', 'message' => 'Missing assignment_id.'], 400);
}
if (!isset($_FILES['file'])) {
    send_json(['status' => 'error', 'message' => 'No file received.'], 400);
}

try {
    ensure_assignment_grading_columns($pdo);
    $gradingEnabled = assignment_grading_enabled($pdo);

    // Ensure assignment exists
    $stmtA = $pdo->prepare("SELECT id FROM assignments WHERE id = :aid LIMIT 1");
    $stmtA->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
    $stmtA->execute();
    if (!$stmtA->fetchColumn()) {
        send_json(['status' => 'error', 'message' => 'Assignment not found.'], 404);
    }

    if (!assignment_student_has_access($pdo, $studentId, $assignmentId)) {
        send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);
    }

    $file = $_FILES['file'];
    $allowedExt = ['pdf', 'doc', 'docx', 'zip', 'txt', 'jpg', 'jpeg', 'png'];
    $allowedMime = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/x-zip-compressed',
        'text/plain',
        'image/jpeg',
        'image/png',
    ];
    $maxBytes = (int)(getenv('UPLOAD_MAX_BYTES') ?: (10 * 1024 * 1024));
    [$ext, $tmpPath] = validate_upload($file, $allowedExt, $allowedMime, $maxBytes);

    $dir = ensure_upload_dir('assignment_submissions');
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetFile = $dir . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $targetFile)) {
        send_json(['status' => 'error', 'message' => 'Failed to move file. Permission denied?'], 500);
    }
    $relPath = 'assignment_submissions/' . $filename;

    // Upsert submission; delete old file best-effort.
    $oldPath = null;
    try {
        $stmtOld = $pdo->prepare("SELECT file_path FROM assignment_submissions WHERE assignment_id = :aid AND student_id = :sid LIMIT 1");
        $stmtOld->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
        $stmtOld->bindParam(':sid', $studentId);
        $stmtOld->execute();
        $old = $stmtOld->fetchColumn();
        if ($old !== false && $old !== null) $oldPath = (string)$old;
    } catch (Exception $e) {
        $oldPath = null;
    }

    if ($gradingEnabled) {
        // On resubmission, clear any existing grade so faculty can re-grade.
        $stmtUp = $pdo->prepare(
            "INSERT INTO assignment_submissions (assignment_id, student_id, file_path, submitted_at, grade, graded_at, graded_by)
             VALUES (:aid, :sid, :fp, NOW(), NULL, NULL, NULL)
             ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), submitted_at = VALUES(submitted_at), grade = NULL, graded_at = NULL, graded_by = NULL"
        );
    } else {
        $stmtUp = $pdo->prepare(
            "INSERT INTO assignment_submissions (assignment_id, student_id, file_path, submitted_at)
             VALUES (:aid, :sid, :fp, NOW())
             ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), submitted_at = VALUES(submitted_at)"
        );
    }
    $stmtUp->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
    $stmtUp->bindParam(':sid', $studentId);
    $stmtUp->bindParam(':fp', $relPath);
    $stmtUp->execute();

    if ($oldPath && $oldPath !== $relPath) {
        $uploadsRoot = realpath(__DIR__ . '/../uploads');
        $fullPath = $uploadsRoot ? realpath($uploadsRoot . DIRECTORY_SEPARATOR . $oldPath) : false;
        if ($uploadsRoot && $fullPath && strncmp($fullPath, $uploadsRoot, strlen($uploadsRoot)) === 0 && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    send_json(['status' => 'success', 'message' => 'Submitted successfully.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
