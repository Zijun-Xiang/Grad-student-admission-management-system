<?php
require_once __DIR__ . '/../bootstrap.php';

include_once '../db.php';
require_once __DIR__ . '/majors_common.php';

$user = require_login();
$userId = (string)($user['id'] ?? '');
$role = normalize_role((string)($user['role'] ?? ''));

if ($userId === '') {
    send_json(['status' => 'error', 'message' => 'Invalid session.'], 401);
}

function has_column(PDO $pdo, string $table, string $col): bool
{
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            if ((string)($c['Field'] ?? '') === $col) return true;
        }
    } catch (Exception $e) {
        return false;
    }
    return false;
}

try {
    // Ensure majors schema exists (dev-friendly); avoid DDL in transaction (this endpoint doesn't start one).
    try {
        ensure_majors_schema($pdo);
    } catch (Exception $e) {
        // ignore
    }

    $hasEmail = has_column($pdo, 'users', 'email');
    $sdHasFirst = $role === 'student' && has_column($pdo, 'student_details', 'first_name');
    $sdHasLast = $role === 'student' && has_column($pdo, 'student_details', 'last_name');

    $emailSel = $hasEmail ? 'u.email' : 'NULL AS email';
    $firstSel = $sdHasFirst ? 'sd.first_name' : 'NULL AS first_name';
    $lastSel = $sdHasLast ? 'sd.last_name' : 'NULL AS last_name';

    $stmt = $pdo->prepare(
        "SELECT u.user_id,
                u.username,
                u.role,
                $emailSel,
                COALESCE(up.major_code, '" . majors_default_code() . "') AS major_code,
                m.major_name,
                $firstSel,
                $lastSel
         FROM users u
         LEFT JOIN user_profiles up ON up.user_id = u.user_id
         LEFT JOIN majors m ON m.major_code = COALESCE(up.major_code, '" . majors_default_code() . "')
         LEFT JOIN student_details sd ON sd.student_id = u.user_id
         WHERE u.user_id = :uid
         LIMIT 1"
    );
    $stmt->bindParam(':uid', $userId);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        send_json(['status' => 'error', 'message' => 'User not found.'], 404);
    }

    // Normalize major if missing
    $mc = normalize_major_code((string)($row['major_code'] ?? ''));
    if ($mc === '') $mc = majors_default_code();
    $row['major_code'] = $mc;
    if (!isset($row['major_name']) || $row['major_name'] === null || $row['major_name'] === '') {
        $row['major_name'] = $mc === 'CS' ? 'Computer Science' : null;
    }

    send_json(['status' => 'success', 'data' => $row]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

