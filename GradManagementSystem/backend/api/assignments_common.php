<?php
// Shared helpers for assignment APIs.

function assignments_tables_ready(PDO $pdo): bool
{
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'assignments'");
        return (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

function ensure_user_profiles_table_for_assignments(PDO $pdo): void
{
    try {
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
    } catch (Exception $e) {
        // ignore
    }
}

function get_student_entry_term_code(PDO $pdo, string $studentId): ?string
{
    $studentId = trim($studentId);
    if ($studentId === '') return null;

    ensure_user_profiles_table_for_assignments($pdo);

    try {
        $stmt = $pdo->prepare("SELECT entry_term_code, entry_date FROM user_profiles WHERE user_id = :uid LIMIT 1");
        $stmt->bindParam(':uid', $studentId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $tc = strtoupper(trim((string)($row['entry_term_code'] ?? '')));
            if ($tc !== '') return $tc;
            $ed = (string)($row['entry_date'] ?? '');
            if ($ed !== '') {
                $fromDate = grad_term_code_from_date($ed);
                if ($fromDate) return $fromDate;
            }
        }
    } catch (Exception $e) {
        // ignore
    }

    try {
        $stmt = $pdo->prepare("SELECT entry_term_code FROM student_details WHERE student_id = :sid LIMIT 1");
        $stmt->bindParam(':sid', $studentId);
        $stmt->execute();
        $tc = $stmt->fetchColumn();
        if ($tc !== false && $tc !== null && trim((string)$tc) !== '') return strtoupper(trim((string)$tc));
    } catch (Exception $e) {
        // ignore
    }

    return null;
}

function ensure_upload_dir(string $relativeDir): string
{
    $base = __DIR__ . '/../uploads';
    if (!is_dir($base)) mkdir($base, 0777, true);

    $dir = $base . '/' . trim($relativeDir, '/');
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    return $dir;
}

function validate_upload(array $file, array $allowedExt, array $allowedMime, int $maxBytes): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        send_json(['status' => 'error', 'message' => 'File upload error code: ' . (string)($file['error'] ?? '')], 400);
    }
    if ((int)($file['size'] ?? 0) > $maxBytes) {
        send_json(['status' => 'error', 'message' => 'File too large.'], 400);
    }

    $originalName = (string)($file['name'] ?? 'upload');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        send_json(['status' => 'error', 'message' => 'Unsupported file type.'], 400);
    }

    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_file($tmpPath)) {
        send_json(['status' => 'error', 'message' => 'Invalid upload.'], 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath);
    if ($mime === false || !in_array($mime, $allowedMime, true)) {
        send_json(['status' => 'error', 'message' => 'Invalid file content type.'], 400);
    }

    return [$ext, $tmpPath, $mime];
}

function assignment_student_has_access(PDO $pdo, string $studentId, int $assignmentId): bool
{
    $entryTerm = get_student_entry_term_code($pdo, $studentId) ?: '';

    $stmt = $pdo->prepare(
        "SELECT 1
         FROM assignment_targets t
         WHERE t.assignment_id = :aid
           AND (
             t.target_type = 'all'
             OR (t.target_type = 'student' AND t.target_value = :sid)
             -- Backward compatibility: old cohort target
             OR (t.target_type = 'cohort' AND t.target_value = :cohort)
             -- New: course target, matches if student registered for that course
             OR (
                t.target_type = 'course'
                AND EXISTS (
                    SELECT 1
                    FROM student_registrations sr
                    WHERE sr.student_id = :sid_course
                      AND sr.course_code = t.target_value
                    LIMIT 1
                )
             )
           )
         LIMIT 1"
    );
    $stmt->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
    $stmt->bindParam(':sid', $studentId);
    $stmt->bindParam(':cohort', $entryTerm);
    $stmt->bindParam(':sid_course', $studentId);
    $stmt->execute();
    return (bool)$stmt->fetchColumn();
}

function ensure_assignment_grading_columns(PDO $pdo): void
{
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM assignment_submissions")->fetchAll(PDO::FETCH_ASSOC);
        $hasGrade = false;
        $hasGradedAt = false;
        $hasGradedBy = false;
        foreach ($cols as $c) {
            $f = (string)($c['Field'] ?? '');
            if ($f === 'grade') $hasGrade = true;
            if ($f === 'graded_at') $hasGradedAt = true;
            if ($f === 'graded_by') $hasGradedBy = true;
        }

        if (!$hasGrade) {
            $pdo->exec("ALTER TABLE assignment_submissions ADD COLUMN grade DECIMAL(6,2) NULL");
        }
        if (!$hasGradedAt) {
            $pdo->exec("ALTER TABLE assignment_submissions ADD COLUMN graded_at DATETIME NULL");
        }
        if (!$hasGradedBy) {
            $pdo->exec("ALTER TABLE assignment_submissions ADD COLUMN graded_by BIGINT UNSIGNED NULL");
        }
    } catch (Exception $e) {
        // Best-effort only.
    }
}

function assignment_grading_enabled(PDO $pdo): bool
{
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM assignment_submissions")->fetchAll(PDO::FETCH_ASSOC);
        $need = ['grade' => false, 'graded_at' => false, 'graded_by' => false];
        foreach ($cols as $c) {
            $f = (string)($c['Field'] ?? '');
            if (array_key_exists($f, $need)) $need[$f] = true;
        }
        return $need['grade'] && $need['graded_at'] && $need['graded_by'];
    } catch (Exception $e) {
        return false;
    }
}
