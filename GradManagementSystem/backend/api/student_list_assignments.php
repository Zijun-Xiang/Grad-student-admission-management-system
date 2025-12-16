<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['student']);

include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';

if (!assignments_tables_ready($pdo)) {
    send_json(['status' => 'error', 'message' => 'Assignments tables not found. Run backend/sql/09_assignments.sql first.'], 500);
}

$studentId = (string)(current_user()['id'] ?? '');
$cohort = get_student_entry_term_code($pdo, $studentId) ?: '';

try {
    ensure_assignment_grading_columns($pdo);
    $gradingEnabled = assignment_grading_enabled($pdo);

    $gradeSelect = $gradingEnabled ? "s.grade,\n                s.graded_at," : "NULL AS grade,\n                NULL AS graded_at,";

    $stmt = $pdo->prepare(
        "SELECT a.id,
                a.title,
                a.description,
                a.due_at,
                a.attachment_path,
                a.created_at,
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
                ) AS course_name,
                s.id AS submission_id,
                s.file_path AS submission_file_path,
                s.submitted_at,
                $gradeSelect
                (SELECT COUNT(*) FROM assignment_submission_comments c WHERE c.submission_id = s.id) AS comments_count
         FROM assignments a
         JOIN users u ON u.user_id = a.created_by
         LEFT JOIN assignment_submissions s ON s.assignment_id = a.id AND s.student_id = :sid_join
         WHERE EXISTS (
            SELECT 1 FROM assignment_targets t
            WHERE t.assignment_id = a.id
              AND (
                t.target_type = 'all'
                OR (t.target_type = 'student' AND t.target_value = :sid_target)
                -- Backward compatibility: old cohort target
                OR (t.target_type = 'cohort' AND t.target_value = :cohort)
                -- New: course target, matches if student registered for that course
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
         ORDER BY a.created_at DESC, a.id DESC"
    );
    // Avoid reusing the same named parameter multiple times (PDO MySQL may throw HY093).
    $stmt->bindParam(':sid_join', $studentId);
    $stmt->bindParam(':sid_target', $studentId);
    $stmt->bindParam(':cohort', $cohort);
    $stmt->bindParam(':sid_course', $studentId);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $rows, 'cohort' => $cohort]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
