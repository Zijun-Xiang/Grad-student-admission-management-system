<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';

$facultyId = (string)(current_user()['id'] ?? '');
$courseCode = strtoupper(trim((string)($_GET['course_code'] ?? '')));
if ($courseCode === '') {
    send_json(['status' => 'error', 'message' => 'Missing course_code.'], 400);
}

function table_exists(PDO $pdo, string $table): bool
{
    try {
        return (bool)$pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

try {
    if (!table_exists($pdo, 'faculty_courses')) {
        send_json(['status' => 'error', 'message' => 'faculty_courses table not found. Run backend/sql/10_faculty_courses.sql first.'], 500);
    }
    if (!table_exists($pdo, 'student_registrations')) {
        send_json(['status' => 'error', 'message' => 'student_registrations table not found.'], 500);
    }

    // Verify faculty teaches the course.
    $stmtTeach = $pdo->prepare("SELECT 1 FROM faculty_courses WHERE faculty_id = :fid AND course_code = :cc LIMIT 1");
    $stmtTeach->bindParam(':fid', $facultyId);
    $stmtTeach->bindParam(':cc', $courseCode);
    $stmtTeach->execute();
    if (!$stmtTeach->fetchColumn()) {
        send_json(['status' => 'error', 'message' => 'Forbidden (not your course).'], 403);
    }

    // Ensure user_profiles exists (entry term/date).
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

    // student_details first/last name columns may not exist
    $sdHasFirstName = false;
    $sdHasLastName = false;
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM student_details")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            $f = (string)($c['Field'] ?? '');
            if ($f === 'first_name') $sdHasFirstName = true;
            if ($f === 'last_name') $sdHasLastName = true;
        }
    } catch (Exception $e) {
        $sdHasFirstName = false;
        $sdHasLastName = false;
    }
    $firstNameSel = $sdHasFirstName ? 'sd.first_name' : 'NULL';
    $lastNameSel = $sdHasLastName ? 'sd.last_name' : 'NULL';

    $stmt = $pdo->prepare(
        "SELECT u.user_id AS student_id,
                u.username,
                u.email,
                $firstNameSel AS first_name,
                $lastNameSel AS last_name,
                up.entry_term_code,
                up.entry_date,
                sr.id AS registration_id
         FROM student_registrations sr
         JOIN users u ON u.user_id = sr.student_id
         LEFT JOIN student_details sd ON sd.student_id = sr.student_id
         LEFT JOIN user_profiles up ON up.user_id = sr.student_id
         WHERE sr.course_code = :cc
           AND u.role = 'student'
         ORDER BY u.username ASC, u.user_id ASC"
    );
    $stmt->bindParam(':cc', $courseCode);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $currentTerm = grad_current_term_code();
    foreach ($rows as &$r) {
        $tc = strtoupper(trim((string)($r['entry_term_code'] ?? '')));
        $ed = (string)($r['entry_date'] ?? '');
        if ($tc === '' && $ed !== '') $tc = grad_term_code_from_date($ed) ?: '';
        $r['entry_term_code'] = $tc;
        $r['term_number'] = $tc !== '' ? grad_term_number($tc, $currentTerm) : null;
        unset($r['entry_date']);
    }

    send_json(['status' => 'success', 'course_code' => $courseCode, 'data' => $rows]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

