<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['admin']);

include_once '../db.php';

function table_columns(PDO $pdo, string $table): array
{
    try {
        return $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function pick_first_existing_column(array $cols, array $candidates): ?string
{
    $available = [];
    foreach ($cols as $c) {
        $available[] = (string)($c['Field'] ?? '');
    }
    foreach ($candidates as $cand) {
        if (in_array($cand, $available, true)) return $cand;
    }
    return null;
}

try {
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

    $latestSubquery =
        "SELECT tp1.*
         FROM thesis_projects tp1
         JOIN (
             SELECT student_id, MAX(id) AS max_id
             FROM thesis_projects
             GROUP BY student_id
         ) latest ON latest.student_id = tp1.student_id AND latest.max_id = tp1.id";

    // Ensure user_profiles exists (term/entry data).
    try {
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
    } catch (Exception $e) {
        // ignore
    }

    $sdCols = table_columns($pdo, 'student_details');
    $sdHasFirstName = pick_first_existing_column($sdCols, ['first_name']) !== null;
    $sdHasLastName = pick_first_existing_column($sdCols, ['last_name']) !== null;
    $firstNameSel = $sdHasFirstName ? 'sd.first_name' : 'NULL';
    $lastNameSel = $sdHasLastName ? 'sd.last_name' : 'NULL';

    $stmt = $pdo->query(
        "SELECT u.user_id AS student_id,
                u.username,
                u.email,
                $firstNameSel AS first_name,
                $lastNameSel AS last_name,
                up.entry_term_code,
                up.entry_date,
                tp.type,
                tp.title,
                tp.submission_date,
                tp.defense_date,
                tp.created_at
         FROM users u
         LEFT JOIN student_details sd ON sd.student_id = u.user_id
         LEFT JOIN user_profiles up ON up.user_id = u.user_id
         LEFT JOIN ($latestSubquery) tp ON tp.student_id = u.user_id
         WHERE u.role = 'student'
         ORDER BY u.username ASC, u.user_id ASC"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $currentTerm = grad_current_term_code();
    foreach ($rows as &$r) {
        $tc = strtoupper(trim((string)($r['entry_term_code'] ?? '')));
        $ed = (string)($r['entry_date'] ?? '');
        if ($tc === '' && $ed !== '') {
            $tc = grad_term_code_from_date($ed) ?: '';
        }
        $r['entry_term_code'] = $tc;
        $r['term_number'] = $tc !== '' ? grad_term_number($tc, $currentTerm) : null;
        unset($r['entry_date']);
    }

    send_json(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
