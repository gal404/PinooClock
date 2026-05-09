<?php
require_once __DIR__ . '/includes/auth.php';
$user  = requireLogin();
$token = csrfToken();
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>שעון — <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="app-body">
<div class="app-shell">

    <header class="app-header">
        <span class="hdr-pill">פינוקים</span>

        <h1 class="hdr-title">שעון</h1>

        <?php if ((int)$user['is_superuser'] === 1): ?>
            <a class="hdr-btn" href="/admin/" title="ניהול">+</a>
        <?php else: ?>
            <span class="hdr-btn hdr-btn-ghost"></span>
        <?php endif; ?>
    </header>

    <main class="clock-main">
        <p class="status-label" id="status-label">מחוץ לעבודה</p>
        <div class="time-display" id="time-display">00:00:00</div>

        <button id="punch-btn" class="punch-btn punch-in" type="button"
                data-csrf="<?= htmlspecialchars($token) ?>">
            <span class="punch-icon" id="punch-icon">▶</span>
            <span class="punch-label" id="punch-label">כניסה</span>
        </button>
    </main>

    <section class="stats-card">
        <div class="stat">
            <div class="stat-icon stat-icon-extra">⏱</div>
            <div class="stat-value"><span id="stat-extra">0</span> דק'</div>
            <div class="stat-name">שעות נוספות</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat">
            <div class="stat-icon stat-icon-break">☕</div>
            <div class="stat-value"><span id="stat-break">0</span> דק'</div>
            <div class="stat-name">הפסקה</div>
        </div>
        <div class="stat-divider"></div>
        <div class="stat">
            <div class="stat-icon stat-icon-money">$</div>
            <div class="stat-value"><span id="stat-earnings">0.00</span>₪</div>
            <div class="stat-name">רווח</div>
        </div>
    </section>

    <nav class="bottom-nav">
        <span class="nav-item nav-active">
            <span class="nav-icon">🕐</span>
            <span class="nav-label">שעון</span>
        </span>
        <span class="nav-item nav-disabled">
            <span class="nav-icon">📋</span>
            <span class="nav-label">גליון עבודה</span>
        </span>
        <span class="nav-item nav-disabled">
            <span class="nav-icon">💼</span>
            <span class="nav-label">עבודות</span>
        </span>
        <span class="nav-item nav-disabled">
            <span class="nav-icon">⇪</span>
            <span class="nav-label">יצוא</span>
        </span>
        <a class="nav-item" href="/logout.php" title="התנתק">
            <span class="nav-icon">⚙</span>
            <span class="nav-label">הגדרות</span>
        </a>
    </nav>
</div>

<script src="/assets/js/clock.js"></script>
</body>
</html>
