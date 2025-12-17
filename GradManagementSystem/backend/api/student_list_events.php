<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['student']);

include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';
require_once __DIR__ . '/course_actions_common.php';

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

$ensureCourseActionReads = function (PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS advisee_course_action_reads (
            action_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (action_id, student_id),
            KEY idx_acar_student (student_id, read_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
};

$studentId = (string)(current_user()['id'] ?? '');
$cohort = get_student_entry_term_code($pdo, $studentId) ?: '';

try {
    ensure_assignment_reads_table($pdo);
    ensure_advisee_course_actions_table($pdo);
    $ensureCourseActionReads($pdo);

    $stmt = $pdo->prepare(
        "SELECT 'assignment' AS type,
                a.id,
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
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT 20"
    );
    $stmt->bindParam(':sid_read', $studentId);
    $stmt->bindParam(':sid_target', $studentId);
    $stmt->bindParam(':sid_adv', $studentId);
    $stmt->bindParam(':cohort', $cohort);
    $stmt->bindParam(':sid_course', $studentId);
    $stmt->execute();
    $assignmentRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt2 = $pdo->prepare(
        "SELECT 'course_action' AS type,
                a.id,
                CONCAT('Advisor request: ', UPPER(a.action_type), ' ', a.course_code) AS title,
                a.created_at,
                NULL AS due_at,
                u.username AS faculty_username,
                a.course_code,
                cc.course_name,
                a.action_type,
                a.comment
         FROM advisee_course_actions a
         JOIN users u ON u.user_id = a.faculty_id
         LEFT JOIN core_courses cc ON cc.course_code = a.course_code
         LEFT JOIN advisee_course_action_reads r ON r.action_id = a.id AND r.student_id = :sid_read2
         WHERE a.student_id = :sid
           AND a.status = 'pending'
           AND r.action_id IS NULL
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT 20"
    );
    $stmt2->bindParam(':sid', $studentId);
    $stmt2->bindParam(':sid_read2', $studentId);
    $stmt2->execute();
    $courseRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $rows = array_merge($courseRows ?: [], $assignmentRows ?: []);
    usort($rows, function ($a, $b) {
        $ta = strtotime((string)($a['created_at'] ?? '')) ?: 0;
        $tb = strtotime((string)($b['created_at'] ?? '')) ?: 0;
        if ($ta !== $tb) return $tb <=> $ta;
        return (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
    });
    $rows = array_slice($rows, 0, 20);

    send_json(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
