<?php
require_once __DIR__ . '/../bootstrap.php';
require_login();

include_once '../db.php';
require_once __DIR__ . '/majors_common.php';

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
    $user = current_user() ?: [];
    $uid = (string)($user['id'] ?? '');
    $majorCode = $uid !== '' ? get_user_major_code($pdo, $uid) : majors_default_code();

    $hasLevel = has_column($pdo, 'core_courses', 'level');
    $hasRequired = has_column($pdo, 'core_courses', 'is_required');
    $hasMajor = has_column($pdo, 'core_courses', 'major_code');

    if (!$hasLevel || !$hasRequired) {
        send_json([
            'status' => 'error',
            'message' => 'core_courses is missing required columns (level, is_required). Run backend/sql/07_core_courses_seed.sql first.',
        ], 500);
    }

    $where = [];
    if ($hasLevel) $where[] = "level = 'UG'";
    if ($hasRequired) $where[] = "is_required = 1";
    if ($hasMajor) $where[] = "major_code = :m";

    $sql = "SELECT course_code, course_name, credits";
    if ($hasLevel) $sql .= ", level";
    if ($hasRequired) $sql .= ", is_required";
    $sql .= " FROM core_courses";
    if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
    $sql .= " ORDER BY course_code ASC";

    $stmt = $pdo->prepare($sql);
    if ($hasMajor) $stmt->bindParam(':m', $majorCode);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $courses]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
