<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['student']);

include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';

if (!assignments_tables_ready($pdo)) {
    send_json(['status' => 'error', 'message' => 'Assignments tables not found. Run backend/sql/09_assignments.sql first.'], 500);
}

function ensure_assignment_reads_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS assignment_reads (
            assignment_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (assignment_id, student_id),
            KEY idx_assignment_reads_student (student_id),
            KEY idx_assignment_reads_read_at (read_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

$studentId = (string)(current_user()['id'] ?? '');
$cohort = get_student_entry_term_code($pdo, $studentId) ?: '';

$data = get_json_input();
$assignmentId = (string)($data['assignment_id'] ?? '');
if ($assignmentId === '' || !ctype_digit($assignmentId) || (int)$assignmentId <= 0) {
    send_json(['status' => 'error', 'message' => 'Invalid assignment_id.'], 400);
}

try {
    ensure_assignment_reads_table($pdo);

    // Ensure the student can actually see this assignment.
    $stmtCheck = $pdo->prepare(
        "SELECT 1
         FROM assignments a
         WHERE a.id = :aid
           AND EXISTS (
                SELECT 1
                FROM assignment_targets t
                WHERE t.assignment_id = a.id
                  AND (
                    (
                        t.target_type = 'all'
                        AND EXISTS (
                            SELECT 1
                            FROM student_details sd
                            WHERE sd.student_id = :sid_adv
                              AND sd.major_professor_id = a.created_by
                              AND sd.mp_status <> 'none'
                            LIMIT 1
                        )
                    )
                    OR (t.target_type = 'student' AND t.target_value = :sid_target)
                    OR (t.target_type = 'cohort' AND t.target_value = :cohort)
                    OR (
                        t.target_type = 'course'
                        AND EXISTS (
                            SELECT 1
                            FROM student_registrations sr
                            WHERE sr.student_id = :sid_course
                              AND sr.course_code = t.target_value
                            LIMIT 1
                        )
                    )
                  )
           )
         LIMIT 1"
    );
    $stmtCheck->bindValue(':aid', (int)$assignmentId, PDO::PARAM_INT);
    $stmtCheck->bindParam(':sid_target', $studentId);
    $stmtCheck->bindParam(':sid_adv', $studentId);
    $stmtCheck->bindParam(':cohort', $cohort);
    $stmtCheck->bindParam(':sid_course', $studentId);
    $stmtCheck->execute();
    if (!$stmtCheck->fetchColumn()) {
        send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);
    }

    $stmt = $pdo->prepare(
        "INSERT INTO assignment_reads (assignment_id, student_id, read_at)
         VALUES (:aid, :sid, NOW())
         ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)"
    );
    $stmt->bindValue(':aid', (int)$assignmentId, PDO::PARAM_INT);
    $stmt->bindParam(':sid', $studentId);
    $stmt->execute();

    send_json(['status' => 'success', 'message' => 'Marked as read.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
