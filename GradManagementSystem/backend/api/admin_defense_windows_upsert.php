<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['admin']);
require_method('POST');

include_once '../db.php';

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
$year = (int)($data['year'] ?? 0);
$startRaw = trim((string)($data['start_date'] ?? ''));
$endRaw = trim((string)($data['end_date'] ?? ''));

if ($year < 2000 || $year > 2100) {
    send_json(['status' => 'error', 'message' => 'Invalid year.'], 400);
}
if ($startRaw === '' || $endRaw === '') {
    send_json(['status' => 'error', 'message' => 'Missing start_date or end_date.'], 400);
}

try {
    $start = new DateTimeImmutable($startRaw);
    $end = new DateTimeImmutable($endRaw);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => 'Invalid date format. Use YYYY-MM-DD.'], 400);
}

if ($start > $end) {
    send_json(['status' => 'error', 'message' => 'start_date must be <= end_date.'], 400);
}
if ((int)$start->format('Y') !== $year || (int)$end->format('Y') !== $year) {
    send_json(['status' => 'error', 'message' => 'start_date and end_date must be in the same year as "year".'], 400);
}

try {
    ensure_defense_windows_table($pdo);

    $adminId = (string)(current_user()['id'] ?? '');
    $startStr = $start->format('Y-m-d');
    $endStr = $end->format('Y-m-d');

    $stmt = $pdo->prepare(
        "INSERT INTO defense_windows (year, start_date, end_date, created_by, created_at)
         VALUES (:y, :s, :e, :by, NOW())
         ON DUPLICATE KEY UPDATE
            start_date = VALUES(start_date),
            end_date = VALUES(end_date),
            created_by = VALUES(created_by)"
    );
    $stmt->bindParam(':y', $year, PDO::PARAM_INT);
    $stmt->bindParam(':s', $startStr);
    $stmt->bindParam(':e', $endStr);
    $stmt->bindParam(':by', $adminId);
    $stmt->execute();

    send_json(['status' => 'success', 'message' => 'Defense window saved.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

