<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/majors_common.php';

$facultyId = (string)(current_user()['id'] ?? '');
$data = get_json_input();
$courseCode = strtoupper(trim((string)($data['course_code'] ?? '')));

if ($courseCode === '') {
    send_json(['status' => 'error', 'message' => 'Missing course_code.'], 400);
}

try {
    // Ensure majors schema exists and core_courses has major_code.
    try {
        ensure_majors_schema($pdo);
    } catch (Exception $e) {
        // ignore
    }

    $facultyMajor = get_user_major_code($pdo, $facultyId);

    // Ensure table exists
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS faculty_courses (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          faculty_id BIGINT UNSIGNED NOT NULL,
          course_code VARCHAR(32) NOT NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          UNIQUE KEY uniq_faculty_course (faculty_id, course_code),
          KEY idx_faculty_courses_faculty (faculty_id),
          KEY idx_faculty_courses_course (course_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    // Validate course exists (best-effort).
    try {
        $hasMajor = majors_has_column($pdo, 'core_courses', 'major_code');
        if ($hasMajor) {
            $stmtV = $pdo->prepare("SELECT 1 FROM core_courses WHERE course_code = :c AND major_code = :m LIMIT 1");
            $stmtV->bindParam(':m', $facultyMajor);
        } else {
            $stmtV = $pdo->prepare("SELECT 1 FROM core_courses WHERE course_code = :c LIMIT 1");
        }
        $stmtV->bindParam(':c', $courseCode);
        $stmtV->execute();
        if (!$stmtV->fetchColumn()) {
            send_json(['status' => 'error', 'message' => 'Course not found for your major.'], 404);
        }
    } catch (Exception $e) {
        // ignore
    }

    $stmt = $pdo->prepare("INSERT IGNORE INTO faculty_courses (faculty_id, course_code) VALUES (:fid, :code)");
    $stmt->bindParam(':fid', $facultyId);
    $stmt->bindParam(':code', $courseCode);
    $stmt->execute();

    send_json(['status' => 'success', 'message' => 'Course added.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
