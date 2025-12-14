<?php
require_once __DIR__ . '/../bootstrap.php';

$user = current_user();
if (!$user) {
    send_json(['status' => 'error', 'message' => 'Not authenticated.'], 401);
}

send_json(['status' => 'success', 'user' => $user]);
