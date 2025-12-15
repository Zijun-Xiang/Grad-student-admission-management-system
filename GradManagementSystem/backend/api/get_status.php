<?php
require_once __DIR__ . '/../bootstrap.php';

include_once '../db.php';

$user = require_login();
$studentId = effective_student_id_for_request($user, isset($_GET['student_id']) ? (string)$_GET['student_id'] : null);

try {
    $stmtHolds = $pdo->prepare('SELECT * FROM holds WHERE student_id = :sid AND is_active = TRUE');
    $stmtHolds->bindParam(':sid', $studentId);
    $stmtHolds->execute();
    $holds = $stmtHolds->fetchAll(PDO::FETCH_ASSOC);

    $stmtDocs = $pdo->prepare('SELECT * FROM documents WHERE student_id = :sid ORDER BY upload_date DESC');
    $stmtDocs->bindParam(':sid', $studentId);
    $stmtDocs->execute();
    $documents = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

    $queryDef = "SELECT sd.course_code, cc.course_name, sd.status
                 FROM student_deficiencies sd
                 JOIN core_courses cc ON sd.course_code = cc.course_code
                 WHERE sd.student_id = :sid";
    $stmtDef = $pdo->prepare($queryDef);
    $stmtDef->bindParam(':sid', $studentId);
    $stmtDef->execute();
    $deficiencies = $stmtDef->fetchAll(PDO::FETCH_ASSOC);

    $queryMp = "SELECT sd.mp_status, u.username as prof_name
                FROM student_details sd
                LEFT JOIN users u ON sd.major_professor_id = u.user_id
                WHERE sd.student_id = :sid";
    $stmtMp = $pdo->prepare($queryMp);
    $stmtMp->bindParam(':sid', $studentId);
    $stmtMp->execute();
    $mpInfo = $stmtMp->fetch(PDO::FETCH_ASSOC);

    // Entry/admission profile (optional)
    $profile = null;
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
        $stmtProf = $pdo->prepare("SELECT entry_date, entry_term_code FROM user_profiles WHERE user_id = :uid LIMIT 1");
        $stmtProf->bindParam(':uid', $studentId);
        $stmtProf->execute();
        $row = $stmtProf->fetch(PDO::FETCH_ASSOC);
        if ($row) $profile = $row;
    } catch (Exception $e) {
        $profile = null;
    }

    // Term info for gating (server-side)
    $currentTermCode = grad_current_term_code();
    $entryDate = is_array($profile) ? (string)($profile['entry_date'] ?? '') : '';
    $entryTermCode = is_array($profile) ? (string)($profile['entry_term_code'] ?? '') : '';

    if ($entryTermCode === '') {
        // Best-effort fallback to student_details.entry_term_code if available.
        try {
            $stmtEntry = $pdo->prepare("SELECT entry_term_code FROM student_details WHERE student_id = :sid LIMIT 1");
            $stmtEntry->bindParam(':sid', $studentId);
            $stmtEntry->execute();
            $tc = $stmtEntry->fetchColumn();
            if ($tc !== false && $tc !== null) $entryTermCode = (string)$tc;
        } catch (Exception $e) {
            // ignore
        }
    }
    if ($entryTermCode === '' && $entryDate !== '') {
        $entryTermCode = grad_term_code_from_date($entryDate) ?: '';
    }
    if ($entryTermCode === '') {
        $entryTermCode = $currentTermCode;
    }

    $termNumber = grad_term_number($entryTermCode, $currentTermCode);
    $term = [
        'current_term_code' => $currentTermCode,
        'entry_term_code' => $entryTermCode,
        'entry_date' => $entryDate,
        'term_number' => $termNumber,
        'unlocks' => [
            'term2' => $termNumber >= 2,
            'term3' => $termNumber >= 3,
            'term4' => $termNumber >= 4,
            'term5' => $termNumber >= 5,
        ],
    ];

    // Core course checklist (optional)
    $coreChecklist = null;
    try {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS student_core_course_checklists (
                student_id BIGINT UNSIGNED NOT NULL,
                completed_codes TEXT NULL,
                submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (student_id)
            )"
        );
        $stmtC = $pdo->prepare("SELECT submitted_at, completed_codes FROM student_core_course_checklists WHERE student_id = :sid LIMIT 1");
        $stmtC->bindParam(':sid', $studentId);
        $stmtC->execute();
        $row = $stmtC->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $coreChecklist = [
                'submitted_at' => $row['submitted_at'] ?? null,
            ];
        }
    } catch (Exception $e) {
        $coreChecklist = null;
    }

    send_json([
        'status' => 'success',
        'holds' => $holds,
        'documents' => $documents,
        'deficiencies' => $deficiencies,
        'mp_info' => $mpInfo,
        'profile' => $profile,
        'term' => $term,
        'core_checklist' => $coreChecklist,
    ]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
