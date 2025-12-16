<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';

$code = isset($_GET['code']) ? (string)$_GET['code'] : '';
if ($code === '') {
    send_json(['status' => 'error', 'message' => 'Missing code.'], 400);
}

try {
    $stmt = $pdo->prepare('SELECT * FROM registrar_signals WHERE code = :code LIMIT 1');
    $stmt->bindParam(':code', $code);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        send_json(['status' => 'error', 'message' => 'Code not found.'], 404);
    }
    send_json(['status' => 'success', 'data' => $row]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

