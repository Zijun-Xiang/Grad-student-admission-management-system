<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['admin']);

include_once '../db.php';
require_once __DIR__ . '/majors_common.php';

try {
    // Ensure profile table exists (dev-friendly)
    try {
        ensure_majors_schema($pdo);
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
        ? "SELECT u.user_id, u.username, u.role, u.email,
                  p.entry_date, p.entry_term_code,
                  COALESCE(p.major_code, '" . majors_default_code() . "') AS major_code,
                  m.major_name
           FROM users u
           LEFT JOIN user_profiles p ON p.user_id = u.user_id
           LEFT JOIN majors m ON m.major_code = COALESCE(p.major_code, '" . majors_default_code() . "')
           ORDER BY u.role ASC, u.username ASC"
        : "SELECT u.user_id, u.username, u.role, NULL AS email,
                  p.entry_date, p.entry_term_code,
                  COALESCE(p.major_code, '" . majors_default_code() . "') AS major_code,
                  m.major_name
           FROM users u
           LEFT JOIN user_profiles p ON p.user_id = u.user_id
           LEFT JOIN majors m ON m.major_code = COALESCE(p.major_code, '" . majors_default_code() . "')
           ORDER BY u.role ASC, u.username ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $users]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
