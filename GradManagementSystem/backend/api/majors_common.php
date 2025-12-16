<?php
declare(strict_types=1);

/**
 * Major / program helpers.
 *
 * Default major_code for legacy users/courses is "CS" (Computer Science).
 */

function majors_default_code(): string
{
    return 'CS';
}

function majors_table_exists(PDO $pdo): bool
{
    try {
        return (bool)$pdo->query("SHOW TABLES LIKE 'majors'")->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

function majors_has_column(PDO $pdo, string $table, string $col): bool
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

function ensure_user_profiles_table_with_major(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS user_profiles (
            user_id BIGINT UNSIGNED NOT NULL,
            entry_date DATE NULL,
            entry_term_code VARCHAR(32) NULL,
            major_code VARCHAR(16) NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id),
            KEY idx_user_profiles_term (entry_term_code),
            KEY idx_user_profiles_major (major_code)
        )"
    );

    // Existing DB might have user_profiles without major_code.
    if (!majors_has_column($pdo, 'user_profiles', 'major_code')) {
        $pdo->exec("ALTER TABLE user_profiles ADD COLUMN major_code VARCHAR(16) NULL AFTER entry_term_code");
        $pdo->exec("ALTER TABLE user_profiles ADD INDEX idx_user_profiles_major (major_code)");
    }
}

function ensure_core_courses_major(PDO $pdo): void
{
    // core_courses should exist from prior setup, but guard anyway.
    try {
        $hasCore = (bool)$pdo->query("SHOW TABLES LIKE 'core_courses'")->fetchColumn();
    } catch (Exception $e) {
        $hasCore = false;
    }
    if (!$hasCore) return;

    if (!majors_has_column($pdo, 'core_courses', 'major_code')) {
        $pdo->exec("ALTER TABLE core_courses ADD COLUMN major_code VARCHAR(16) NULL AFTER course_code");
        $pdo->exec("ALTER TABLE core_courses ADD INDEX idx_core_courses_major (major_code)");
        // Best-effort legacy default.
        $pdo->exec("UPDATE core_courses SET major_code = '" . majors_default_code() . "' WHERE major_code IS NULL OR major_code = ''");
    }
}

function ensure_majors_schema(PDO $pdo): void
{
    // user_profiles may already be created elsewhere; ensure it includes major_code.
    ensure_user_profiles_table_with_major($pdo);

    // majors lookup table
    if (!majors_table_exists($pdo)) {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS majors (
                major_code VARCHAR(16) NOT NULL,
                major_name VARCHAR(128) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (major_code),
                KEY idx_majors_active (is_active)
            )"
        );
    }

    // Seed a few majors (idempotent).
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO majors (major_code, major_name, is_active) VALUES
            ('CS', 'Computer Science', 1),
            ('SE', 'Software Engineering', 1),
            ('DS', 'Data Science', 1)
        ");
        $stmt->execute();
    } catch (Exception $e) {
        // ignore
    }

    // Ensure core_courses has a major_code so we can filter courses per major.
    ensure_core_courses_major($pdo);

    // Best-effort: backfill existing user_profiles with default major.
    try {
        $pdo->exec(
            "UPDATE user_profiles
             SET major_code = '" . majors_default_code() . "'
             WHERE major_code IS NULL OR major_code = ''"
        );
    } catch (Exception $e) {
        // ignore
    }
}

function normalize_major_code(?string $majorCode): string
{
    $c = strtoupper(trim((string)$majorCode));
    $c = preg_replace('/[^A-Z0-9_]/', '', $c);
    return is_string($c) ? $c : '';
}

function get_user_major_code(PDO $pdo, string $userId): string
{
    try {
        // Avoid DDL inside a transaction (MySQL DDL implicitly commits).
        if (!$pdo->inTransaction()) {
            ensure_majors_schema($pdo);
        }
    } catch (Exception $e) {
        // ignore, fallback below
    }

    try {
        $stmt = $pdo->prepare("SELECT major_code FROM user_profiles WHERE user_id = :uid LIMIT 1");
        $stmt->bindParam(':uid', $userId);
        $stmt->execute();
        $mc = $stmt->fetchColumn();
        $mc = $mc === false || $mc === null ? '' : (string)$mc;
        $mc = normalize_major_code($mc);
        return $mc !== '' ? $mc : majors_default_code();
    } catch (Exception $e) {
        return majors_default_code();
    }
}

function major_code_exists(PDO $pdo, string $majorCode): bool
{
    $majorCode = normalize_major_code($majorCode);
    if ($majorCode === '') return false;
    try {
        // Avoid DDL inside a transaction (MySQL DDL implicitly commits).
        if (!$pdo->inTransaction()) {
            ensure_majors_schema($pdo);
        }
        $stmt = $pdo->prepare("SELECT 1 FROM majors WHERE major_code = :c AND is_active = 1 LIMIT 1");
        $stmt->bindParam(':c', $majorCode);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

function list_active_majors(PDO $pdo): array
{
    try {
        // Avoid DDL inside a transaction (MySQL DDL implicitly commits).
        if (!$pdo->inTransaction()) {
            ensure_majors_schema($pdo);
        }
        $stmt = $pdo->query("SELECT major_code, major_name FROM majors WHERE is_active = 1 ORDER BY major_name ASC, major_code ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    } catch (Exception $e) {
        return [
            ['major_code' => majors_default_code(), 'major_name' => 'Computer Science'],
        ];
    }
}
