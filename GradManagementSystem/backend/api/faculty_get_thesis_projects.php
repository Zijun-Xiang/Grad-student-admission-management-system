<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS thesis_projects (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            student_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(16) NOT NULL DEFAULT 'thesis',
            title VARCHAR(255) NULL,
            submission_date DATE NOT NULL,
            defense_date DATE NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_thesis_projects_student (student_id)
        )"
    );

    $facultyId = (string)(current_user()['id'] ?? '');

    $latestDocSubquery =
        "SELECT d1.*
         FROM documents d1
         JOIN (
             SELECT student_id, MAX(doc_id) AS max_id
             FROM documents
             WHERE doc_type = 'thesis_project'
             GROUP BY student_id
         ) latest ON latest.student_id = d1.student_id AND latest.max_id = d1.doc_id";

    $latestSubquery =
        "SELECT tp1.*
         FROM thesis_projects tp1
         JOIN (
             SELECT student_id, MAX(id) AS max_id
             FROM thesis_projects
             GROUP BY student_id
         ) latest ON latest.student_id = tp1.student_id AND latest.max_id = tp1.id";

    $stmt = $pdo->prepare(
        "SELECT sd.student_id,
                su.username AS student_username,
                su.email AS student_email,
                sd.first_name,
                sd.last_name,
                sd.mp_status,
                tp.type,
                tp.title,
                tp.submission_date,
                tp.defense_date,
                tp.created_at,
                td.doc_id AS thesis_doc_id,
                td.file_path AS thesis_file_path,
                td.status AS thesis_doc_status
         FROM student_details sd
         LEFT JOIN users su ON su.user_id = sd.student_id
         LEFT JOIN ($latestSubquery) tp ON tp.student_id = sd.student_id
         LEFT JOIN ($latestDocSubquery) td ON td.student_id = sd.student_id
         WHERE sd.major_professor_id = :fid
           AND sd.mp_status <> 'none'
         ORDER BY sd.student_id ASC"
    );
    $stmt->bindParam(':fid', $facultyId);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
