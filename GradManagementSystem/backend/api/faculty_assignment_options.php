<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';

if (!assignments_tables_ready($pdo)) {
    send_json(['status' => 'error', 'message' => 'Assignments tables not found. Run backend/sql/09_assignments.sql first.'], 500);
}

$facultyId = (string)(current_user()['id'] ?? '');

function table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE :t");
        $stmt->bindParam(':t', $table);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

function table_columns(PDO $pdo, string $table): array
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn ($c) => (string)($c['Field'] ?? ''), $cols);
    } catch (Exception $e) {
        return [];
    }
}

try {
    ensure_user_profiles_table_for_assignments($pdo);

    // Courses taught by this faculty (for course-target assignments).
    $courses = [];
    $coursesEnabled = false;
    try {
        $stmtT = $pdo->query("SHOW TABLES LIKE 'faculty_courses'");
        $coursesEnabled = (bool)$stmtT->fetchColumn();
    } catch (Exception $e) {
        $coursesEnabled = false;
    }

    if ($coursesEnabled) {
        $stmtC = $pdo->prepare(
            "SELECT fc.course_code,
                    cc.course_name,
                    cc.credits
             FROM faculty_courses fc
             LEFT JOIN core_courses cc ON cc.course_code = fc.course_code
             WHERE fc.faculty_id = :fid
             ORDER BY fc.course_code ASC"
        );
        $stmtC->bindParam(':fid', $facultyId);
        $stmtC->execute();
        $courses = $stmtC->fetchAll(PDO::FETCH_ASSOC);
    }

    // Students list (advisees only)
    $sdExists = table_exists($pdo, 'student_details');
    $sdCols = $sdExists ? table_columns($pdo, 'student_details') : [];
    $selectSd = '';
    $joinSd = '';
    $whereAdv = '';
    if ($sdExists) {
        $joinSd = "LEFT JOIN student_details sd ON sd.student_id = u.user_id";
        if (in_array('first_name', $sdCols, true)) $selectSd .= ", sd.first_name";
        if (in_array('last_name', $sdCols, true)) $selectSd .= ", sd.last_name";
        $whereAdv = "AND sd.major_professor_id = " . $pdo->quote($facultyId) . " AND sd.mp_status <> 'none'";
    }

    $stmtS = $pdo->query(
        "SELECT u.user_id AS student_id,
                u.username,
                u.email,
                up.entry_term_code AS entry_term_code,
                up.entry_date
                $selectSd
         FROM users u
         LEFT JOIN user_profiles up ON up.user_id = u.user_id
         $joinSd
         WHERE u.role = 'student'
         $whereAdv
         ORDER BY u.username ASC, u.user_id ASC"
    );
    $students = $stmtS->fetchAll(PDO::FETCH_ASSOC);

    foreach ($students as &$s) {
        $tc = strtoupper(trim((string)($s['entry_term_code'] ?? '')));
        $ed = (string)($s['entry_date'] ?? '');
        if ($tc === '' && $ed !== '') $tc = grad_term_code_from_date($ed) ?: '';
        $s['entry_term_code'] = $tc;
        unset($s['entry_date']);
    }

    send_json([
        'status' => 'success',
        'courses_enabled' => $coursesEnabled,
        'courses' => $courses,
        'students' => $students,
    ]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
