<?php
require_once __DIR__ . '/includes/auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfCheck($_POST['csrf'] ?? null)) {
        $error = 'בקשה לא תקינה, נסה/י שוב.';
    } else {
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        if ($u === '' || $p === '') {
            $error = 'יש למלא שם משתמש וסיסמה.';
        } elseif (loginAttempt($u, $p)) {
            header('Location: /clock.php');
            exit;
        } else {
            $error = 'שם משתמש או סיסמה שגויים.';
        }
    }
}

$token = csrfToken();
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>התחברות — <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-body">
<main class="auth-card">
    <div class="auth-brand">
        <div class="auth-logo">🕐</div>
        <h1>שעון נוכחות</h1>
        <p class="muted">פינוקים</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="auth-form" autocomplete="on">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($token) ?>">

        <label class="field">
            <span>שם משתמש</span>
            <input type="text" name="username" required autofocus autocapitalize="none" autocomplete="username">
        </label>

        <label class="field">
            <span>סיסמה</span>
            <input type="password" name="password" required autocomplete="current-password">
        </label>

        <button type="submit" class="btn btn-primary btn-block">כניסה</button>
    </form>
</main>
</body>
</html>
