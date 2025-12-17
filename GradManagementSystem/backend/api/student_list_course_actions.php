<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['student']);

include_once '../db.php';
require_once __DIR__ . '/course_actions_common.php';

$studentId = (string)(current_user()['id'] ?? '');

try {
    ensure_advisee_course_actions_table($pdo);

    $stmt = $pdo->prepare(
        "SELECT a.id,
                a.action_type,
                a.course_code,
                cc.course_name,
                cc.credits,
                a.comment,
                a.status,
                a.created_at,
                a.applied_at,
                a.rejected_at,
                a.student_comment,
                u.username AS faculty_username
         FROM advisee_course_actions a
         JOIN users u ON u.user_id = a.faculty_id
         LEFT JOIN core_courses cc ON cc.course_code = a.course_code
         WHERE a.student_id = :sid
           AND a.status IN ('pending','applied','rejected')
         ORDER BY (a.status = 'pending') DESC, a.created_at DESC, a.id DESC
         LIMIT 50"
    );
    $stmt->bindParam(':sid', $studentId);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pending = [];
    $history = [];
    foreach ($rows as $r) {
        if (($r['status'] ?? '') === 'pending') $pending[] = $r;
        else $history[] = $r;
    }

    send_json(['status' => 'success', 'pending' => $pending, 'history' => $history]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
