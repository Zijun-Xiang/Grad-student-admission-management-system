<?php
// Shared helpers for advisor course actions.

function ensure_advisee_course_actions_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS advisee_course_actions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            faculty_id BIGINT UNSIGNED NOT NULL,
            student_id BIGINT UNSIGNED NOT NULL,
            action_type ENUM('add','drop') NOT NULL,
            course_code VARCHAR(32) NOT NULL,
            comment TEXT NULL,
            status ENUM('pending','applied','cancelled','rejected') NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            applied_at DATETIME NULL,
            cancelled_at DATETIME NULL,
            rejected_at DATETIME NULL,
            student_comment TEXT NULL,
            PRIMARY KEY (id),
            KEY idx_aca_student_status (student_id, status, created_at),
            KEY idx_aca_faculty_status (faculty_id, status, created_at),
            KEY idx_aca_course (course_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // Migrate existing tables (best-effort).
    try {
        $col = $pdo->query("SHOW COLUMNS FROM advisee_course_actions LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
        $type = strtolower((string)($col['Type'] ?? ''));
        if ($type !== '' && strpos($type, "rejected") === false) {
            $pdo->exec("ALTER TABLE advisee_course_actions MODIFY status ENUM('pending','applied','cancelled','rejected') NOT NULL DEFAULT 'pending'");
        }
    } catch (Exception $e) {
        // ignore
    }

    try {
        $hasRejectedAt = (bool)$pdo->query("SHOW COLUMNS FROM advisee_course_actions LIKE 'rejected_at'")->fetchColumn();
        if (!$hasRejectedAt) $pdo->exec("ALTER TABLE advisee_course_actions ADD COLUMN rejected_at DATETIME NULL AFTER cancelled_at");
    } catch (Exception $e) {
        // ignore
    }
    try {
        $hasStudentComment = (bool)$pdo->query("SHOW COLUMNS FROM advisee_course_actions LIKE 'student_comment'")->fetchColumn();
        if (!$hasStudentComment) $pdo->exec("ALTER TABLE advisee_course_actions ADD COLUMN student_comment TEXT NULL AFTER rejected_at");
    } catch (Exception $e) {
        // ignore
    }
}

function faculty_owns_advisee(PDO $pdo, string $facultyId, string $studentId): bool
{
    try {
        $stmt = $pdo->prepare(
            "SELECT 1
             FROM student_details sd
             WHERE sd.student_id = :sid
               AND sd.major_professor_id = :fid
               AND sd.mp_status <> 'none'
             LIMIT 1"
        );
        $stmt->bindParam(':sid', $studentId);
        $stmt->bindParam(':fid', $facultyId);
        $stmt->execute();
        return (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}
