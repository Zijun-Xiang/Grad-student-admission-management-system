<?php
require_once __DIR__ . '/../bootstrap.php';
require_login();
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/majors_common.php';

$user = current_user() ?: [];
$userId = (string)($user['id'] ?? '');
$role = normalize_role((string)($user['role'] ?? ''));
if ($userId === '') {
    send_json(['status' => 'error', 'message' => 'Invalid session.'], 401);
}

$data = get_json_input();
$email = isset($data['email']) ? trim((string)$data['email']) : null;
$majorCode = isset($data['major_code']) ? normalize_major_code((string)$data['major_code']) : null;
$password = isset($data['password']) ? (string)$data['password'] : '';
$firstName = isset($data['first_name']) ? trim((string)$data['first_name']) : null;
$lastName = isset($data['last_name']) ? trim((string)$data['last_name']) : null;

function has_column(PDO $pdo, string $table, string $col): bool
{
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            if ((string)($c['Field'] ?? '') === $col) return true;
        }
    } catch (Exception $e) {
        return false;
    }
    return false;
}

try {
    // Ensure schema outside transaction to avoid DDL auto-commit issues.
    try {
        ensure_majors_schema($pdo);
    } catch (Exception $e) {
        // ignore
    }

    if ($majorCode !== null) {
        if ($majorCode === '') {
            send_json(['status' => 'error', 'message' => 'Major/program is required.'], 400);
        }
        if (!major_code_exists($pdo, $majorCode)) {
            send_json(['status' => 'error', 'message' => 'Invalid major.'], 400);
        }
    }

    $hasEmail = has_column($pdo, 'users', 'email');
    if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        send_json(['status' => 'error', 'message' => 'Invalid email.'], 400);
    }

    if ($password !== '' && strlen($password) < 6) {
        send_json(['status' => 'error', 'message' => 'Password must be at least 6 characters.'], 400);
    }

    // Student name fields are optional; only update if columns exist.
    $sdHasFirst = $role === 'student' && has_column($pdo, 'student_details', 'first_name');
    $sdHasLast = $role === 'student' && has_column($pdo, 'student_details', 'last_name');

    $pdo->beginTransaction();

    // Update email
    if ($hasEmail && $email !== null) {
        if ($email !== '') {
            $stmtChk = $pdo->prepare("SELECT 1 FROM users WHERE email = :e AND user_id <> :uid LIMIT 1");
            $stmtChk->bindParam(':e', $email);
            $stmtChk->bindParam(':uid', $userId);
            $stmtChk->execute();
            if ($stmtChk->fetchColumn()) {
                $pdo->rollBack();
                send_json(['status' => 'error', 'message' => 'Email already exists.'], 409);
            }
        }

        $stmtUp = $pdo->prepare("UPDATE users SET email = :e WHERE user_id = :uid");
        $stmtUp->bindValue(':e', $email === '' ? null : $email, $email === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmtUp->bindParam(':uid', $userId);
        $stmtUp->execute();
    }

    // Update password (optional)
    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmtPw = $pdo->prepare("UPDATE users SET password = :p WHERE user_id = :uid");
        $stmtPw->bindParam(':p', $hash);
        $stmtPw->bindParam(':uid', $userId);
        $stmtPw->execute();
    }

    // Update major_code (upsert user_profiles)
    if ($majorCode !== null) {
        $stmtProf = $pdo->prepare(
            "INSERT INTO user_profiles (user_id, major_code)
             VALUES (:uid, :mc)
             ON DUPLICATE KEY UPDATE major_code = VALUES(major_code)"
        );
        $stmtProf->bindParam(':uid', $userId);
        $stmtProf->bindParam(':mc', $majorCode);
        $stmtProf->execute();
    }

    // Update student name fields (optional)
    if ($role === 'student' && ($firstName !== null || $lastName !== null) && ($sdHasFirst || $sdHasLast)) {
        // Ensure student_details row exists
        $stmtExists = $pdo->prepare("SELECT 1 FROM student_details WHERE student_id = :sid LIMIT 1");
        $stmtExists->bindParam(':sid', $userId);
        $stmtExists->execute();
        $exists = (bool)$stmtExists->fetchColumn();
        if (!$exists) {
            $stmtIns = $pdo->prepare("INSERT INTO student_details (student_id) VALUES (:sid)");
            $stmtIns->bindParam(':sid', $userId);
            $stmtIns->execute();
        }

        $parts = [];
        if ($sdHasFirst && $firstName !== null) $parts[] = "first_name = :fn";
        if ($sdHasLast && $lastName !== null) $parts[] = "last_name = :ln";
        if (!empty($parts)) {
            $sql = "UPDATE student_details SET " . implode(', ', $parts) . " WHERE student_id = :sid";
            $stmtNm = $pdo->prepare($sql);
            if ($sdHasFirst && $firstName !== null) $stmtNm->bindValue(':fn', $firstName === '' ? null : $firstName, $firstName === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
            if ($sdHasLast && $lastName !== null) $stmtNm->bindValue(':ln', $lastName === '' ? null : $lastName, $lastName === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmtNm->bindParam(':sid', $userId);
            $stmtNm->execute();
        }
    }

    $pdo->commit();

    send_json(['status' => 'success', 'message' => 'Profile updated.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

