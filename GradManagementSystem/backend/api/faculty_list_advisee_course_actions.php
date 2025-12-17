<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';
require_once __DIR__ . '/course_actions_common.php';

$facultyId = (string)(current_user()['id'] ?? '');
$studentId = (string)($_GET['student_id'] ?? '');
if ($studentId === '') {
    send_json(['status' => 'error', 'message' => 'Missing student_id.'], 400);
}

try {
    ensure_advisee_course_actions_table($pdo);

    if (!faculty_owns_advisee($pdo, $facultyId, $studentId)) {
        send_json(['status' => 'error', 'message' => 'Forbidden (not your advisee).'], 403);
    }

    $stmt = $pdo->prepare(
        "SELECT a.id,
                a.action_type,
                a.course_code,
                cc.course_name,
                cc.credits,
                a.comment,
                a.student_comment,
                a.status,
                a.created_at,
                a.applied_at,
                a.cancelled_at,
                a.rejected_at
         FROM advisee_course_actions a
         LEFT JOIN core_courses cc ON cc.course_code = a.course_code
         WHERE a.faculty_id = :fid
           AND a.student_id = :sid
         ORDER BY a.created_at DESC, a.id DESC
         LIMIT 50"
    );
    $stmt->bindParam(':fid', $facultyId);
    $stmt->bindParam(':sid', $studentId);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_json(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
