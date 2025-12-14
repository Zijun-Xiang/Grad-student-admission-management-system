<?php
require_once __DIR__ . '/../bootstrap.php';

include_once '../db.php';

$user = require_login();
$docId = isset($_GET['doc_id']) ? (string)$_GET['doc_id'] : '';
if ($docId === '') {
    send_json(['status' => 'error', 'message' => 'Missing doc_id.'], 400);
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

    // Access: admin, faculty advisor of this student, or the student owner.
    $allowed = false;
    if ($role === 'admin') {
        $allowed = true;
    } elseif ($role === 'student' && $uid === $studentId) {
        $allowed = true;
    } elseif ($role === 'faculty') {
        $stmtAdv = $pdo->prepare(
            "SELECT 1 FROM student_details
             WHERE student_id = :sid AND major_professor_id = :fid
               AND (mp_status = 'approved' OR mp_status = 'accepted')
             LIMIT 1"
        );
        $stmtAdv->bindParam(':sid', $studentId);
        $stmtAdv->bindParam(':fid', $uid);
        $stmtAdv->execute();
        $allowed = (bool)$stmtAdv->fetchColumn();
    }

    if (!$allowed) {
        send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);
    }

    $stmt = $pdo->prepare(
        "SELECT c.id, c.doc_id, c.author_id, c.author_role, u.username AS author_username, c.comment, c.created_at
         FROM document_comments c
         LEFT JOIN users u ON u.user_id = c.author_id
         WHERE c.doc_id = :did
         ORDER BY c.created_at ASC, c.id ASC"
    );
    $stmt->bindParam(':did', $docId);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $comments]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
