<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['admin']);

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

    $latestSubquery =
        "SELECT tp1.*
         FROM thesis_projects tp1
         JOIN (
             SELECT student_id, MAX(id) AS max_id
             FROM thesis_projects
             GROUP BY student_id
         ) latest ON latest.student_id = tp1.student_id AND latest.max_id = tp1.id";

    $stmt = $pdo->query(
        "SELECT u.user_id AS student_id,
                u.username,
                u.email,
                sd.first_name,
                sd.last_name,
                tp.type,
                tp.title,
                tp.submission_date,
                tp.defense_date,
                tp.created_at
         FROM users u
         LEFT JOIN student_details sd ON sd.student_id = u.user_id
         LEFT JOIN ($latestSubquery) tp ON tp.student_id = u.user_id
         WHERE u.role = 'student'
         ORDER BY u.username ASC, u.user_id ASC"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
