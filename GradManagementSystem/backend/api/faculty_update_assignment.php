<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';

if (!assignments_tables_ready($pdo)) {
    send_json(['status' => 'error', 'message' => 'Assignments tables not found. Run backend/sql/09_assignments.sql first.'], 500);
}

$facultyId = (string)(current_user()['id'] ?? '');
$data = get_json_input();

$assignmentId = (int)($data['assignment_id'] ?? 0);
$title = isset($data['title']) ? trim((string)$data['title']) : '';
$description = isset($data['description']) ? trim((string)$data['description']) : '';
$dueAt = isset($data['due_at']) ? trim((string)$data['due_at']) : ''; // YYYY-MM-DDTHH:mm or empty

$targetMode = trim((string)($data['target_mode'] ?? '')); // all | course | students | cohort(legacy) | '' (no change)
$courseCode = strtoupper(trim((string)($data['course_code'] ?? '')));
$cohort = trim((string)($data['cohort_term_code'] ?? '')); // legacy
$studentIds = $data['student_ids'] ?? null;

if ($assignmentId <= 0) {
    send_json(['status' => 'error', 'message' => 'Missing assignment_id.'], 400);
}
if ($title === '') {
    send_json(['status' => 'error', 'message' => 'Missing title.'], 400);
}

$dueAtSql = null;
if ($dueAt !== '') {
    $ts = strtotime($dueAt);
    if ($ts === false) {
        send_json(['status' => 'error', 'message' => 'Invalid due_at.'], 400);
    }
    $dueAtSql = date('Y-m-d H:i:s', $ts);
}

$updateTargets = ($targetMode !== '');
if ($updateTargets) {
    if (!in_array($targetMode, ['all', 'course', 'students', 'cohort'], true)) {
        send_json(['status' => 'error', 'message' => 'Invalid target_mode.'], 400);
    }
    if ($targetMode === 'course' && $courseCode === '') {
        send_json(['status' => 'error', 'message' => 'Missing course_code.'], 400);
    }
    if ($targetMode === 'cohort' && $cohort === '') {
        send_json(['status' => 'error', 'message' => 'Missing cohort_term_code.'], 400);
    }
    if ($targetMode === 'students') {
        if (!is_array($studentIds) || empty($studentIds)) {
            send_json(['status' => 'error', 'message' => 'Select at least one student.'], 400);
        }
        $studentIds = array_values(array_unique(array_map(fn($v) => trim((string)$v), $studentIds)));
        $studentIds = array_values(array_filter($studentIds, fn($v) => $v !== ''));
        if (empty($studentIds)) {
            send_json(['status' => 'error', 'message' => 'Select at least one student.'], 400);
        }
    }
}

try {
    $stmtA = $pdo->prepare("SELECT id FROM assignments WHERE id = :aid AND created_by = :fid LIMIT 1");
    $stmtA->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
    $stmtA->bindParam(':fid', $facultyId);
    $stmtA->execute();
    if (!$stmtA->fetchColumn()) {
        send_json(['status' => 'error', 'message' => 'Forbidden or assignment not found.'], 403);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "UPDATE assignments
         SET title = :t, description = :d, due_at = :due
         WHERE id = :aid AND created_by = :fid"
    );
    $stmt->bindParam(':t', $title);
    $stmt->bindParam(':d', $description);
    $stmt->bindValue(':due', $dueAtSql, $dueAtSql === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
    $stmt->bindParam(':fid', $facultyId);
    $stmt->execute();

    if ($updateTargets) {
        if ($targetMode === 'course') {
            // Ensure faculty teaches the course.
            $has = $pdo->query("SHOW TABLES LIKE 'faculty_courses'")->fetchColumn();
            if (!$has) {
                send_json(['status' => 'error', 'message' => 'faculty_courses table not found. Add teaching courses first.'], 500);
            }
            $stmtChk = $pdo->prepare("SELECT 1 FROM faculty_courses WHERE faculty_id = :fid AND course_code = :cc LIMIT 1");
            $stmtChk->bindParam(':fid', $facultyId);
            $stmtChk->bindParam(':cc', $courseCode);
            $stmtChk->execute();
            if (!$stmtChk->fetchColumn()) {
                send_json(['status' => 'error', 'message' => 'You can only publish to your own teaching courses.'], 403);
            }
        }

        $del = $pdo->prepare("DELETE FROM assignment_targets WHERE assignment_id = :aid");
        $del->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
        $del->execute();

        if ($targetMode === 'all') {
            $t = $pdo->prepare("INSERT INTO assignment_targets (assignment_id, target_type, target_value) VALUES (:aid, 'all', NULL)");
            $t->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
            $t->execute();
        } elseif ($targetMode === 'course') {
            $t = $pdo->prepare("INSERT INTO assignment_targets (assignment_id, target_type, target_value) VALUES (:aid, 'course', :val)");
            $t->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
            $t->bindParam(':val', $courseCode);
            $t->execute();
        } elseif ($targetMode === 'cohort') {
            $t = $pdo->prepare("INSERT INTO assignment_targets (assignment_id, target_type, target_value) VALUES (:aid, 'cohort', :val)");
            $t->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
            $t->bindParam(':val', $cohort);
            $t->execute();
        } else {
            $t = $pdo->prepare("INSERT INTO assignment_targets (assignment_id, target_type, target_value) VALUES (:aid, 'student', :val)");
            foreach ($studentIds as $sid) {
                $t->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
                $t->bindParam(':val', $sid);
                $t->execute();
            }
        }
    }

    $pdo->commit();
    send_json(['status' => 'success', 'message' => 'Assignment updated.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
