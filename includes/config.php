<?php
// Pinookim Attendance Clock — configuration
declare(strict_types=1);

date_default_timezone_set('Asia/Jerusalem');

const APP_NAME = 'שעון נוכחות - פינוקים';
const DB_PATH  = __DIR__ . '/../data/pinooclock.db';

// Initial superuser seeded on first run
const SEED_SUPERUSER_USERNAME = 'gal404';
const SEED_SUPERUSER_PASSWORD = '123456';
const SEED_SUPERUSER_NAME     = 'Gal (Super Admin)';

// Session lifetime in seconds (12 hours)
const SESSION_LIFETIME = 60 * 60 * 12;
