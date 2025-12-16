<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/majors_common.php';

$data = get_json_input();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');
$role = trim((string)($data['role'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$firstName = trim((string)($data['first_name'] ?? ''));
$lastName = trim((string)($data['last_name'] ?? ''));
$entryTermCode = trim((string)($data['term_code'] ?? ''));
$entryDate = trim((string)($data['entry_date'] ?? ''));
$majorCode = normalize_major_code(isset($data['major_code']) ? (string)$data['major_code'] : '');

if ($username === '' || $password === '' || $role === '') {
    send_json(['status' => 'error', 'message' => 'Missing username/password/role.'], 400);
}

$allowedRoles = ['student', 'faculty'];
if (!in_array($role, $allowedRoles, true)) {
    send_json(['status' => 'error', 'message' => 'Invalid role.'], 400);
}

function guess_term_code(): string
{
    $year = (int)date('Y');
    $month = (int)date('n');
    if ($month <= 4) return $year . 'SP';
    if ($month <= 8) return $year . 'SU';
    return $year . 'FA';
}

function term_code_from_date(string $date): ?string
{
    $ts = strtotime($date);
    if ($ts === false) return null;
    $year = (int)date('Y', $ts);
    $month = (int)date('n', $ts);
    if ($month <= 4) return $year . 'SP';
    if ($month <= 8) return $year . 'SU';
    return $year . 'FA';
}

function ensure_user_profiles_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS user_profiles (
            user_id BIGINT UNSIGNED NOT NULL,
            entry_date DATE NULL,
            entry_term_code VARCHAR(32) NULL,
            major_code VARCHAR(16) NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id),
            KEY idx_user_profiles_term (entry_term_code),
            KEY idx_user_profiles_major (major_code)
        )"
    );
}

function default_value_for_column(array $col)
{
    $type = strtolower((string)($col['Type'] ?? ''));

    if (preg_match("/^enum\\((.*)\\)$/", $type, $m)) {
        if (preg_match("/'([^']*)'/", (string)$m[1], $m2)) {
            return (string)$m2[1];
        }
        return '';
    }

    if (
        strpos($type, 'int') !== false ||
        strpos($type, 'decimal') !== false ||
        strpos($type, 'float') !== false ||
        strpos($type, 'double') !== false
    ) {
        return 0;
    }

    if (strpos($type, 'datetime') !== false || strpos($type, 'timestamp') !== false) {
        return date('Y-m-d H:i:s');
    }
    if (strpos($type, 'date') !== false) {
        return date('Y-m-d');
    }
    if (strpos($type, 'time') !== false) {
        return date('H:i:s');
    }

    if (strpos($type, 'json') !== false) {
        return '{}';
    }

    return '';
}

if (strlen($username) < 3 || strlen($username) > 64) {
    send_json(['status' => 'error', 'message' => 'Username must be 3-64 characters.'], 400);
}

if (strlen($password) < 6) {
    send_json(['status' => 'error', 'message' => 'Password must be at least 6 characters.'], 400);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json(['status' => 'error', 'message' => 'Invalid email.'], 400);
}

try {
    // Ensure auxiliary tables exist BEFORE starting any transaction.
    // MySQL will implicitly commit when running DDL (e.g., CREATE TABLE), which would break our transaction.
    try {
        ensure_majors_schema($pdo);
    } catch (Exception $e) {
        // ignore
    }

    if ($majorCode === '') {
        send_json(['status' => 'error', 'message' => 'Missing major/program.'], 400);
    }
    if (!major_code_exists($pdo, $majorCode)) {
        send_json(['status' => 'error', 'message' => 'Invalid major.'], 400);
    }

    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE username = :u LIMIT 1');
    $stmt->bindParam(':u', $username);
    $stmt->execute();
    if ($stmt->fetchColumn()) {
        send_json(['status' => 'error', 'message' => 'Username already exists.'], 409);
    }

    // Best-effort email uniqueness if the column exists.
    if ($email !== '') {
        try {
            $stmtEmail = $pdo->prepare('SELECT 1 FROM users WHERE email = :e LIMIT 1');
            $stmtEmail->bindParam(':e', $email);
            $stmtEmail->execute();
            if ($stmtEmail->fetchColumn()) {
                send_json(['status' => 'error', 'message' => 'Email already exists.'], 409);
            }
        } catch (Exception $e) {
            // Ignore if email column doesn't exist.
        }
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert into users table, adapt to schema (some setups might not have email).
    $hasEmail = false;
    try {
        $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            if (($col['Field'] ?? '') === 'email') {
                $hasEmail = true;
                break;
            }
        }
    } catch (Exception $e) {
        $hasEmail = true;
    }

    $pdo->beginTransaction();

    if ($hasEmail) {
        $stmtIns = $pdo->prepare('INSERT INTO users (username, password, role, email) VALUES (:u, :p, :r, :e)');
        $stmtIns->bindParam(':u', $username);
        $stmtIns->bindParam(':p', $hash);
        $stmtIns->bindParam(':r', $role);
        $stmtIns->bindParam(':e', $email);
        $stmtIns->execute();
    } else {
        $stmtIns = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (:u, :p, :r)');
        $stmtIns->bindParam(':u', $username);
        $stmtIns->bindParam(':p', $hash);
        $stmtIns->bindParam(':r', $role);
        $stmtIns->execute();
    }

    $userId = (string)$pdo->lastInsertId();

    // Upsert entry/admission date for any user.
    try {
        $termCodeForProfile = $entryTermCode !== '' ? $entryTermCode : (term_code_from_date($entryDate) ?: (getenv('DEFAULT_TERM_CODE') ?: guess_term_code()));
        $dateForProfile = $entryDate !== '' ? $entryDate : null;
        $majorForProfile = $majorCode;

        $stmtProf = $pdo->prepare(
            "INSERT INTO user_profiles (user_id, entry_date, entry_term_code, major_code)
             VALUES (:uid, :ed, :tc, :mc)
             ON DUPLICATE KEY UPDATE
                entry_date = VALUES(entry_date),
                entry_term_code = VALUES(entry_term_code),
                major_code = VALUES(major_code)"
        );
        $stmtProf->bindParam(':uid', $userId);
        $stmtProf->bindValue(':ed', $dateForProfile, $dateForProfile === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmtProf->bindParam(':tc', $termCodeForProfile);
        $stmtProf->bindParam(':mc', $majorForProfile);
        $stmtProf->execute();
    } catch (Exception $e) {
        // ignore
    }

    // Best-effort: create student_details row for students if table exists.
    if ($role === 'student') {
        $termCode = $entryTermCode !== '' ? $entryTermCode : (term_code_from_date($entryDate) ?: (getenv('DEFAULT_TERM_CODE') ?: guess_term_code()));
        try {
            $cols = $pdo->query('SHOW COLUMNS FROM student_details')->fetchAll(PDO::FETCH_ASSOC);

            $values = [];
            foreach ($cols as $col) {
                $field = (string)($col['Field'] ?? '');
                if ($field === 'student_id') $values['student_id'] = $userId;
                if ($field === 'first_name') $values['first_name'] = $firstName;
                if ($field === 'last_name') $values['last_name'] = $lastName;
                if ($field === 'mp_status') $values['mp_status'] = 'none';
                if ($field === 'major_professor_id') $values['major_professor_id'] = null;
                if ($field === 'entry_term_code') $values['entry_term_code'] = $termCode;
            }

            foreach ($cols as $col) {
                $field = (string)($col['Field'] ?? '');
                $null = (string)($col['Null'] ?? '');
                $default = $col['Default'] ?? null;
                $extra = strtolower((string)($col['Extra'] ?? ''));
                if ($null === 'NO' && $default === null && strpos($extra, 'auto_increment') === false) {
                    if (!array_key_exists($field, $values)) {
                        $values[$field] = default_value_for_column($col);
                    }
                }
            }

            if (!empty($values)) {
                $columnsSql = implode(', ', array_keys($values));
                $placeholders = implode(', ', array_map(fn ($k) => ':' . $k, array_keys($values)));
                $sql = "INSERT INTO student_details ($columnsSql) VALUES ($placeholders)";
                $stmtSd = $pdo->prepare($sql);
                foreach ($values as $k => $v) {
                    if ($v === null) {
                        $stmtSd->bindValue(':' . $k, null, PDO::PARAM_NULL);
                    } else {
                        $stmtSd->bindValue(':' . $k, $v);
                    }
                }
                $stmtSd->execute();
            }
        } catch (Exception $e) {
            // Ignore if student_details doesn't exist or schema mismatch.
        }

        // Default: new students start in Term 1 with an Admission Letter hold.
        try {
            $hasTermCodeCol = false;
            try {
                $holdCols = $pdo->query('SHOW COLUMNS FROM holds')->fetchAll(PDO::FETCH_ASSOC);
                foreach ($holdCols as $c) {
                    if ((string)($c['Field'] ?? '') === 'term_code') {
                        $hasTermCodeCol = true;
                        break;
                    }
                }
            } catch (Exception $e) {
                $hasTermCodeCol = false;
            }

            if ($hasTermCodeCol) {
                $stmtHold = $pdo->prepare(
                    "INSERT INTO holds (student_id, hold_type, is_active, term_code)
                     VALUES (:sid, 'admission_letter', TRUE, :term)"
                );
                $stmtHold->bindParam(':sid', $userId);
                $stmtHold->bindParam(':term', $termCode);
                $stmtHold->execute();
            } else {
                $stmtHold = $pdo->prepare(
                    "INSERT INTO holds (student_id, hold_type, is_active)
                     VALUES (:sid, 'admission_letter', TRUE)"
                );
                $stmtHold->bindParam(':sid', $userId);
                $stmtHold->execute();
            }
        } catch (Exception $e) {
            throw new Exception('Failed to create initial hold. Ensure holds table exists and has columns (student_id, hold_type, is_active). ' . $e->getMessage(), 0, $e);
        }
    }

    $pdo->commit();

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $userId,
        'username' => $username,
        'role' => normalize_role($role),
    ];

    send_json([
        'status' => 'success',
        'message' => 'Registered successfully.',
        'user' => $_SESSION['user'],
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
