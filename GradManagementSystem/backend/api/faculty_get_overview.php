<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';

$facultyId = (string)(current_user()['id'] ?? '');

function faculty_courses_table_ready(PDO $pdo): bool
{
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'faculty_courses'");
        return (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

try {
    // Ensure user_profiles exists (some DB setups rely on runtime creation in other endpoints).
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

    $hasFacultyCourses = faculty_courses_table_ready($pdo);
    $taughtCourses = [];
    if ($hasFacultyCourses) {
        $stmtC = $pdo->prepare(
            "SELECT fc.course_code,
                    cc.course_name,
                    cc.credits,
                    cc.level,
                    cc.is_required,
                    fc.created_at
             FROM faculty_courses fc
             LEFT JOIN core_courses cc ON cc.course_code = fc.course_code
             WHERE fc.faculty_id = :fid
             ORDER BY fc.created_at DESC, fc.course_code ASC"
        );
        $stmtC->bindParam(':fid', $facultyId);
        $stmtC->execute();
        $taughtCourses = $stmtC->fetchAll(PDO::FETCH_ASSOC);
    }

    // Advisees (include pending/approved/accepted; exclude "none").
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

    $stmtA = $pdo->prepare(
        "SELECT sd.student_id,
                su.username AS student_username,
                su.email AS student_email,
                sd.mp_status,
                $firstNameSel AS first_name,
                $lastNameSel AS last_name,
                up.entry_term_code AS entry_term_code,
                up.entry_date
         FROM student_details sd
         LEFT JOIN users su ON su.user_id = sd.student_id
         LEFT JOIN user_profiles up ON up.user_id = sd.student_id
         WHERE sd.major_professor_id = :fid
           AND sd.mp_status <> 'none'
         ORDER BY sd.student_id ASC"
    );
    $stmtA->bindParam(':fid', $facultyId);
    $stmtA->execute();
    $advisees = $stmtA->fetchAll(PDO::FETCH_ASSOC);
    foreach ($advisees as &$a) {
        $tc = strtoupper(trim((string)($a['entry_term_code'] ?? '')));
        $ed = (string)($a['entry_date'] ?? '');
        if ($tc === '' && $ed !== '') {
            $tc = grad_term_code_from_date($ed) ?: '';
        }
        $a['entry_term_code'] = $tc;
    }

    usort($advisees, function ($a, $b) {
        $af = strtoupper(substr(trim((string)($a['first_name'] ?? '')), 0, 1));
        $bf = strtoupper(substr(trim((string)($b['first_name'] ?? '')), 0, 1));

        if ($af === '') $af = strtoupper(substr(trim((string)($a['student_username'] ?? '')), 0, 1));
        if ($bf === '') $bf = strtoupper(substr(trim((string)($b['student_username'] ?? '')), 0, 1));

        $cmp = strcmp($af, $bf);
        if ($cmp !== 0) return $cmp;

        $al = strtoupper(trim((string)($a['last_name'] ?? '')));
        $bl = strtoupper(trim((string)($b['last_name'] ?? '')));
        $cmp = strcmp($al, $bl);
        if ($cmp !== 0) return $cmp;

        $au = strtoupper(trim((string)($a['student_username'] ?? '')));
        $bu = strtoupper(trim((string)($b['student_username'] ?? '')));
        $cmp = strcmp($au, $bu);
        if ($cmp !== 0) return $cmp;

        return (int)($a['student_id'] ?? 0) <=> (int)($b['student_id'] ?? 0);
    });

    send_json([
        'status' => 'success',
        'faculty_courses_enabled' => $hasFacultyCourses,
        'taught_courses' => $taughtCourses,
        'advisees' => $advisees,
    ]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
