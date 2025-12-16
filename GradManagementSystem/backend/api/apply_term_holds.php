<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty', 'admin']);
require_method('POST');

include_once '../db.php';

$data = get_json_input();
$termCode = trim((string)($data['term_code'] ?? ''));
$stage = (int)($data['stage'] ?? 0); // 0=auto, or 1/2/3
$onlyStudentId = isset($data['student_id']) ? (string)$data['student_id'] : '';

if ($termCode === '') {
    $termCode = grad_current_term_code();
}
if (!in_array($stage, [0, 1, 2, 3], true)) {
    send_json(['status' => 'error', 'message' => 'Invalid stage (use 0=auto or 1/2/3).'], 400);
}

$researchMethodCourseCode = getenv('RESEARCH_METHOD_COURSE_CODE') ?: 'CS690';

function hold_type_for_stage(int $stage): string
{
    return match ($stage) {
        1 => 'admission_letter',
        2 => 'major_professor_form',
        3 => 'research_method',
        default => 'admission_letter',
    };
}

try {
    // Best-effort: ensure user_profiles exists (so we can read entry_term_code).
    try {
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
    } catch (Exception $e) {
        // ignore
    }

    $studentsSql = "SELECT u.user_id,
                           up.entry_term_code AS entry_term_code,
                           up.entry_date
                    FROM users u
                    LEFT JOIN user_profiles up ON up.user_id = u.user_id
                    WHERE u.role = 'student'";
    if ($onlyStudentId !== '') $studentsSql .= " AND u.user_id = :sid";

    $stmtStudents = $pdo->prepare($studentsSql);
    if ($onlyStudentId !== '') $stmtStudents->bindParam(':sid', $onlyStudentId);
    $stmtStudents->execute();
    $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);

    $created = 0;
    $skipped = 0;

    foreach ($students as $row) {
        $sid = (string)($row['user_id'] ?? '');
        if ($sid === '') continue;

        $entryTermCode = strtoupper(trim((string)($row['entry_term_code'] ?? '')));
        $entryDate = (string)($row['entry_date'] ?? '');
        if ($entryTermCode === '' && $entryDate !== '') {
            $entryTermCode = grad_term_code_from_date($entryDate) ?: '';
        }
        if ($entryTermCode === '') $entryTermCode = $termCode;

        $autoStage = min(3, grad_term_number($entryTermCode, $termCode));
        $effectiveStage = ($stage === 0) ? $autoStage : $stage;
        if ($effectiveStage < 1) {
            $skipped++;
            continue;
        }

        $holdType = hold_type_for_stage($effectiveStage);

        // Check whether requirement is already satisfied for this stage.
        $satisfied = false;
        if ($holdType === 'admission_letter') {
            $stmtOk = $pdo->prepare(
                "SELECT 1 FROM documents
                 WHERE student_id = :sid AND doc_type = 'admission_letter' AND status = 'approved'
                 LIMIT 1"
            );
            $stmtOk->bindParam(':sid', $sid);
            $stmtOk->execute();
            $satisfied = (bool)$stmtOk->fetchColumn();
        } elseif ($holdType === 'major_professor_form') {
            $stmtOk = $pdo->prepare(
                "SELECT 1 FROM documents
                 WHERE student_id = :sid AND doc_type = 'major_professor_form' AND status = 'approved'
                 LIMIT 1"
            );
            $stmtOk->bindParam(':sid', $sid);
            $stmtOk->execute();
            $satisfied = (bool)$stmtOk->fetchColumn();
        } else {
            $stmtOk = $pdo->prepare(
                "SELECT 1 FROM student_registrations
                 WHERE student_id = :sid AND course_code = :code
                 LIMIT 1"
            );
            $stmtOk->bindParam(':sid', $sid);
            $stmtOk->bindParam(':code', $researchMethodCourseCode);
            $stmtOk->execute();
            $satisfied = (bool)$stmtOk->fetchColumn();
        }

        if ($satisfied) {
            $skipped++;
            continue;
        }

        // Insert hold if not already active for this term.
        $stmtExists = $pdo->prepare(
            "SELECT 1 FROM holds
             WHERE student_id = :sid AND hold_type = :ht AND is_active = TRUE AND term_code = :term
             LIMIT 1"
        );
        $stmtExists->bindParam(':sid', $sid);
        $stmtExists->bindParam(':ht', $holdType);
        $stmtExists->bindParam(':term', $termCode);
        $stmtExists->execute();
        if ($stmtExists->fetchColumn()) {
            $skipped++;
            continue;
        }

        $stmtIns = $pdo->prepare(
            "INSERT INTO holds (student_id, hold_type, is_active, term_code)
             VALUES (:sid, :ht, TRUE, :term)"
        );
        $stmtIns->bindParam(':sid', $sid);
        $stmtIns->bindParam(':ht', $holdType);
        $stmtIns->bindParam(':term', $termCode);
        $stmtIns->execute();
        $created++;
    }

    send_json([
        'status' => 'success',
        'message' => $stage === 0 ? 'Term holds applied (auto by student term).' : 'Term holds applied.',
        'term_code' => $termCode,
        'stage' => $stage,
        'created' => $created,
        'skipped' => $skipped,
    ]);
} catch (PDOException $e) {
    // Common failure: holds table does not have term_code column yet.
    send_json([
        'status' => 'error',
        'message' => 'Failed to apply holds. Ensure holds table has term_code column (run backend/sql/00_holds_term_code.sql). ' . $e->getMessage(),
    ], 500);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
