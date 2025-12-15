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

$assignmentId = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
if ($assignmentId <= 0) {
    http_response_code(400);
    echo 'Missing assignment_id.';
    exit();
}

$role = normalize_role((string)($user['role'] ?? ''));
$userId = (string)($user['id'] ?? '');

try {
    $stmt = $pdo->prepare("SELECT id, created_by, attachment_path FROM assignments WHERE id = :aid LIMIT 1");
    $stmt->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();
    $a = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$a) {
        http_response_code(404);
        echo 'Not found.';
        exit();
    }

    $path = (string)($a['attachment_path'] ?? '');
    if ($path === '') {
        http_response_code(404);
        echo 'No attachment.';
        exit();
    }

    if ($role === 'faculty') {
        if ((string)($a['created_by'] ?? '') !== $userId) {
            http_response_code(403);
            echo 'Forbidden.';
            exit();
        }
    } elseif ($role === 'student') {
        if (!assignment_student_has_access($pdo, $userId, $assignmentId)) {
            http_response_code(403);
            echo 'Forbidden.';
            exit();
        }
    } elseif ($role !== 'admin') {
        http_response_code(403);
        echo 'Forbidden.';
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

