<?php
require_once __DIR__ . '/../includes/auth.php';

$me = currentUser();
if (!$me || (int)$me['is_superuser'] !== 1) jsonResponse(['error' => 'forbidden'], 403);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db     = db();

if ($method === 'GET' && $action === 'list') {
    $rows = $db->query("
        SELECT u.id, u.username, u.full_name, u.is_superuser, u.is_active, u.hourly_rate,
               u.created_at,
               (SELECT s.id FROM shifts s WHERE s.user_id = u.id AND s.clock_out IS NULL ORDER BY s.id DESC LIMIT 1) AS open_shift_id,
               (SELECT s.clock_in FROM shifts s WHERE s.user_id = u.id AND s.clock_out IS NULL ORDER BY s.id DESC LIMIT 1) AS open_shift_since
        FROM users u
        ORDER BY u.is_superuser DESC, u.username ASC
    ")->fetchAll();
    jsonResponse(['users' => $rows]);
}

if ($method !== 'POST') jsonResponse(['error' => 'method'], 405);

$body = readJsonBody();
if (!csrfCheck($body['csrf'] ?? null)) jsonResponse(['error' => 'csrf'], 400);

if ($action === 'create') {
    $u  = trim((string)($body['username']  ?? ''));
    $p  = (string)($body['password']  ?? '');
    $fn = trim((string)($body['full_name'] ?? ''));
    $hr = (float)($body['hourly_rate'] ?? 0);
    $su = !empty($body['is_superuser']) ? 1 : 0;
    $ac = isset($body['is_active']) ? (!empty($body['is_active']) ? 1 : 0) : 1;

    if ($u === '' || strlen($u) < 3) jsonResponse(['error' => 'שם משתמש קצר מדי (לפחות 3 תווים)'], 400);
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $u)) jsonResponse(['error' => 'שם משתמש: רק אותיות באנגלית, ספרות, נקודה/קו תחתון/מקף'], 400);
    if (strlen($p) < 4) jsonResponse(['error' => 'סיסמה קצרה מדי (לפחות 4 תווים)'], 400);

    try {
        $stmt = $db->prepare('
            INSERT INTO users (username, password_hash, full_name, is_superuser, hourly_rate, is_active)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$u, password_hash($p, PASSWORD_DEFAULT), $fn, $su, $hr, $ac]);
        jsonResponse(['ok' => true, 'id' => (int)$db->lastInsertId()]);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'UNIQUE')) {
            jsonResponse(['error' => 'שם המשתמש כבר קיים'], 409);
        }
        jsonResponse(['error' => 'שגיאה: ' . $e->getMessage()], 500);
    }
}

if ($action === 'update') {
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) jsonResponse(['error' => 'id חסר'], 400);

    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) jsonResponse(['error' => 'משתמש לא נמצא'], 404);

    $fields = [];
    $params = [];

    if (array_key_exists('full_name', $body)) {
        $fields[] = 'full_name = ?';
        $params[] = trim((string)$body['full_name']);
    }
    if (array_key_exists('hourly_rate', $body)) {
        $fields[] = 'hourly_rate = ?';
        $params[] = (float)$body['hourly_rate'];
    }
    if (array_key_exists('is_active', $body)) {
        if ((int)$existing['id'] === (int)$me['id'] && empty($body['is_active'])) {
            jsonResponse(['error' => 'לא ניתן להשבית את המשתמש שלך'], 400);
        }
        $fields[] = 'is_active = ?';
        $params[] = !empty($body['is_active']) ? 1 : 0;
    }
    if (array_key_exists('is_superuser', $body)) {
        if ((int)$existing['id'] === (int)$me['id'] && empty($body['is_superuser'])) {
            jsonResponse(['error' => 'לא ניתן להוריד הרשאות סופר-יוזר מעצמך'], 400);
        }
        $fields[] = 'is_superuser = ?';
        $params[] = !empty($body['is_superuser']) ? 1 : 0;
    }
    if (!empty($body['password'])) {
        if (strlen((string)$body['password']) < 4) jsonResponse(['error' => 'סיסמה קצרה מדי'], 400);
        $fields[] = 'password_hash = ?';
        $params[] = password_hash((string)$body['password'], PASSWORD_DEFAULT);
    }
    if (!empty($body['username'])) {
        $u = trim((string)$body['username']);
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $u) || strlen($u) < 3) {
            jsonResponse(['error' => 'שם משתמש לא תקין'], 400);
        }
        $fields[] = 'username = ?';
        $params[] = $u;
    }

    if (empty($fields)) jsonResponse(['ok' => true]);

    $params[] = $id;
    try {
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $db->prepare($sql)->execute($params);
        jsonResponse(['ok' => true]);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'UNIQUE')) {
            jsonResponse(['error' => 'שם המשתמש כבר קיים'], 409);
        }
        jsonResponse(['error' => 'שגיאה: ' . $e->getMessage()], 500);
    }
}

if ($action === 'delete') {
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) jsonResponse(['error' => 'id חסר'], 400);
    if ($id === (int)$me['id']) jsonResponse(['error' => 'לא ניתן למחוק את המשתמש שלך'], 400);

    $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    jsonResponse(['ok' => true]);
}

jsonResponse(['error' => 'unknown_action'], 400);
