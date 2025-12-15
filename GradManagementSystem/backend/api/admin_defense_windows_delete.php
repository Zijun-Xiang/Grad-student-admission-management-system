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
if ($year < 2000 || $year > 2100) {
    send_json(['status' => 'error', 'message' => 'Invalid year.'], 400);
}

try {
    ensure_defense_windows_table($pdo);

    $stmt = $pdo->prepare("DELETE FROM defense_windows WHERE year = :y");
    $stmt->bindParam(':y', $year, PDO::PARAM_INT);
    $stmt->execute();

    send_json(['status' => 'success', 'message' => 'Defense window deleted.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

