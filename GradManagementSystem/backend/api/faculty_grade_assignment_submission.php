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
$submissionId = (int)($data['submission_id'] ?? 0);
$gradeRaw = $data['grade'] ?? null;

if ($submissionId <= 0) {
    send_json(['status' => 'error', 'message' => 'Missing submission_id.'], 400);
}

$grade = null;
if ($gradeRaw !== null && $gradeRaw !== '') {
    if (!is_numeric($gradeRaw)) {
        send_json(['status' => 'error', 'message' => 'Grade must be a number.'], 400);
    }
    $grade = (float)$gradeRaw;
    if ($grade < 0 || $grade > 100) {
        send_json(['status' => 'error', 'message' => 'Grade must be between 0 and 100.'], 400);
    }
}

try {
    ensure_assignment_grading_columns($pdo);
    if (!assignment_grading_enabled($pdo)) {
        send_json([
            'status' => 'error',
            'message' => 'Grading columns are missing. Run backend/sql/12_assignment_grades.sql first.',
        ], 500);
    }

    // Verify ownership: submission belongs to an assignment created by this faculty.
    $stmtChk = $pdo->prepare(
        "SELECT s.id
         FROM assignment_submissions s
         JOIN assignments a ON a.id = s.assignment_id
         WHERE s.id = :sid
           AND a.created_by = :fid
         LIMIT 1"
    );
    $stmtChk->bindParam(':sid', $submissionId, PDO::PARAM_INT);
    $stmtChk->bindParam(':fid', $facultyId);
    $stmtChk->execute();
    if (!$stmtChk->fetchColumn()) {
        send_json(['status' => 'error', 'message' => 'Forbidden or submission not found.'], 403);
    }

    if ($grade === null) {
        $stmt = $pdo->prepare(
            "UPDATE assignment_submissions
             SET grade = NULL, graded_at = NULL, graded_by = NULL
             WHERE id = :sid"
        );
        $stmt->bindParam(':sid', $submissionId, PDO::PARAM_INT);
        $stmt->execute();
        send_json(['status' => 'success', 'message' => 'Grade cleared.', 'grade' => null, 'graded_at' => null]);
    }

    $stmt = $pdo->prepare(
        "UPDATE assignment_submissions
         SET grade = :g, graded_at = NOW(), graded_by = :fid
         WHERE id = :sid"
    );
    $stmt->bindParam(':g', $grade);
    $stmt->bindParam(':fid', $facultyId);
    $stmt->bindParam(':sid', $submissionId, PDO::PARAM_INT);
    $stmt->execute();

    $stmtGet = $pdo->prepare("SELECT grade, graded_at FROM assignment_submissions WHERE id = :sid LIMIT 1");
    $stmtGet->bindParam(':sid', $submissionId, PDO::PARAM_INT);
    $stmtGet->execute();
    $row = $stmtGet->fetch(PDO::FETCH_ASSOC) ?: [];

    send_json([
        'status' => 'success',
        'message' => 'Grade saved.',
        'grade' => $row['grade'] ?? $grade,
        'graded_at' => $row['graded_at'] ?? null,
    ]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
