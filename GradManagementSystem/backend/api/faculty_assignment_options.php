<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';

if (!assignments_tables_ready($pdo)) {
    send_json(['status' => 'error', 'message' => 'Assignments tables not found. Run backend/sql/09_assignments.sql first.'], 500);
}

$facultyId = (string)(current_user()['id'] ?? '');

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

    // Students list
    $stmtS = $pdo->query(
        "SELECT u.user_id AS student_id,
                u.username,
                u.email,
                up.entry_term_code AS entry_term_code,
                up.entry_date
         FROM users u
         LEFT JOIN user_profiles up ON up.user_id = u.user_id
         WHERE u.role = 'student'
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
