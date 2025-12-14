<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';

$facultyId = (string)(current_user()['id'] ?? '');

try {
    // Only show documents for students whose major professor is this faculty AND approved.
    $stmt = $pdo->prepare(
        "SELECT d.doc_id,
                d.student_id,
                su.username AS student_username,
                d.doc_type,
                d.file_path,
                d.status,
                d.upload_date
         FROM documents d
         JOIN student_details sd ON sd.student_id = d.student_id
         LEFT JOIN users su ON su.user_id = d.student_id
         WHERE sd.major_professor_id = :fid
           AND (sd.mp_status = 'approved' OR sd.mp_status = 'accepted')
         ORDER BY d.upload_date DESC, d.doc_id DESC"
    );
    $stmt->bindParam(':fid', $facultyId);
    $stmt->execute();
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $docs]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

