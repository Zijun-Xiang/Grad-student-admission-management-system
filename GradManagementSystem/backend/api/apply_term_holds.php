<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);
require_method('POST');

include_once '../db.php';

$data = get_json_input();
$termCode = (string)($data['term_code'] ?? '');
$stage = (int)($data['stage'] ?? 0); // 1,2,3
$onlyStudentId = isset($data['student_id']) ? (string)$data['student_id'] : '';

if ($termCode === '' || !in_array($stage, [1, 2, 3], true)) {
    send_json(['status' => 'error', 'message' => 'Missing/invalid term_code or stage (1/2/3).'], 400);
}

$researchMethodCourseCode = getenv('RESEARCH_METHOD_COURSE_CODE') ?: 'CS690';

$holdType = match ($stage) {
    1 => 'admission_letter',
    2 => 'major_professor_form',
    3 => 'research_method',
};

try {
    $studentsSql = "SELECT user_id FROM users WHERE role = 'student'";
    if ($onlyStudentId !== '') {
        $studentsSql .= " AND user_id = :sid";
    }
    $stmtStudents = $pdo->prepare($studentsSql);
    if ($onlyStudentId !== '') {
        $stmtStudents->bindParam(':sid', $onlyStudentId);
    }
    $stmtStudents->execute();
    $studentIds = $stmtStudents->fetchAll(PDO::FETCH_COLUMN);

    $created = 0;
    $skipped = 0;

    foreach ($studentIds as $sid) {
        $sid = (string)$sid;

        // Check whether requirement is already satisfied for this stage.
        $satisfied = false;
        if ($stage === 1) {
            $stmtOk = $pdo->prepare(
                "SELECT 1 FROM documents
                 WHERE student_id = :sid AND doc_type = 'admission_letter' AND status = 'approved'
                 LIMIT 1"
            );
            $stmtOk->bindParam(':sid', $sid);
            $stmtOk->execute();
            $satisfied = (bool)$stmtOk->fetchColumn();
        } elseif ($stage === 2) {
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
        'message' => 'Term holds applied.',
        'term_code' => $termCode,
        'stage' => $stage,
        'hold_type' => $holdType,
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

