<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['admin']);

include_once '../db.php';

$termCode = isset($_GET['term_code']) ? trim((string)$_GET['term_code']) : '';

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
    $userCols = table_columns($pdo, 'users');
    $holdsCols = table_columns($pdo, 'holds');
    $docsCols = table_columns($pdo, 'documents');

    $usersIdCol = pick_first_existing_column($userCols, ['user_id', 'id']) ?: 'user_id';
    $holdsPkCol = pick_first_existing_column($holdsCols, ['id', 'hold_id']);
    $hasTermCodeCol = pick_first_existing_column($holdsCols, ['term_code']) !== null;
    $docsDateCol = pick_first_existing_column($docsCols, ['upload_date', 'created_at']) ?: 'doc_id';

    // Active holds (LEFT JOIN so admin can still see holds even if user row is missing)
    $holdsSql = "SELECT ";
    if ($holdsPkCol !== null) {
        $holdsSql .= "h.`$holdsPkCol` AS hold_id, ";
    } else {
        $holdsSql .= "NULL AS hold_id, ";
    }
    $holdsSql .= "h.student_id, u.username AS student_username, h.hold_type";
    if ($hasTermCodeCol) {
        $holdsSql .= ", h.term_code";
    }
    $holdsSql .= "
        FROM holds h
        LEFT JOIN users u ON u.`$usersIdCol` = h.student_id
        WHERE h.is_active = TRUE";

    if ($hasTermCodeCol && $termCode !== '') {
        $holdsSql .= " AND h.term_code = :term";
    }
    $holdsSql .= " ORDER BY h.student_id ASC, h.hold_type ASC";

    $stmtHolds = $pdo->prepare($holdsSql);
    if ($hasTermCodeCol && $termCode !== '') {
        $stmtHolds->bindParam(':term', $termCode);
    }
    $stmtHolds->execute();
    $holds = $stmtHolds->fetchAll(PDO::FETCH_ASSOC);

    // Pending documents
    $docsSql = "SELECT d.doc_id, d.student_id, u.username AS student_username, d.doc_type, d.file_path, d.status";
    if ($docsDateCol !== 'doc_id') {
        $docsSql .= ", d.`$docsDateCol` AS upload_date";
    }
    $docsSql .= "
        FROM documents d
        LEFT JOIN users u ON u.`$usersIdCol` = d.student_id
        WHERE d.status = 'pending'
        ORDER BY d.`$docsDateCol` ASC";

    $stmtDocs = $pdo->prepare($docsSql);
    $stmtDocs->execute();
    $pendingDocs = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

    send_json([
        'status' => 'success',
        'holds' => $holds,
        'pending_documents' => $pendingDocs,
        'term_code' => $termCode !== '' ? $termCode : null,
    ]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
