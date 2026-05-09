<?php
require_once __DIR__ . '/../includes/auth.php';

$user = currentUser();
if (!$user) jsonResponse(['error' => 'unauthorized'], 401);

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$db     = db();

if ($method === 'GET' && $action === 'status') {
    jsonResponse(buildStatus($db, (int)$user['id'], (float)$user['hourly_rate']));
}

if ($method === 'POST') {
    $body = readJsonBody();
    if (!csrfCheck($body['csrf'] ?? null)) jsonResponse(['error' => 'csrf'], 400);

    if ($action === 'in') {
        $open = openShift($db, (int)$user['id']);
        if ($open) jsonResponse(['error' => 'already_clocked_in'], 409);
        $stmt = $db->prepare('INSERT INTO shifts (user_id, clock_in) VALUES (?, ?)');
        $stmt->execute([(int)$user['id'], gmdate('Y-m-d H:i:s')]);
        jsonResponse(buildStatus($db, (int)$user['id'], (float)$user['hourly_rate']));
    }

    if ($action === 'out') {
        $open = openShift($db, (int)$user['id']);
        if (!$open) jsonResponse(['error' => 'not_clocked_in'], 409);
        $stmt = $db->prepare('UPDATE shifts SET clock_out = ? WHERE id = ?');
        $stmt->execute([gmdate('Y-m-d H:i:s'), (int)$open['id']]);
        jsonResponse(buildStatus($db, (int)$user['id'], (float)$user['hourly_rate']));
    }
}

jsonResponse(['error' => 'bad_request'], 400);

function openShift(PDO $db, int $userId): ?array {
    $stmt = $db->prepare('SELECT * FROM shifts WHERE user_id = ? AND clock_out IS NULL ORDER BY id DESC LIMIT 1');
    $stmt->execute([$userId]);
    $r = $stmt->fetch();
    return $r ?: null;
}

function buildStatus(PDO $db, int $userId, float $rate): array {
    $open = openShift($db, $userId);
    $serverNow = gmdate('Y-m-d H:i:s');

    $todayStartUtc = (new DateTimeImmutable('today', new DateTimeZone(date_default_timezone_get())))
        ->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

    $stmt = $db->prepare("
        SELECT clock_in, clock_out, break_minutes
        FROM shifts
        WHERE user_id = ? AND clock_in >= ?
    ");
    $stmt->execute([$userId, $todayStartUtc]);
    $todayShifts = $stmt->fetchAll();

    $todaySeconds = 0;
    $breakMinutes = 0;
    foreach ($todayShifts as $s) {
        $start = strtotime($s['clock_in'] . ' UTC');
        $end   = $s['clock_out'] ? strtotime($s['clock_out'] . ' UTC') : time();
        $todaySeconds += max(0, $end - $start) - ((int)$s['break_minutes'] * 60);
        $breakMinutes += (int)$s['break_minutes'];
    }
    if ($todaySeconds < 0) $todaySeconds = 0;

    $earnings = ($todaySeconds / 3600) * $rate;

    return [
        'is_clocked_in' => (bool)$open,
        'open_shift'    => $open ? [
            'id'        => (int)$open['id'],
            'clock_in'  => $open['clock_in'],
        ] : null,
        'server_now_utc' => $serverNow,
        'today' => [
            'seconds_worked'   => (int)$todaySeconds,
            'break_minutes'    => $breakMinutes,
            'earnings'         => round($earnings, 2),
        ],
    ];
}
