<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE status = 'pending' ORDER BY upload_date ASC");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    send_json(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

