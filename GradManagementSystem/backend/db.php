<?php
// backend/db.php
// Configure via environment variables (avoid hardcoded secrets in source code).
//
// Required (in most setups):
// - DB_PASS
//
// Optional:
// - DB_HOST (default: localhost)
// - DB_NAME (default: grad_system)
// - DB_USER (default: root)
// - DB_CHARSET (default: utf8mb4)

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$db = getenv('DB_NAME') ?: 'grad_system';
$dbUser = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS');
if ($pass === false || $pass === '') {
    // Local dev fallback (recommended: set DB_PASS in your web server env instead of hardcoding).
    $pass = 'Lqw84441669.';
}
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $pass, $options);
} catch (\PDOException $e) {
    if (function_exists('send_json')) {
        send_json([
            'status' => 'error',
            'message' => 'Database connection failed. Check DB_HOST/DB_NAME/DB_USER/DB_PASS.',
        ], 500);
    }
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
