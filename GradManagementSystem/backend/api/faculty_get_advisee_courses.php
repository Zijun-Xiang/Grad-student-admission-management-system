<?php
require_once __DIR__ . '/../bootstrap.php';
require_login(['faculty']);

include_once '../db.php';

$facultyId = (string)(current_user()['id'] ?? '');
$studentId = (string)($_GET['student_id'] ?? '');
$username = trim((string)($_GET['username'] ?? ''));
if ($studentId === '') {
    send_json(['status' => 'error', 'message' => 'Missing student_id.'], 400);
}

function table_exists(PDO $pdo, string $table): bool
{
    try {
        return (bool)$pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

function column_exists(PDO $pdo, string $table, string $col): bool
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col));
        return (bool)$stmt->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

function users_pk_col(PDO $pdo): string
{
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            $f = (string)($c['Field'] ?? '');
            if ($f === 'user_id') return 'user_id';
        }
        foreach ($cols as $c) {
            $f = (string)($c['Field'] ?? '');
            if ($f === 'id') return 'id';
        }
    } catch (Exception $e) {
        // ignore
    }
    return 'user_id';
}

function pick_existing_columns(PDO $pdo, string $table, array $wanted): array
{
    $existing = [];
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        $set = [];
        foreach ($cols as $c) {
            $f = (string)($c['Field'] ?? '');
            if ($f !== '') $set[$f] = true;
        }
        foreach ($wanted as $w) {
            $w = (string)$w;
            if (isset($set[$w])) $existing[] = $w;
        }
    } catch (Exception $e) {
        // ignore
    }
    return $existing;
}

function normalize_candidate_ids(array $ids): array
{
    $out = [];
    foreach ($ids as $id) {
        $s = trim((string)$id);
        if ($s === '') continue;
        if (!preg_match('/^\d+$/', $s)) continue;
        $out[$s] = true;
    }
    return array_keys($out);
}

function normalize_candidate_usernames(array $names): array
{
    $out = [];
    foreach ($names as $n) {
        $s = strtolower(trim((string)$n));
        if ($s === '') continue;
        $out[$s] = true;
    }
    return array_keys($out);
}

function get_column_type(PDO $pdo, string $table, string $col): ?string
{
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($col));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $type = is_array($row) ? (string)($row['Type'] ?? '') : '';
        return $type !== '' ? strtolower($type) : null;
    } catch (Exception $e) {
        return null;
    }
}

function is_stringish_mysql_type(?string $type): bool
{
    if ($type === null) return false;
    $t = strtolower($type);
    return (strpos($t, 'char') !== false) || (strpos($t, 'text') !== false) || (strpos($t, 'blob') !== false) || (strpos($t, 'binary') !== false);
}

try {
    if (!table_exists($pdo, 'student_details')) {
        send_json(['status' => 'error', 'message' => 'student_details table not found.'], 500);
    }
    if (!table_exists($pdo, 'student_registrations')) {
        send_json(['status' => 'error', 'message' => 'student_registrations table not found.'], 500);
    }
    if (!table_exists($pdo, 'core_courses')) {
        send_json(['status' => 'error', 'message' => 'core_courses table not found.'], 500);
    }

    // Verify this student is an advisee of the current faculty.
    $stmtCheck = $pdo->prepare(
        "SELECT 1
         FROM student_details sd
         WHERE sd.student_id = :sid
           AND sd.major_professor_id = :fid
           AND sd.mp_status <> 'none'
         LIMIT 1"
    );
    $stmtCheck->bindParam(':sid', $studentId);
    $stmtCheck->bindParam(':fid', $facultyId);
    $stmtCheck->execute();
    if (!$stmtCheck->fetchColumn()) {
        send_json(['status' => 'error', 'message' => 'Forbidden (not your advisee).'], 403);
    }

    // Build candidate IDs/usernames to handle legacy/mismatched schema/data.
    $candidateIds = [$studentId];
    $candidateUsernames = [$username];
    try {
        $sdCols = pick_existing_columns($pdo, 'student_details', ['id']);
        if (!empty($sdCols)) {
            $stmtSid = $pdo->prepare("SELECT `id` FROM student_details WHERE student_id = :sid LIMIT 1");
            $stmtSid->bindParam(':sid', $studentId);
            $stmtSid->execute();
            $sdInternalId = $stmtSid->fetchColumn();
            if ($sdInternalId !== false && $sdInternalId !== null) $candidateIds[] = (string)$sdInternalId;
        }
    } catch (Exception $e) {
        // ignore
    }

    if (table_exists($pdo, 'users')) {
        try {
            $cols = pick_existing_columns($pdo, 'users', ['user_id', 'id']);
            $hasUsernameCol = column_exists($pdo, 'users', 'username');
            $usernameSel = $hasUsernameCol ? ', username' : '';

            if (!empty($cols) && $username !== '') {
                $sel = implode(', ', array_map(fn($c) => "`$c`", $cols)) . $usernameSel;
                $stmtU = $pdo->prepare("SELECT $sel FROM users WHERE username = :u LIMIT 1");
                $stmtU->bindParam(':u', $username);
                $stmtU->execute();
                $urow = $stmtU->fetch(PDO::FETCH_ASSOC);
                if (is_array($urow)) {
                    foreach ($cols as $c) {
                        if (isset($urow[$c]) && $urow[$c] !== null && $urow[$c] !== false) $candidateIds[] = (string)$urow[$c];
                    }
                    if ($hasUsernameCol && isset($urow['username'])) $candidateUsernames[] = (string)$urow['username'];
                }
            }

            // Also try resolving by numeric id (handles student_details.student_id referencing a legacy users.id, etc).
            if (!empty($cols)) {
                $where = [];
                foreach ($cols as $c) $where[] = "`$c` = :sidnum";
                $sel = implode(', ', array_map(fn($c) => "`$c`", $cols)) . $usernameSel;
                $stmtU2 = $pdo->prepare("SELECT $sel FROM users WHERE " . implode(' OR ', $where) . " LIMIT 1");
                $stmtU2->bindParam(':sidnum', $studentId);
                $stmtU2->execute();
                $urow2 = $stmtU2->fetch(PDO::FETCH_ASSOC);
                if (is_array($urow2)) {
                    foreach ($cols as $c) {
                        if (isset($urow2[$c]) && $urow2[$c] !== null && $urow2[$c] !== false) $candidateIds[] = (string)$urow2[$c];
                    }
                    if ($hasUsernameCol && isset($urow2['username'])) $candidateUsernames[] = (string)$urow2['username'];
                }
            } else if ($username !== '') {
                // Fallback: try whatever PK exists (user_id preferred).
                $pk = users_pk_col($pdo);
                $stmtU = $pdo->prepare("SELECT `$pk` FROM users WHERE username = :u LIMIT 1");
                $stmtU->bindParam(':u', $username);
                $stmtU->execute();
                $resolved = $stmtU->fetchColumn();
                if ($resolved !== false && $resolved !== null) $candidateIds[] = (string)$resolved;
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    $candidateIds = normalize_candidate_ids($candidateIds);
    if (empty($candidateIds)) $candidateIds = normalize_candidate_ids([$studentId]);
    $candidateUsernames = normalize_candidate_usernames($candidateUsernames);

    // Determine how student_registrations identifies a student (some legacy schemas used multiple columns).
    $srIdCols = [];
    foreach (['student_id', 'user_id', 'sid'] as $c) {
        if (column_exists($pdo, 'student_registrations', $c)) $srIdCols[] = $c;
    }

    $srUserCols = [];
    foreach (['student_username', 'username'] as $c) {
        if (column_exists($pdo, 'student_registrations', $c)) $srUserCols[] = $c;
    }

    $srCourseCol = '';
    foreach (['course_code', 'course', 'course_id', 'courseId'] as $c) {
        if (column_exists($pdo, 'student_registrations', $c)) {
            $srCourseCol = $c;
            break;
        }
    }
    if ($srCourseCol === '') {
        send_json(['status' => 'error', 'message' => 'Unsupported student_registrations schema (missing course_code).'], 500);
    }

    $hasCreatedAt = column_exists($pdo, 'student_registrations', 'created_at');
    $createdSel = $hasCreatedAt ? 'sr.created_at AS registered_at' : 'NULL AS registered_at';

    $params = [];
    $whereParts = [];
    $hasAnyFilter = false;

    // ID-like columns (student_id/user_id/sid) may store numeric ids OR legacy usernames. Handle both when column is string-ish.
    if (!empty($srIdCols) && !empty($candidateIds)) {
        foreach ($srIdCols as $col) {
            $placeholders = [];
            foreach ($candidateIds as $i => $cid) {
                $ph = ':sid_' . $col . '_' . $i;
                $placeholders[] = $ph;
                $params[$ph] = $cid;
            }
            $whereParts[] = "sr.`$col` IN (" . implode(', ', $placeholders) . ")";
            $hasAnyFilter = true;

            $type = get_column_type($pdo, 'student_registrations', $col);
            if (is_stringish_mysql_type($type) && !empty($candidateUsernames)) {
                $uPlaceholders = [];
                foreach ($candidateUsernames as $i => $u) {
                    $ph = ':suser_' . $col . '_' . $i;
                    $uPlaceholders[] = $ph;
                    $params[$ph] = $u;
                }
                $whereParts[] = "LOWER(sr.`$col`) IN (" . implode(', ', $uPlaceholders) . ")";
            }
        }
    }

    // Username columns.
    if (!empty($srUserCols) && !empty($candidateUsernames)) {
        foreach ($srUserCols as $col) {
            $uPlaceholders = [];
            foreach ($candidateUsernames as $i => $u) {
                $ph = ':u_' . $col . '_' . $i;
                $uPlaceholders[] = $ph;
                $params[$ph] = $u;
            }
            $whereParts[] = "LOWER(sr.`$col`) IN (" . implode(', ', $uPlaceholders) . ")";
            $hasAnyFilter = true;
        }
    }

    if (!$hasAnyFilter || empty($whereParts)) {
        send_json(['status' => 'error', 'message' => 'Unsupported student_registrations schema (no usable student identifier column found).'], 500);
    }

    $stmt = $pdo->prepare(
        "SELECT sr.`$srCourseCol` AS course_code,
                cc.course_name,
                cc.credits,
                cc.level,
                cc.is_required,
                $createdSel
         FROM student_registrations sr
         LEFT JOIN core_courses cc ON cc.course_code = sr.`$srCourseCol`
         WHERE (" . implode(' OR ', $whereParts) . ")
         ORDER BY course_code ASC"
    );
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compute deficiency flag in PHP (schema may not match for joins in legacy DBs).
    $defSet = [];
    if (table_exists($pdo, 'student_deficiencies')) {
        try {
            $hasStatus = column_exists($pdo, 'student_deficiencies', 'status');
            $defPlaceholders = [];
            $defParams = [];
            foreach ($candidateIds as $i => $cid) {
                $ph = ':dsid' . $i;
                $defPlaceholders[] = $ph;
                $defParams[$ph] = $cid;
            }
            $defInSql = implode(', ', $defPlaceholders);
            $sqlDef = "SELECT course_code FROM student_deficiencies WHERE student_id IN ($defInSql)";
            if ($hasStatus) $sqlDef .= " AND status = 'assigned'";
            $stmtDef = $pdo->prepare($sqlDef);
            foreach ($defParams as $k => $v) $stmtDef->bindValue($k, $v);
            $stmtDef->execute();
            $defs = $stmtDef->fetchAll(PDO::FETCH_COLUMN);
            foreach ($defs as $c) {
                $cc = strtoupper(trim((string)$c));
                if ($cc !== '') $defSet[$cc] = true;
            }
        } catch (Exception $e) {
            // ignore
        }
    }
    foreach ($rows as &$r) {
        $cc = strtoupper(trim((string)($r['course_code'] ?? '')));
        $r['is_deficiency'] = isset($defSet[$cc]) ? 1 : 0;
    }
    unset($r);

    // De-dupe by course_code (if legacy IDs caused duplicates).
    $byCode = [];
    foreach ($rows as $r) {
        $code = strtoupper(trim((string)($r['course_code'] ?? '')));
        if ($code === '') continue;
        if (!isset($byCode[$code])) {
            $byCode[$code] = $r;
            continue;
        }
        // Prefer the row with a registered_at timestamp if only one has it.
        $a = (string)($byCode[$code]['registered_at'] ?? '');
        $b = (string)($r['registered_at'] ?? '');
        if ($a === '' && $b !== '') $byCode[$code] = $r;
    }
    $rows = array_values($byCode);
    usort($rows, fn($a, $b) => strcmp((string)($a['course_code'] ?? ''), (string)($b['course_code'] ?? '')));

    $totalCredits = 0;
    foreach ($rows as $r) {
        $totalCredits += (int)($r['credits'] ?? 0);
    }

    send_json([
        'status' => 'success',
        'student_id' => $studentId,
        'total_credits' => $totalCredits,
        'data' => $rows,
    ]);
} catch (Exception $e) {
    send_json(['status' => 'error', 'message' => $e->getMessage()], 500);
}
