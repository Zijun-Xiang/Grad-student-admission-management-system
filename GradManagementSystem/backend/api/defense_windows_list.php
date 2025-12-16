<?php
require_once __DIR__ . '/../bootstrap.php';
require_login();

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

try {
    ensure_defense_windows_table($pdo);

    $stmt = $pdo->query(
        "SELECT id, year, start_date, end_date, created_by, created_at
         FROM defense_windows
         ORDER BY year DESC"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json([
        'status' => 'success',
        'data' => $rows,
        'current_year' => (int)date('Y'),
    ]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

