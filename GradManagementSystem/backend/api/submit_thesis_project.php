<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');

include_once '../db.php';

$user = require_login(['student']);

function ensure_thesis_projects_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS thesis_projects (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            student_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(16) NOT NULL DEFAULT 'thesis',
            title VARCHAR(255) NULL,
            submission_date DATE NOT NULL,
            defense_date DATE NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_thesis_projects_student (student_id)
        )"
    );
}

function ensure_defense_windows_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS defense_windows (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            year INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_defense_windows_year (year),
            KEY idx_defense_windows_start (start_date),
            KEY idx_defense_windows_end (end_date)
        )"
    );
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
    $sDate = $submissionDate->format('Y-m-d');
    $dDate = $defenseDate->format('Y-m-d');

    ensure_thesis_projects_table($pdo);

    // Validate defense date within admin-published window for that year.
    ensure_defense_windows_table($pdo);
    $defenseYear = (int)$defenseDate->format('Y');
    $stmtW = $pdo->prepare(
        "SELECT start_date, end_date
         FROM defense_windows
         WHERE year = :y
         LIMIT 1"
    );
    $stmtW->bindParam(':y', $defenseYear, PDO::PARAM_INT);
    $stmtW->execute();
    $window = $stmtW->fetch(PDO::FETCH_ASSOC);
    if (!$window) {
        send_json([
            'status' => 'error',
            'message' => "No defense window published for {$defenseYear}. Please contact admin.",
        ], 400);
    }
    $start = (string)($window['start_date'] ?? '');
    $end = (string)($window['end_date'] ?? '');
    if ($start !== '' && $end !== '') {
        if ($dDate < $start || $dDate > $end) {
            send_json([
                'status' => 'error',
                'message' => "Defense date must be within the published window: {$start} to {$end}.",
            ], 400);
        }
    }

    // Update existing record (if any) so students can modify their dates.
    $stmtFind = $pdo->prepare(
        "SELECT id FROM thesis_projects WHERE student_id = :sid ORDER BY id DESC LIMIT 1"
    );
    $stmtFind->bindParam(':sid', $studentId);
    $stmtFind->execute();
    $existingId = $stmtFind->fetchColumn();

    if ($existingId) {
        $stmt = $pdo->prepare(
            "UPDATE thesis_projects
             SET type = :type,
                 title = :title,
                 submission_date = :sdate,
                 defense_date = :ddate
             WHERE id = :id AND student_id = :sid"
        );
        $stmt->bindParam(':id', $existingId, PDO::PARAM_INT);
        $stmt->bindParam(':sid', $studentId);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO thesis_projects (student_id, type, title, submission_date, defense_date, created_at)
             VALUES (:sid, :type, :title, :sdate, :ddate, NOW())"
        );
        $stmt->bindParam(':sid', $studentId);
    }
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':sdate', $sDate);
    $stmt->bindParam(':ddate', $dDate);
    $stmt->execute();

    send_json(['status' => 'success', 'message' => 'Thesis/Project timeline saved.']);
} catch (Exception $e) {
    send_json([
        'status' => 'error',
        'message' => 'Insert failed. Did you create table thesis_projects? ' . $e->getMessage(),
    ], 500);
}
