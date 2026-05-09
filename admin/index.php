<?php
require_once __DIR__ . '/../includes/auth.php';
$me    = requireSuperuser();
$token = csrfToken();
?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>ניהול — <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-shell" data-csrf="<?= htmlspecialchars($token) ?>">

    <header class="admin-header">
        <div>
            <h1>דאשבורד ניהול — שעון נוכחות פינוקים</h1>
            <p class="muted" style="margin:6px 0 0">מחובר/ת: <strong><?= htmlspecialchars($me['username']) ?></strong></p>
        </div>
        <div style="display:flex; gap:8px;">
            <a class="btn btn-ghost" href="/clock.php">← חזרה לשעון</a>
            <a class="btn btn-ghost" href="/logout.php">התנתק</a>
        </div>
    </header>

    <section class="card">
        <h2>סטטוס נוכחי</h2>
        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>משתמש</th>
                        <th>שם מלא</th>
                        <th>סטטוס</th>
                        <th>בכניסה מ-</th>
                    </tr>
                </thead>
                <tbody id="status-body">
                    <tr><td colspan="4" class="muted">טוען…</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; gap:12px; flex-wrap:wrap;">
            <h2 style="margin:0;">משתמשים</h2>
            <button class="btn btn-primary" id="btn-new-user">+ הוסף משתמש</button>
        </div>

        <div class="tbl-wrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>שם משתמש</th>
                        <th>שם מלא</th>
                        <th>תפקיד</th>
                        <th>שעת/שעה (₪)</th>
                        <th>פעיל</th>
                        <th>פעולות</th>
                    </tr>
                </thead>
                <tbody id="users-body">
                    <tr><td colspan="6" class="muted">טוען…</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- Modal -->
<div id="modal" style="display:none; position:fixed; inset:0; background:rgba(20,25,35,0.45); z-index:50; align-items:center; justify-content:center; padding:16px;">
    <div class="card" style="width:100%; max-width:520px; margin:0;">
        <h2 id="modal-title" style="margin-bottom:14px;">משתמש חדש</h2>
        <form id="user-form">
            <input type="hidden" name="id">
            <div class="form-grid">
                <label class="field">
                    <span>שם משתמש</span>
                    <input type="text" name="username" required autocomplete="off">
                </label>
                <label class="field">
                    <span>שם מלא</span>
                    <input type="text" name="full_name" autocomplete="off">
                </label>
                <label class="field">
                    <span>סיסמה <span class="muted" id="pw-hint" style="display:none;">(ריק = ללא שינוי)</span></span>
                    <input type="password" name="password" autocomplete="new-password">
                </label>
                <label class="field">
                    <span>שכר לשעה (₪)</span>
                    <input type="number" name="hourly_rate" min="0" step="0.5" value="0">
                </label>
                <label class="checkbox field-full">
                    <input type="checkbox" name="is_superuser"> סופר-יוזר (גישה לניהול)
                </label>
                <label class="checkbox field-full">
                    <input type="checkbox" name="is_active" checked> משתמש פעיל
                </label>
            </div>

            <div id="form-error" class="alert alert-error" style="display:none; margin-top:14px;"></div>

            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:18px;">
                <button type="button" class="btn btn-ghost" id="btn-cancel">ביטול</button>
                <button type="submit" class="btn btn-primary">שמור</button>
            </div>
        </form>
    </div>
</div>

<script src="/assets/js/admin.js"></script>
</body>
</html>
