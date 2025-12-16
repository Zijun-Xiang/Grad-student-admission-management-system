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

try {
    ensure_assignment_reads_table($pdo);

    $stmt = $pdo->prepare(
        "SELECT a.id,
                a.title,
                a.created_at,
                a.due_at,
                u.username AS faculty_username,
                (
                    SELECT t.target_value
                    FROM assignment_targets t
                    WHERE t.assignment_id = a.id AND t.target_type = 'course'
                    ORDER BY t.id ASC
                    LIMIT 1
                ) AS course_code,
                (
                    SELECT cc.course_name
                    FROM assignment_targets t
                    JOIN core_courses cc ON cc.course_code = t.target_value
                    WHERE t.assignment_id = a.id AND t.target_type = 'course'
                    ORDER BY t.id ASC
                    LIMIT 1
                ) AS course_name
         FROM assignments a
         JOIN users u ON u.user_id = a.created_by
         LEFT JOIN assignment_reads r ON r.assignment_id = a.id AND r.student_id = :sid_read
         WHERE r.assignment_id IS NULL
           AND EXISTS (
                SELECT 1 FROM assignment_targets t
                WHERE t.assignment_id = a.id
                  AND (
                    t.target_type = 'all'
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
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT 20"
    );
    $stmt->bindParam(':sid_read', $studentId);
    $stmt->bindParam(':sid_target', $studentId);
    $stmt->bindParam(':cohort', $cohort);
    $stmt->bindParam(':sid_course', $studentId);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

