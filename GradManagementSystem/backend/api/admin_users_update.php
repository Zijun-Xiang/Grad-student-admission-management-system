<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['admin']);
require_method('POST');

include_once '../db.php';

$data = get_json_input();
$userId = (string)($data['user_id'] ?? '');
$username = isset($data['username']) ? trim((string)$data['username']) : null;
$email = isset($data['email']) ? trim((string)$data['email']) : null;
$entryDate = isset($data['entry_date']) ? trim((string)$data['entry_date']) : null;
$entryTermCode = isset($data['entry_term_code']) ? trim((string)$data['entry_term_code']) : null;
$firstName = isset($data['first_name']) ? trim((string)$data['first_name']) : null;
$lastName = isset($data['last_name']) ? trim((string)$data['last_name']) : null;

if ($userId === '') {
    send_json(['status' => 'error', 'message' => 'Missing user_id.'], 400);
}

function term_code_from_date(?string $date): ?string
{
    if ($date === null || $date === '') return null;
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
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id),
            KEY idx_user_profiles_term (entry_term_code)
        )"
    );
}

try {
    $stmt = $pdo->prepare('SELECT user_id, username, role FROM users WHERE user_id = :uid LIMIT 1');
    $stmt->bindParam(':uid', $userId);
    $stmt->execute();
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        send_json(['status' => 'error', 'message' => 'User not found.'], 404);
    }

    $role = normalize_role((string)($u['role'] ?? ''));

    if ($username !== null && ($username === '' || strlen($username) < 3 || strlen($username) > 64)) {
        send_json(['status' => 'error', 'message' => 'Username must be 3-64 characters.'], 400);
    }
    if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        send_json(['status' => 'error', 'message' => 'Invalid email.'], 400);
    }

    $hasEmail = false;
    try {
        $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            if ((string)($c['Field'] ?? '') === 'email') {
                $hasEmail = true;
                break;
            }
        }
    } catch (Exception $e) {
        $hasEmail = true;
    }

    // Ensure profile table exists BEFORE transactions (MySQL DDL may auto-commit).
    if ($entryDate !== null || $entryTermCode !== null) {
        ensure_user_profiles_table($pdo);
    }

    $pdo->beginTransaction();

    // Update username/email (if requested)
    if ($username !== null || ($email !== null && $hasEmail)) {
        if ($username !== null && $username !== (string)($u['username'] ?? '')) {
            $stmtChk = $pdo->prepare('SELECT 1 FROM users WHERE username = :u AND user_id <> :uid LIMIT 1');
            $stmtChk->bindParam(':u', $username);
            $stmtChk->bindParam(':uid', $userId);
            $stmtChk->execute();
            if ($stmtChk->fetchColumn()) {
                $pdo->rollBack();
                send_json(['status' => 'error', 'message' => 'Username already exists.'], 409);
            }
        }

        if ($hasEmail) {
            $sql = 'UPDATE users SET username = COALESCE(:u, username), email = COALESCE(:e, email) WHERE user_id = :uid';
            $stmtUp = $pdo->prepare($sql);
            $stmtUp->bindValue(':u', $username, $username === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmtUp->bindValue(':e', $email, $email === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmtUp->bindParam(':uid', $userId);
            $stmtUp->execute();
        } else {
            if ($username !== null) {
                $stmtUp = $pdo->prepare('UPDATE users SET username = :u WHERE user_id = :uid');
                $stmtUp->bindParam(':u', $username);
                $stmtUp->bindParam(':uid', $userId);
                $stmtUp->execute();
            }
        }
    }

    // Upsert profile fields
    if ($entryDate !== null || $entryTermCode !== null) {
        $tc = $entryTermCode;
        if (($tc === null || $tc === '') && $entryDate !== null && $entryDate !== '') {
            $tc = term_code_from_date($entryDate);
        }
        $stmtProf = $pdo->prepare(
            "INSERT INTO user_profiles (user_id, entry_date, entry_term_code)
             VALUES (:uid, :ed, :tc)
             ON DUPLICATE KEY UPDATE
                entry_date = COALESCE(VALUES(entry_date), entry_date),
                entry_term_code = COALESCE(VALUES(entry_term_code), entry_term_code)"
        );
        $stmtProf->bindParam(':uid', $userId);
        $stmtProf->bindValue(':ed', $entryDate, ($entryDate === null || $entryDate === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmtProf->bindValue(':tc', $tc, ($tc === null || $tc === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmtProf->execute();

        // Best-effort: if student, tag active admission_letter hold with new term_code (only when empty)
        if ($role === 'student' && $tc !== null && $tc !== '') {
            try {
                $holdCols = $pdo->query('SHOW COLUMNS FROM holds')->fetchAll(PDO::FETCH_ASSOC);
                $hasTermCodeCol = false;
                foreach ($holdCols as $c) {
                    if ((string)($c['Field'] ?? '') === 'term_code') {
                        $hasTermCodeCol = true;
                        break;
                    }
                }
                if ($hasTermCodeCol) {
                    $stmtHold = $pdo->prepare(
                        "UPDATE holds
                         SET term_code = :tc
                         WHERE student_id = :sid
                           AND hold_type = 'admission_letter'
                           AND is_active = TRUE
                           AND (term_code IS NULL OR term_code = '')"
                    );
                    $stmtHold->bindParam(':tc', $tc);
                    $stmtHold->bindParam(':sid', $userId);
                    $stmtHold->execute();
                }
            } catch (Exception $e) {
                // ignore
            }
        }
    }

    // Best-effort: update student name fields
    if ($role === 'student' && ($firstName !== null || $lastName !== null)) {
        try {
            $cols = $pdo->query('SHOW COLUMNS FROM student_details')->fetchAll(PDO::FETCH_ASSOC);
            $hasFn = false;
            $hasLn = false;
            foreach ($cols as $c) {
                if ((string)($c['Field'] ?? '') === 'first_name') $hasFn = true;
                if ((string)($c['Field'] ?? '') === 'last_name') $hasLn = true;
            }
            if ($hasFn || $hasLn) {
                $parts = [];
                if ($hasFn && $firstName !== null) $parts[] = "first_name = :fn";
                if ($hasLn && $lastName !== null) $parts[] = "last_name = :ln";
                if (!empty($parts)) {
                    $sql = "UPDATE student_details SET " . implode(', ', $parts) . " WHERE student_id = :sid";
                    $stmtNm = $pdo->prepare($sql);
                    if ($hasFn && $firstName !== null) $stmtNm->bindParam(':fn', $firstName);
                    if ($hasLn && $lastName !== null) $stmtNm->bindParam(':ln', $lastName);
                    $stmtNm->bindParam(':sid', $userId);
                    $stmtNm->execute();
                }
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    $pdo->commit();

    send_json(['status' => 'success', 'message' => 'User updated.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
