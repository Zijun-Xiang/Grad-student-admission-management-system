<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');

include_once '../db.php';

$user = require_login();
if (($user['role'] ?? '') === 'faculty') {
    send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);
}

$data = get_json_input();
$type = (string)($data['type'] ?? 'thesis'); // thesis | project
$title = (string)($data['title'] ?? '');
$submissionDateRaw = (string)($data['submission_date'] ?? '');
$defenseDateRaw = (string)($data['defense_date'] ?? '');

if ($submissionDateRaw === '' || $defenseDateRaw === '') {
    send_json(['status' => 'error', 'message' => 'Missing submission_date or defense_date.'], 400);
}

try {
    $submissionDate = new DateTimeImmutable($submissionDateRaw);
    $defenseDate = new DateTimeImmutable($defenseDateRaw);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => 'Invalid date format. Use YYYY-MM-DD.'], 400);
}

// Rule: submission must be at least 1 month before defense.
$minDefenseDate = $submissionDate->modify('+1 month');
if ($defenseDate < $minDefenseDate) {
    send_json([
        'status' => 'error',
        'message' => 'Defense date must be at least 1 month after submission date.',
    ], 400);
}

$studentId = (string)($user['id'] ?? '');

try {
    $stmt = $pdo->prepare(
        'INSERT INTO thesis_projects (student_id, type, title, submission_date, defense_date, created_at)
         VALUES (:sid, :type, :title, :sdate, :ddate, NOW())'
    );
    $stmt->bindParam(':sid', $studentId);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':title', $title);
    $sDate = $submissionDate->format('Y-m-d');
    $dDate = $defenseDate->format('Y-m-d');
    $stmt->bindParam(':sdate', $sDate);
    $stmt->bindParam(':ddate', $dDate);
    $stmt->execute();

    send_json(['status' => 'success', 'message' => 'Submission recorded.']);
} catch (Exception $e) {
    send_json([
        'status' => 'error',
        'message' => 'Insert failed. Did you create table thesis_projects? ' . $e->getMessage(),
    ], 500);
}

