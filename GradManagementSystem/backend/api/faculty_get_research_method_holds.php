<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';

$facultyId = (string)(current_user()['id'] ?? '');
$researchMethodCourseCode = getenv('RESEARCH_METHOD_COURSE_CODE') ?: 'CS690';

function table_columns(PDO $pdo, string $table): array
{
    try {
        return $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function pick_first_existing_column(array $cols, array $candidates): ?string
{
    $available = [];
    foreach ($cols as $c) {
        $available[] = (string)($c['Field'] ?? '');
    }
    foreach ($candidates as $cand) {
        if (in_array($cand, $available, true)) return $cand;
    }
    return null;
}

try {
    $holdsCols = table_columns($pdo, 'holds');
    $docsCols = table_columns($pdo, 'documents');
    $hasTermCode = pick_first_existing_column($holdsCols, ['term_code']) !== null;
    $docsDateCol = pick_first_existing_column($docsCols, ['upload_date', 'created_at']) ?: 'doc_id';

    $stmt = $pdo->prepare(
        "SELECT h.student_id,
                su.username AS student_username,
                " . ($hasTermCode ? "h.term_code," : "NULL AS term_code,") . "
                EXISTS(
                    SELECT 1 FROM student_registrations sr
                    WHERE sr.student_id = h.student_id AND sr.course_code = :code
                    LIMIT 1
                ) AS has_research_method,
                (
                    SELECT d.doc_id
                    FROM documents d
                    WHERE d.student_id = h.student_id AND d.doc_type = 'research_method_proof'
                    ORDER BY d.`$docsDateCol` DESC, d.doc_id DESC
                    LIMIT 1
                ) AS proof_doc_id,
                (
                    SELECT d.status
                    FROM documents d
                    WHERE d.student_id = h.student_id AND d.doc_type = 'research_method_proof'
                    ORDER BY d.`$docsDateCol` DESC, d.doc_id DESC
                    LIMIT 1
                ) AS proof_status,
                (
                    SELECT d.`$docsDateCol`
                    FROM documents d
                    WHERE d.student_id = h.student_id AND d.doc_type = 'research_method_proof'
                    ORDER BY d.`$docsDateCol` DESC, d.doc_id DESC
                    LIMIT 1
                ) AS proof_upload_date
         FROM holds h
         JOIN student_details sd ON sd.student_id = h.student_id
         LEFT JOIN users su ON su.user_id = h.student_id
         WHERE h.is_active = TRUE
           AND h.hold_type = 'research_method'
           AND sd.major_professor_id = :fid
           AND (sd.mp_status = 'approved' OR sd.mp_status = 'accepted')
         ORDER BY " . ($hasTermCode ? "h.term_code DESC," : "") . " h.student_id ASC"
    );
    $stmt->bindParam(':fid', $facultyId);
    $stmt->bindParam(':code', $researchMethodCourseCode);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $rows, 'course_code' => $researchMethodCourseCode]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
