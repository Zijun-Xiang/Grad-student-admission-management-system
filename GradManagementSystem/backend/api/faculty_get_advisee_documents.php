<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';

$facultyId = (string)(current_user()['id'] ?? '');

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
    // Ensure user_profiles exists (some DB setups rely on runtime creation in other endpoints).
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

    $docsCols = table_columns($pdo, 'documents');
    $sdCols = table_columns($pdo, 'student_details');

    $docsDateCol = pick_first_existing_column($docsCols, ['upload_date', 'created_at']) ?: 'doc_id';
    $sdHasFirstName = pick_first_existing_column($sdCols, ['first_name']) !== null;
    $sdHasLastName = pick_first_existing_column($sdCols, ['last_name']) !== null;

    $firstNameSel = $sdHasFirstName ? 'sd.first_name' : 'NULL';
    $lastNameSel = $sdHasLastName ? 'sd.last_name' : 'NULL';

    // Only show documents for students whose major professor is this faculty AND approved.
    $stmt = $pdo->prepare(
        "SELECT d.doc_id,
                d.student_id,
                su.username AS student_username,
                su.email AS student_email,
                $firstNameSel AS first_name,
                $lastNameSel AS last_name,
                up.entry_term_code,
                up.entry_date,
                d.doc_type,
                d.file_path,
                d.status,
                d.`$docsDateCol` AS upload_date
         FROM documents d
         JOIN student_details sd ON sd.student_id = d.student_id
         LEFT JOIN users su ON su.user_id = d.student_id
         LEFT JOIN user_profiles up ON up.user_id = d.student_id
         WHERE sd.major_professor_id = :fid
           AND (sd.mp_status = 'approved' OR sd.mp_status = 'accepted')
         ORDER BY d.`$docsDateCol` DESC, d.doc_id DESC"
    );
    $stmt->bindParam(':fid', $facultyId);
    $stmt->execute();
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($docs as &$d) {
        $tc = strtoupper(trim((string)($d['entry_term_code'] ?? '')));
        $ed = (string)($d['entry_date'] ?? '');
        if ($tc === '' && $ed !== '') {
            $tc = grad_term_code_from_date($ed) ?: '';
        }
        $d['entry_term_code'] = $tc;
    }

    send_json(['status' => 'success', 'data' => $docs]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
