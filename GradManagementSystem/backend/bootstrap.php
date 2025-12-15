<?php
declare(strict_types=1);

function cors(): void
{
    $allowed = getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:5173';
    $allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $allowed))));

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $isAllowed = false;
    if ($origin) {
        if (in_array($origin, $allowedOrigins, true)) {
            $isAllowed = true;
        } else {
            $parts = parse_url($origin);
            $scheme = is_array($parts) ? ($parts['scheme'] ?? '') : '';
            $host = is_array($parts) ? ($parts['host'] ?? '') : '';
            if (($scheme === 'http' || $scheme === 'https') && ($host === 'localhost' || $host === '127.0.0.1')) {
                $isAllowed = true;
            }
        }
    }

    if ($isAllowed) {
        header("Access-Control-Allow-Origin: $origin");
        header('Vary: Origin');
    }

    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

cors();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit();
}

session_name(getenv('SESSION_NAME') ?: 'grad_session');
session_set_cookie_params([
    'httponly' => true,
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'samesite' => 'Lax',
    'path' => '/',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!defined('BOOTSTRAP_NO_JSON')) {
    header('Content-Type: application/json; charset=UTF-8');
}

function send_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit();
}

function normalize_role(?string $role): string
{
    $r = strtolower(trim((string)$role));
    // keep only safe chars (avoid hidden/control chars breaking role checks)
    $r = preg_replace('/[^a-z_]/', '', $r);
    return is_string($r) ? $r : '';
}

function generate_registrar_code(int $length = 24): string
{
    // URL-safe code for registrar confirmation, e.g. "GRAD-AB12..."
    $bytes = random_bytes((int)ceil($length / 2));
    return 'GRAD-' . strtoupper(substr(bin2hex($bytes), 0, $length));
}

function require_method(string $method): void
{
    $actual = $_SERVER['REQUEST_METHOD'] ?? '';
    if ($actual !== $method) {
        send_json(['status' => 'error', 'message' => "Method not allowed (expected $method)."], 405);
    }
}

function get_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        send_json(['status' => 'error', 'message' => 'Invalid JSON body.'], 400);
    }

    return $decoded;
}

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;
    return is_array($user) ? $user : null;
}

function require_login(?array $roles = null): array
{
    $user = current_user();
    if (!$user) {
        send_json(['status' => 'error', 'message' => 'Not authenticated.'], 401);
    }

    if ($roles !== null) {
        $role = normalize_role(isset($user['role']) ? (string)$user['role'] : '');
        $allowed = array_map(fn ($r) => normalize_role((string)$r), $roles);
        if (!in_array($role, $allowed, true)) {
            send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);
        }
    }

    return $user;
}

function effective_student_id_for_request(array $user, ?string $requestedStudentId): string
{
    $userId = (string)($user['id'] ?? '');
    $role = (string)($user['role'] ?? '');

    if ($role === 'faculty' || $role === 'admin') {
        if ($requestedStudentId === null || $requestedStudentId === '') {
            send_json(['status' => 'error', 'message' => 'Missing student_id.'], 400);
        }
        return (string)$requestedStudentId;
    }

    if ($requestedStudentId !== null && $requestedStudentId !== '' && (string)$requestedStudentId !== $userId) {
        send_json(['status' => 'error', 'message' => 'Forbidden (student_id mismatch).'], 403);
    }

    return $userId;
}

function grad_term_code_from_date(string $date): ?string
{
    $ts = strtotime($date);
    if ($ts === false) return null;
    $year = (int)date('Y', $ts);
    $month = (int)date('n', $ts);
    if ($month <= 4) return $year . 'SP';
    if ($month <= 8) return $year . 'SU';
    return $year . 'FA';
}

function grad_current_term_code(): string
{
    $year = (int)date('Y');
    $month = (int)date('n');
    if ($month <= 4) return $year . 'SP';
    if ($month <= 8) return $year . 'SU';
    return $year . 'FA';
}

function grad_term_index(string $termCode): ?int
{
    $termCode = strtoupper(trim($termCode));
    if (!preg_match('/^(\\d{4})(SP|SU|FA)$/', $termCode, $m)) return null;
    $year = (int)$m[1];
    $season = $m[2];
    $offset = ($season === 'SP') ? 0 : (($season === 'SU') ? 1 : 2);
    return ($year * 3) + $offset;
}

function grad_term_number(string $entryTermCode, string $currentTermCode): int
{
    $a = grad_term_index($entryTermCode);
    $b = grad_term_index($currentTermCode);
    if ($a === null || $b === null) return 1;
    return max(1, ($b - $a) + 1);
}
