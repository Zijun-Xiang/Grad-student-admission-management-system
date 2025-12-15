<?php
define('BOOTSTRAP_NO_JSON', true);
require_once __DIR__ . '/../bootstrap.php';

$user = require_login();
include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';

if (!assignments_tables_ready($pdo)) {
    http_response_code(500);
    echo 'Assignments tables not found. Run backend/sql/09_assignments.sql first.';
    exit();
}

$submissionId = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;
if ($submissionId <= 0) {
    http_response_code(400);
    echo 'Missing submission_id.';
    exit();
}

$role = normalize_role((string)($user['role'] ?? ''));
$userId = (string)($user['id'] ?? '');

try {
    $stmt = $pdo->prepare(
        "SELECT s.id, s.student_id, s.file_path, a.created_by
         FROM assignment_submissions s
         JOIN assignments a ON a.id = s.assignment_id
         WHERE s.id = :sid
         LIMIT 1"
    );
    $stmt->bindParam(':sid', $submissionId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo 'Not found.';
        exit();
    }

    $studentId = (string)($row['student_id'] ?? '');
    $createdBy = (string)($row['created_by'] ?? '');

    $allowed = false;
    if ($role === 'admin') $allowed = true;
    elseif ($role === 'faculty' && $createdBy === $userId) $allowed = true;
    elseif ($role === 'student' && $studentId === $userId) $allowed = true;
    if (!$allowed) {
        http_response_code(403);
        echo 'Forbidden.';
        exit();
    }

    $path = (string)($row['file_path'] ?? '');
    if ($path === '') {
        http_response_code(404);
        echo 'Not found.';
        exit();
    }

    $uploadsRoot = realpath(__DIR__ . '/../uploads');
    $fullPath = $uploadsRoot ? realpath($uploadsRoot . DIRECTORY_SEPARATOR . $path) : false;
    if ($uploadsRoot === false || $fullPath === false) {
        http_response_code(404);
        echo 'Not found.';
        exit();
    }
    if (strncmp($fullPath, $uploadsRoot, strlen($uploadsRoot)) !== 0 || !is_file($fullPath)) {
        http_response_code(404);
        echo 'Not found.';
        exit();
    }

    $basename = basename($fullPath);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($fullPath) ?: 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . $basename . '"');
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($fullPath));
    readfile($fullPath);
    exit();
} catch (Exception $e) {
    http_response_code(500);
    echo 'Server error.';
    exit();
}

