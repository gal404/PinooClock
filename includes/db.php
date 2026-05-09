<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $needsInit = !file_exists(DB_PATH);
    if ($needsInit) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    initSchema($pdo);
    seedSuperuser($pdo);

    return $pdo;
}

function initSchema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            full_name     TEXT NOT NULL DEFAULT '',
            is_superuser  INTEGER NOT NULL DEFAULT 0,
            hourly_rate   REAL NOT NULL DEFAULT 0,
            is_active     INTEGER NOT NULL DEFAULT 1,
            created_at    TEXT NOT NULL DEFAULT (datetime('now'))
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shifts (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id        INTEGER NOT NULL,
            clock_in       TEXT NOT NULL,
            clock_out      TEXT,
            break_minutes  INTEGER NOT NULL DEFAULT 0,
            notes          TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_shifts_user ON shifts(user_id, clock_in DESC);");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_shifts_open ON shifts(user_id) WHERE clock_out IS NULL;");
}

function seedSuperuser(PDO $pdo): void {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    $stmt->execute([SEED_SUPERUSER_USERNAME]);
    if ((int)$stmt->fetchColumn() > 0) return;

    $hash = password_hash(SEED_SUPERUSER_PASSWORD, PASSWORD_DEFAULT);
    $ins  = $pdo->prepare('
        INSERT INTO users (username, password_hash, full_name, is_superuser, is_active)
        VALUES (?, ?, ?, 1, 1)
    ');
    $ins->execute([SEED_SUPERUSER_USERNAME, $hash, SEED_SUPERUSER_NAME]);
}
