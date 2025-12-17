<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);
require_method('POST');

include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';

if (!assignments_tables_ready($pdo)) {
    send_json(['status' => 'error', 'message' => 'Assignments tables not found. Run backend/sql/09_assignments.sql first.'], 500);
}

$facultyId = (string)(current_user()['id'] ?? '');

$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$dueAt = trim((string)($_POST['due_at'] ?? '')); // YYYY-MM-DDTHH:mm
$targetMode = trim((string)($_POST['target_mode'] ?? 'all')); // all | course | students | cohort(legacy)
$courseCode = strtoupper(trim((string)($_POST['course_code'] ?? '')));
$cohort = trim((string)($_POST['cohort_term_code'] ?? '')); // legacy
$studentIdsRaw = (string)($_POST['student_ids'] ?? '[]'); // JSON array

if ($title === '') {
    send_json(['status' => 'error', 'message' => 'Missing title.'], 400);
}
if (!in_array($targetMode, ['all', 'course', 'students', 'cohort'], true)) {
    send_json(['status' => 'error', 'message' => 'Invalid target_mode.'], 400);
}
if ($targetMode === 'course' && $courseCode === '') {
    send_json(['status' => 'error', 'message' => 'Missing course_code.'], 400);
}
if ($targetMode === 'cohort' && $cohort === '') {
    send_json(['status' => 'error', 'message' => 'Missing cohort_term_code.'], 400);
}

$studentIds = [];
if ($targetMode === 'students') {
    $decoded = json_decode($studentIdsRaw, true);
    if (!is_array($decoded) || empty($decoded)) {
        send_json(['status' => 'error', 'message' => 'Select at least one student.'], 400);
    }
    $studentIds = array_values(array_unique(array_map(fn ($v) => trim((string)$v), $decoded)));
    $studentIds = array_values(array_filter($studentIds, fn ($v) => $v !== ''));
    if (empty($studentIds)) {
        send_json(['status' => 'error', 'message' => 'Select at least one student.'], 400);
    }
}

$dueAtSql = null;
if ($dueAt !== '') {
    $ts = strtotime($dueAt);
    if ($ts === false) {
        send_json(['status' => 'error', 'message' => 'Invalid due_at.'], 400);
    }
    $dueAtSql = date('Y-m-d H:i:s', $ts);
}

$attachmentRel = null;
if (isset($_FILES['file']) && is_array($_FILES['file'])) {
    $file = $_FILES['file'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $allowedExt = ['pdf', 'doc', 'docx', 'zip', 'txt', 'jpg', 'jpeg', 'png'];
        $allowedMime = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
            'text/plain',
            'image/jpeg',
            'image/png',
        ];
        $maxBytes = (int)(getenv('UPLOAD_MAX_BYTES') ?: (10 * 1024 * 1024));
        [$ext, $tmpPath] = validate_upload($file, $allowedExt, $allowedMime, $maxBytes);

        $dir = ensure_upload_dir('assignments');
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetFile = $dir . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $targetFile)) {
            send_json(['status' => 'error', 'message' => 'Failed to move file. Permission denied?'], 500);
        }
        $attachmentRel = 'assignments/' . $filename;
    }
}

try {
    if ($targetMode === 'course') {
        // Ensure faculty_courses table exists and this faculty teaches the course.
        $has = $pdo->query("SHOW TABLES LIKE 'faculty_courses'")->fetchColumn();
        if (!$has) {
            send_json(['status' => 'error', 'message' => 'faculty_courses table not found. Add teaching courses first.'], 500);
        }
        $stmtChk = $pdo->prepare("SELECT 1 FROM faculty_courses WHERE faculty_id = :fid AND course_code = :cc LIMIT 1");
        $stmtChk->bindParam(':fid', $facultyId);
        $stmtChk->bindParam(':cc', $courseCode);
        $stmtChk->execute();
        if (!$stmtChk->fetchColumn()) {
            send_json(['status' => 'error', 'message' => 'You can only publish to your own teaching courses.'], 403);
        }
    }

    // For selected-students assignments, only allow targeting your advisees.
    if ($targetMode === 'students') {
        $hasSd = (bool)$pdo->query("SHOW TABLES LIKE 'student_details'")->fetchColumn();
        if ($hasSd) {
            $stmtAdv = $pdo->prepare(
                "SELECT 1
                 FROM student_details sd
                 WHERE sd.student_id = :sid
                   AND sd.major_professor_id = :fid
                   AND sd.mp_status <> 'none'
                 LIMIT 1"
            );
            foreach ($studentIds as $sid) {
                $stmtAdv->bindParam(':sid', $sid);
                $stmtAdv->bindParam(':fid', $facultyId);
                $stmtAdv->execute();
                if (!$stmtAdv->fetchColumn()) {
                    send_json(['status' => 'error', 'message' => 'You can only publish to your own advisees (Selected Students).'], 403);
                }
            }
        }
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO assignments (created_by, title, description, due_at, attachment_path)
         VALUES (:by, :title, :descr, :due, :ap)"
    );
    $stmt->bindParam(':by', $facultyId);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':descr', $description);
    $stmt->bindValue(':due', $dueAtSql, $dueAtSql === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':ap', $attachmentRel, $attachmentRel === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->execute();

    $assignmentId = (int)$pdo->lastInsertId();

    if ($targetMode === 'all') {
        $t = $pdo->prepare("INSERT INTO assignment_targets (assignment_id, target_type, target_value) VALUES (:aid, 'all', NULL)");
        $t->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
        $t->execute();
    } elseif ($targetMode === 'course') {
        $t = $pdo->prepare("INSERT INTO assignment_targets (assignment_id, target_type, target_value) VALUES (:aid, 'course', :val)");
        $t->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
        $t->bindParam(':val', $courseCode);
        $t->execute();
    } elseif ($targetMode === 'cohort') {
        $t = $pdo->prepare("INSERT INTO assignment_targets (assignment_id, target_type, target_value) VALUES (:aid, 'cohort', :val)");
        $t->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
        $t->bindParam(':val', $cohort);
        $t->execute();
    } else {
        $t = $pdo->prepare("INSERT INTO assignment_targets (assignment_id, target_type, target_value) VALUES (:aid, 'student', :val)");
        foreach ($studentIds as $sid) {
            $t->bindParam(':aid', $assignmentId, PDO::PARAM_INT);
            $t->bindParam(':val', $sid);
            $t->execute();
        }
    }

    $pdo->commit();

    send_json([
        'status' => 'success',
        'message' => 'Assignment created.',
        'assignment_id' => $assignmentId,
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($attachmentRel) {
        $uploadsRoot = realpath(__DIR__ . '/../uploads');
        $fullPath = $uploadsRoot ? realpath($uploadsRoot . DIRECTORY_SEPARATOR . $attachmentRel) : false;
        if ($uploadsRoot && $fullPath && strncmp($fullPath, $uploadsRoot, strlen($uploadsRoot)) === 0 && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
