<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function currentUser(): ?array {
    startSession();
    if (empty($_SESSION['user_id'])) return null;
    $stmt = db()->prepare('SELECT id, username, full_name, is_superuser, hourly_rate, is_active FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || (int)$user['is_active'] !== 1) {
        session_destroy();
        return null;
    }
    return $user;
}

function requireLogin(): array {
    $user = currentUser();
    if (!$user) {
        header('Location: /login.php');
        exit;
    }
    return $user;
}

function requireSuperuser(): array {
    $user = requireLogin();
    if ((int)$user['is_superuser'] !== 1) {
        http_response_code(403);
        echo '<!doctype html><meta charset="utf-8"><title>403</title><h1>403 — אין הרשאה</h1>';
        exit;
    }
    return $user;
}

function loginAttempt(string $username, string $password): bool {
    startSession();
    $stmt = db()->prepare('SELECT id, password_hash, is_active FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if (!$row || (int)$row['is_active'] !== 1) return false;
    if (!password_verify($password, $row['password_hash'])) return false;

    session_regenerate_id(true);
    $_SESSION['user_id']    = (int)$row['id'];
    $_SESSION['login_time'] = time();
    return true;
}

function logout(): void {
    startSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'] ?? false, $p['httponly'] ?? true);
    }
    session_destroy();
}

function csrfToken(): string {
    startSession();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfCheck(?string $token): bool {
    startSession();
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function readJsonBody(): array {
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') return [];
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
}
