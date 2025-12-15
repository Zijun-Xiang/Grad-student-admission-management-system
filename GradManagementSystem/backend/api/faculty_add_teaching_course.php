<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);
require_method('POST');

include_once '../db.php';

$facultyId = (string)(current_user()['id'] ?? '');
$data = get_json_input();
$courseCode = strtoupper(trim((string)($data['course_code'] ?? '')));

if ($courseCode === '') {
    send_json(['status' => 'error', 'message' => 'Missing course_code.'], 400);
}

try {
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
        $stmtV = $pdo->prepare("SELECT 1 FROM core_courses WHERE course_code = :c LIMIT 1");
        $stmtV->bindParam(':c', $courseCode);
        $stmtV->execute();
        if (!$stmtV->fetchColumn()) {
            send_json(['status' => 'error', 'message' => 'Course not found.'], 404);
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

