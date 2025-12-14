<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');

include_once '../db.php';

$user = require_login(['faculty', 'admin']);
$data = get_json_input();
$docId = (string)($data['doc_id'] ?? '');
$comment = trim((string)($data['comment'] ?? ''));

if ($docId === '' || $comment === '') {
    send_json(['status' => 'error', 'message' => 'Missing doc_id/comment.'], 400);
}

try {
    // Ensure table exists (dev-friendly).
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS document_comments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            doc_id BIGINT UNSIGNED NOT NULL,
            author_id BIGINT UNSIGNED NOT NULL,
            author_role VARCHAR(32) NOT NULL,
            comment TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_doc_comments_doc (doc_id),
            KEY idx_doc_comments_author (author_id)
        )"
    );

    $stmtDoc = $pdo->prepare('SELECT doc_id, student_id FROM documents WHERE doc_id = :did LIMIT 1');
    $stmtDoc->bindParam(':did', $docId);
    $stmtDoc->execute();
    $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);
    if (!$doc) {
        send_json(['status' => 'error', 'message' => 'Document not found.'], 404);
    }

    $role = normalize_role((string)($user['role'] ?? ''));
    $uid = (string)($user['id'] ?? '');
    $studentId = (string)$doc['student_id'];

    // If faculty, must be the student's advisor.
    if ($role === 'faculty') {
        $stmtAdv = $pdo->prepare(
            "SELECT 1 FROM student_details
             WHERE student_id = :sid AND major_professor_id = :fid
               AND (mp_status = 'approved' OR mp_status = 'accepted')
             LIMIT 1"
        );
        $stmtAdv->bindParam(':sid', $studentId);
        $stmtAdv->bindParam(':fid', $uid);
        $stmtAdv->execute();
        if (!$stmtAdv->fetchColumn()) {
            send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);
        }
    }

    $stmt = $pdo->prepare(
        "INSERT INTO document_comments (doc_id, author_id, author_role, comment)
         VALUES (:did, :aid, :ar, :c)"
    );
    $stmt->bindParam(':did', $docId);
    $stmt->bindParam(':aid', $uid);
    $stmt->bindParam(':ar', $role);
    $stmt->bindParam(':c', $comment);
    $stmt->execute();

    send_json(['status' => 'success', 'message' => 'Comment added.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
