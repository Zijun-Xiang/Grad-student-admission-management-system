<?php
require_once __DIR__ . '/../bootstrap.php';
require_method('POST');

include_once '../db.php';

$user = require_login();
if (($user['role'] ?? '') === 'faculty') {
    send_json(['status' => 'error', 'message' => 'Forbidden.'], 403);
}

$data = get_json_input();
$professorId = (string)($data['professor_id'] ?? '');
if ($professorId === '') {
    send_json(['status' => 'error', 'message' => 'Missing data.'], 400);
}

$studentId = effective_student_id_for_request($user, isset($data['student_id']) ? (string)$data['student_id'] : null);

function default_value_for_column(array $col)
{
    $type = strtolower((string)($col['Type'] ?? ''));

    if (preg_match("/^enum\\((.*)\\)$/", $type, $m)) {
        if (preg_match("/'([^']*)'/", (string)$m[1], $m2)) {
            return (string)$m2[1];
        }
        return '';
    }

    if (
        strpos($type, 'int') !== false ||
        strpos($type, 'decimal') !== false ||
        strpos($type, 'float') !== false ||
        strpos($type, 'double') !== false
    ) {
        return 0;
    }

    if (strpos($type, 'datetime') !== false || strpos($type, 'timestamp') !== false) {
        return date('Y-m-d H:i:s');
    }
    if (strpos($type, 'date') !== false) {
        return date('Y-m-d');
    }
    if (strpos($type, 'time') !== false) {
        return date('H:i:s');
    }

    if (strpos($type, 'json') !== false) {
        return '{}';
    }

    return '';
}

try {
    // Ensure student_details row exists for this student (some schemas require explicit insert).
    $stmtExists = $pdo->prepare('SELECT 1 FROM student_details WHERE student_id = :sid LIMIT 1');
    $stmtExists->bindParam(':sid', $studentId);
    $stmtExists->execute();
    $exists = (bool)$stmtExists->fetchColumn();

    if (!$exists) {
        $cols = $pdo->query('SHOW COLUMNS FROM student_details')->fetchAll(PDO::FETCH_ASSOC);
        $values = [];

        foreach ($cols as $col) {
            $field = (string)($col['Field'] ?? '');
            if ($field === 'student_id') $values['student_id'] = $studentId;
            if ($field === 'mp_status') $values['mp_status'] = 'none';
            if ($field === 'major_professor_id') $values['major_professor_id'] = null;
        }

        foreach ($cols as $col) {
            $field = (string)($col['Field'] ?? '');
            $null = (string)($col['Null'] ?? '');
            $default = $col['Default'] ?? null;
            $extra = strtolower((string)($col['Extra'] ?? ''));
            if ($null === 'NO' && $default === null && strpos($extra, 'auto_increment') === false) {
                if (!array_key_exists($field, $values)) {
                    $values[$field] = default_value_for_column($col);
                }
            }
        }

        if (!empty($values)) {
            $columnsSql = implode(', ', array_keys($values));
            $placeholders = implode(', ', array_map(fn ($k) => ':' . $k, array_keys($values)));
            $sql = "INSERT INTO student_details ($columnsSql) VALUES ($placeholders)";
            $stmtIns = $pdo->prepare($sql);
            foreach ($values as $k => $v) {
                if ($v === null) {
                    $stmtIns->bindValue(':' . $k, null, PDO::PARAM_NULL);
                } else {
                    $stmtIns->bindValue(':' . $k, $v);
                }
            }
            $stmtIns->execute();
        }
    }

    $stmt = $pdo->prepare("UPDATE student_details
                           SET major_professor_id = :pid, mp_status = 'pending'
                           WHERE student_id = :sid");
    $stmt->bindParam(':pid', $professorId);
    $stmt->bindParam(':sid', $studentId);
    $ok = $stmt->execute();

    if (!$ok) {
        send_json(['status' => 'error', 'message' => 'Update failed.'], 500);
    }

    send_json(['status' => 'success', 'message' => 'Request sent to professor.']);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
