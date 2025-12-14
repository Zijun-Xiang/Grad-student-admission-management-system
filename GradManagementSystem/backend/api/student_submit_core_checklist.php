<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['student']);
require_method('POST');

include_once '../db.php';

$user = current_user();
$studentId = (string)($user['id'] ?? '');

$data = get_json_input();
$completed = $data['completed_courses'] ?? null;

if (!is_array($completed)) {
    send_json(['status' => 'error', 'message' => 'completed_courses must be an array.'], 400);
}

function ensure_student_deficiencies_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS student_deficiencies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            student_id BIGINT UNSIGNED NOT NULL,
            course_code VARCHAR(32) NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'assigned',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_student_def (student_id, course_code),
            KEY idx_student_def_student (student_id),
            KEY idx_student_def_status (status)
        )"
    );
}

function ensure_core_checklist_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS student_core_course_checklists (
            student_id BIGINT UNSIGNED NOT NULL,
            completed_codes TEXT NULL,
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (student_id)
        )"
    );
}

function core_course_columns(PDO $pdo): array
{
    try {
        return $pdo->query("SHOW COLUMNS FROM core_courses")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function has_core_col(array $cols, string $name): bool
{
    foreach ($cols as $c) {
        if ((string)($c['Field'] ?? '') === $name) return true;
    }
    return false;
}

try {
    // Must have admission letter approved first
    $stmtOk = $pdo->prepare(
        "SELECT 1 FROM documents
         WHERE student_id = :sid AND doc_type = 'admission_letter' AND status = 'approved'
         LIMIT 1"
    );
    $stmtOk->bindParam(':sid', $studentId);
    $stmtOk->execute();
    if (!$stmtOk->fetchColumn()) {
        send_json(['status' => 'error', 'message' => 'Admission letter must be approved before setting deficiencies.'], 403);
    }

    $cols = core_course_columns($pdo);
    $hasLevel = has_core_col($cols, 'level');
    $hasRequired = has_core_col($cols, 'is_required');

    if (!$hasLevel || !$hasRequired) {
        send_json([
            'status' => 'error',
            'message' => 'core_courses is missing required columns (level, is_required). Run backend/sql/07_core_courses_seed.sql first.',
        ], 500);
    }

    $where = [];
    if ($hasLevel) $where[] = "level = 'UG'";
    if ($hasRequired) $where[] = "is_required = 1";

    $sql = "SELECT course_code FROM core_courses";
    if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $required = array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    if (empty($required)) {
        send_json(['status' => 'error', 'message' => 'No core courses found. Please seed core_courses first (backend/sql/07_core_courses_seed.sql).'], 500);
    }

    $requiredSet = array_fill_keys($required, true);
    $completedClean = [];
    foreach ($completed as $code) {
        $code = trim((string)$code);
        if ($code === '') continue;
        if (!isset($requiredSet[$code])) continue;
        $completedClean[$code] = true;
    }
    $completedCodes = array_keys($completedClean);

    $missing = [];
    foreach ($required as $code) {
        if (!isset($completedClean[$code])) $missing[] = $code;
    }

    ensure_student_deficiencies_table($pdo);
    ensure_core_checklist_table($pdo);

    $pdo->beginTransaction();

    $json = json_encode(array_values($completedCodes));
    $stmtUp = $pdo->prepare(
        "INSERT INTO student_core_course_checklists (student_id, completed_codes, submitted_at)
         VALUES (:sid, :codes, NOW())
         ON DUPLICATE KEY UPDATE completed_codes = VALUES(completed_codes), submitted_at = NOW()"
    );
    $stmtUp->bindParam(':sid', $studentId);
    $stmtUp->bindParam(':codes', $json);
    $stmtUp->execute();

    // Reset previously assigned deficiencies (so re-submission fixes any prior data).
    $stmtDel = $pdo->prepare(
        "DELETE FROM student_deficiencies
         WHERE student_id = :sid
           AND status = 'assigned'"
    );
    $stmtDel->bindParam(':sid', $studentId);
    $stmtDel->execute();

    $stmtIns = $pdo->prepare(
        "INSERT INTO student_deficiencies (student_id, course_code, status)
         VALUES (:sid, :code, 'assigned')
         ON DUPLICATE KEY UPDATE status = 'assigned'"
    );
    foreach ($missing as $code) {
        $stmtIns->bindParam(':sid', $studentId);
        $stmtIns->bindParam(':code', $code);
        $stmtIns->execute();
    }

    $pdo->commit();

    send_json([
        'status' => 'success',
        'message' => 'Core course checklist submitted.',
        'completed' => $completedCodes,
        'missing' => $missing,
        'assigned_deficiencies' => count($missing),
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
