# PinooClock — שעון נוכחות פינוקים

Standalone PHP + SQLite employee time-tracking app. No build step, no SSH required — upload the folder to any PHP-enabled hosting and it works.

## Requirements

- PHP 8.0+ with `pdo_sqlite` extension (enabled by default on most hosts).
- A web server that can serve PHP (Apache, Nginx, shared hosting, LAMP/MAMP).
- The `data/` directory must be writable by the web server (chmod 775).

## Installation

1. Upload the entire project to your web root (e.g. `public_html/`).
2. Make sure `data/` is writable: `chmod -R 775 data`.
3. Visit `https://your-domain.tld/` in a browser.
   - The SQLite database is created automatically on first request.
   - The seed superuser is created automatically:
     - **Username:** `gal404`
     - **Password:** `123456`
   - **⚠ Change this password from the admin dashboard immediately.**

## What's included so far

- **Login** (`/login.php`) — username/password with sessions + CSRF.
- **Clock screen** (`/clock.php`) — RTL UI matching the provided design:
  - "מחוץ לעבודה" / "במשמרת" status
  - Live `HH:MM:SS` timer for today's worked time
  - Big circular **כניסה / יציאה** button (clock-in / clock-out)
  - Stats card: extra minutes, break minutes, today's earnings (₪)
- **Admin dashboard** (`/admin/`, superusers only):
  - Live status of who is currently clocked in
  - Add / edit / delete users
  - Set hourly rate, mark superuser, activate/deactivate
- **Auto-seeded superuser** `gal404 / 123456`.

## Project layout

```
/
├── index.php                # → login or → clock
├── login.php / logout.php
├── clock.php                # main employee screen
├── admin/index.php          # admin-only dashboard
├── api/
│   ├── clock.php            # status / clock-in / clock-out
│   └── users.php            # users CRUD (superuser only)
├── assets/
│   ├── css/style.css
│   └── js/{clock.js,admin.js}
├── includes/
│   ├── config.php           # app constants & timezone
│   ├── db.php               # SQLite + schema bootstrap + seed
│   └── auth.php             # sessions, CSRF, login helpers
└── data/                    # SQLite DB lives here (web-blocked)
```

## Security notes

- Passwords stored with `password_hash()` (bcrypt).
- All write APIs require a CSRF token tied to the session.
- `data/` and `includes/` ship with `.htaccess` deny-all rules.
- On Nginx, you must replicate the deny rules for those folders manually.

## Roadmap (next steps)

- Per-user shift history page + manual edits from admin.
- Break tracking (start/stop a break inside an open shift).
- Monthly report + CSV export.
- Job tagging per shift (the "עבודות" tab in the bottom nav).
