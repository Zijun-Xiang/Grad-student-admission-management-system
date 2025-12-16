<?php
require_once __DIR__ . '/../bootstrap.php';

include_once '../db.php';
require_once __DIR__ . '/majors_common.php';

try {
    $majors = list_active_majors($pdo);
    send_json(['status' => 'success', 'data' => $majors]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

