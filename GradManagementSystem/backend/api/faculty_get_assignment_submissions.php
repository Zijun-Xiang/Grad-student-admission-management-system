<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';

if (!assignments_tables_ready($pdo)) {
    send_json(['status' => 'error', 'message' => 'Assignments tables not found. Run backend/sql/09_assignments.sql first.'], 500);
}

$facultyId = (string)(current_user()['id'] ?? '');
$assignmentId = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
if ($assignmentId <= 0) {
    send_json(['status' => 'error', 'message' => 'Missing assignment_id.'], 400);
}

try {
    ensure_user_profiles_table_for_assignments($pdo);
    ensure_assignment_grading_columns($pdo);
    $gradingEnabled = assignment_grading_enabled($pdo);

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

    $stmtA = $pdo->prepare("SELECT id, title, created_by, due_at, attachment_path, created_at FROM assignments WHERE id = :aid LIMIT 1");
    $stmtA->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
    $stmtA->execute();
    $assignment = $stmtA->fetch(PDO::FETCH_ASSOC);
    if (!$assignment) {
        send_json(['status' => 'error', 'message' => 'Assignment not found.'], 404);
    }
    if ((string)($assignment['created_by'] ?? '') !== $facultyId) {
        send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);
    }

    $stmtT = $pdo->prepare("SELECT target_type, target_value FROM assignment_targets WHERE assignment_id = :aid");
    $stmtT->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
    $stmtT->execute();
    $targets = $stmtT->fetchAll(PDO::FETCH_ASSOC);

    $studentWhere = "";
    $params = [];
    $hasAll = false;
    $cohort = null; // legacy
    $courseCode = null;
    $studentIds = [];
    foreach ($targets as $t) {
        $type = (string)($t['target_type'] ?? '');
        $val = (string)($t['target_value'] ?? '');
        if ($type === 'all') $hasAll = true;
        if ($type === 'cohort' && $val !== '') $cohort = $val;
        if ($type === 'course' && $val !== '') $courseCode = strtoupper($val);
        if ($type === 'student' && $val !== '') $studentIds[] = $val;
    }
    $studentIds = array_values(array_unique($studentIds));

    if ($hasAll) {
        $studentWhere = "u.role = 'student'";
    } elseif ($courseCode !== null) {
        // Ensure student_registrations exists.
        $hasRegs = false;
        try {
            $hasRegs = (bool)$pdo->query("SHOW TABLES LIKE 'student_registrations'")->fetchColumn();
        } catch (Exception $e) {
            $hasRegs = false;
        }
        if (!$hasRegs) {
            send_json(['status' => 'error', 'message' => 'student_registrations table not found.'], 500);
        }
        $studentWhere = "u.role = 'student' AND EXISTS (
            SELECT 1 FROM student_registrations sr
            WHERE sr.student_id = u.user_id AND sr.course_code = :course_code
            LIMIT 1
        )";
        $params[':course_code'] = $courseCode;
    } elseif ($cohort !== null) {
        $studentWhere = "u.role = 'student' AND up.entry_term_code = :cohort";
        $params[':cohort'] = $cohort;
    } else {
        if (empty($studentIds)) {
            send_json(['status' => 'success', 'assignment' => $assignment, 'targets' => [], 'data' => []]);
        }
        // Use named placeholders only (PDO MySQL may error on mixed named + positional parameters).
        $placeholders = [];
        foreach ($studentIds as $i => $sid) {
            $ph = ":sid{$i}";
            $placeholders[] = $ph;
            $params[$ph] = $sid;
        }
        $studentWhere = "u.role = 'student' AND u.user_id IN (" . implode(',', $placeholders) . ")";
    }

    $gradeSelect = $gradingEnabled ? "s.grade,\n                   s.graded_at," : "NULL AS grade,\n                   NULL AS graded_at,";

    $sql = "SELECT u.user_id AS student_id,
                   u.username AS student_username,
                   u.email AS student_email,
                   $firstNameSel AS first_name,
                   $lastNameSel AS last_name,
                   up.entry_term_code AS entry_term_code,
                   up.entry_date,
                   s.id AS submission_id,
                   s.file_path,
                   s.submitted_at,
                   $gradeSelect
                   (SELECT COUNT(*) FROM assignment_submission_comments c WHERE c.submission_id = s.id) AS comments_count
            FROM users u
            LEFT JOIN student_details sd ON sd.student_id = u.user_id
            LEFT JOIN user_profiles up ON up.user_id = u.user_id
            LEFT JOIN assignment_submissions s ON s.assignment_id = :aid AND s.student_id = u.user_id
            WHERE $studentWhere
            ORDER BY u.username ASC, u.user_id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $tc = strtoupper(trim((string)($r['entry_term_code'] ?? '')));
        $ed = (string)($r['entry_date'] ?? '');
        if ($tc === '' && $ed !== '') $tc = grad_term_code_from_date($ed) ?: '';
        $r['entry_term_code'] = $tc;
        unset($r['entry_date']);
    }

    send_json(['status' => 'success', 'assignment' => $assignment, 'targets' => $targets, 'data' => $rows]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
