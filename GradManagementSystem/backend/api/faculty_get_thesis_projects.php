<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

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

    $facultyId = (string)(current_user()['id'] ?? '');

    $sdCols = table_columns($pdo, 'student_details');
    $sdHasFirstName = pick_first_existing_column($sdCols, ['first_name']) !== null;
    $sdHasLastName = pick_first_existing_column($sdCols, ['last_name']) !== null;
    $firstNameSel = $sdHasFirstName ? 'sd.first_name' : 'NULL';
    $lastNameSel = $sdHasLastName ? 'sd.last_name' : 'NULL';

    $latestDocSubquery =
        "SELECT d1.*
         FROM documents d1
         JOIN (
             SELECT student_id, MAX(doc_id) AS max_id
             FROM documents
             WHERE doc_type = 'thesis_project'
             GROUP BY student_id
         ) latest ON latest.student_id = d1.student_id AND latest.max_id = d1.doc_id";

    $latestSubquery =
        "SELECT tp1.*
         FROM thesis_projects tp1
         JOIN (
             SELECT student_id, MAX(id) AS max_id
             FROM thesis_projects
             GROUP BY student_id
         ) latest ON latest.student_id = tp1.student_id AND latest.max_id = tp1.id";

    $stmt = $pdo->prepare(
        "SELECT sd.student_id,
                su.username AS student_username,
                su.email AS student_email,
                $firstNameSel AS first_name,
                $lastNameSel AS last_name,
                sd.mp_status,
                up.entry_term_code,
                up.entry_date,
                tp.type,
                tp.title,
                tp.submission_date,
                tp.defense_date,
                tp.created_at,
                td.doc_id AS thesis_doc_id,
                td.file_path AS thesis_file_path,
                td.status AS thesis_doc_status
         FROM student_details sd
         LEFT JOIN users su ON su.user_id = sd.student_id
         LEFT JOIN user_profiles up ON up.user_id = sd.student_id
         LEFT JOIN ($latestSubquery) tp ON tp.student_id = sd.student_id
         LEFT JOIN ($latestDocSubquery) td ON td.student_id = sd.student_id
         WHERE sd.major_professor_id = :fid
           AND sd.mp_status <> 'none'
         ORDER BY sd.student_id ASC"
    );
    $stmt->bindParam(':fid', $facultyId);
    $stmt->execute();
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
