<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');

if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            (bool)($params['secure'] ?? false),
            true
        );
    }
    session_destroy();
}

send_json(['status' => 'success', 'message' => 'Logged out.']);
