<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';
require_once __DIR__ . '/assignments_common.php';

if (!assignments_tables_ready($pdo)) {
    send_json(['status' => 'error', 'message' => 'Assignments tables not found. Run backend/sql/09_assignments.sql first.'], 500);
}

$facultyId = (string)(current_user()['id'] ?? '');

try {
    $stmt = $pdo->prepare(
        "SELECT a.id,
                a.title,
                a.description,
                a.due_at,
                a.attachment_path,
                a.created_at,
                (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id = a.id) AS submissions_count
         FROM assignments a
         WHERE a.created_by = :by
         ORDER BY a.created_at DESC, a.id DESC"
    );
    $stmt->bindParam(':by', $facultyId);
    $stmt->execute();
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Targets per assignment
    $ids = array_map(fn ($r) => (int)($r['id'] ?? 0), $assignments);
    $targetsBy = [];
    if (!empty($ids)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmtT = $pdo->prepare("SELECT assignment_id, target_type, target_value FROM assignment_targets WHERE assignment_id IN ($in)");
        foreach ($ids as $i => $id) $stmtT->bindValue($i + 1, $id, PDO::PARAM_INT);
        $stmtT->execute();
        $rows = $stmtT->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $t) {
            $aid = (int)($t['assignment_id'] ?? 0);
            if (!isset($targetsBy[$aid])) $targetsBy[$aid] = [];
            $targetsBy[$aid][] = [
                'target_type' => (string)($t['target_type'] ?? ''),
                'target_value' => $t['target_value'] ?? null,
            ];
        }
    }

    foreach ($assignments as &$a) {
        $aid = (int)($a['id'] ?? 0);
        $a['targets'] = $targetsBy[$aid] ?? [];
    }

    send_json(['status' => 'success', 'data' => $assignments]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}

