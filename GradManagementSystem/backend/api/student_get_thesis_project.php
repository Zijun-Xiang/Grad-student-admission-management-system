<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['student']);

include_once '../db.php';

try {
    $studentId = (string)(current_user()['id'] ?? '');

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

    $stmt = $pdo->prepare(
        "SELECT id, type, title, submission_date, defense_date, created_at
         FROM thesis_projects
         WHERE student_id = :sid
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->bindParam(':sid', $studentId);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $row ?: null]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
