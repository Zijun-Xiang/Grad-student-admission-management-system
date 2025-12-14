<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['admin']);

include_once '../db.php';

try {
    // Ensure profile table exists (dev-friendly)
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS user_profiles (
                user_id BIGINT UNSIGNED NOT NULL,
                entry_date DATE NULL,
                entry_term_code VARCHAR(32) NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id),
                KEY idx_user_profiles_term (entry_term_code)
            )"
        );
    } catch (Exception $e) {
        // ignore
    }

    $hasEmail = false;
    try {
        $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            if ((string)($c['Field'] ?? '') === 'email') {
                $hasEmail = true;
                break;
            }
        }
    } catch (Exception $e) {
        $hasEmail = true;
    }

    $sql = $hasEmail
        ? "SELECT u.user_id, u.username, u.role, u.email, p.entry_date, p.entry_term_code
           FROM users u
           LEFT JOIN user_profiles p ON p.user_id = u.user_id
           ORDER BY u.role ASC, u.username ASC"
        : "SELECT u.user_id, u.username, u.role, NULL AS email, p.entry_date, p.entry_term_code
           FROM users u
           LEFT JOIN user_profiles p ON p.user_id = u.user_id
           ORDER BY u.role ASC, u.username ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $users]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
