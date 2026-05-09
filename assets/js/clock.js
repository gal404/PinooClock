(() => {
    'use strict';

    const punchBtn   = document.getElementById('punch-btn');
    const punchIcon  = document.getElementById('punch-icon');
    const punchLabel = document.getElementById('punch-label');
    const statusLbl  = document.getElementById('status-label');
    const timeDisp   = document.getElementById('time-display');
    const elBreak    = document.getElementById('stat-break');
    const elEarnings = document.getElementById('stat-earnings');
    const elExtra    = document.getElementById('stat-extra');

    const csrf = punchBtn.dataset.csrf;

    let state = {
        is_clocked_in: false,
        openSince: null,        // epoch ms (UTC) when current shift started
        baseSeconds: 0,         // seconds worked today excluding the open shift
        breakMinutes: 0,
        earnings: 0,
        rateProxyHourly: 0,     // derived from baseSeconds + earnings (server-truth)
    };

    let tickHandle = null;

    function fmtHMS(totalSec) {
        if (totalSec < 0) totalSec = 0;
        const h = Math.floor(totalSec / 3600);
        const m = Math.floor((totalSec % 3600) / 60);
        const s = Math.floor(totalSec % 60);
        const pad = (n) => String(n).padStart(2, '0');
        return `${pad(h)}:${pad(m)}:${pad(s)}`;
    }

    function parseUtc(ts) {
        // 'YYYY-MM-DD HH:MM:SS' in UTC
        return Date.parse(ts.replace(' ', 'T') + 'Z');
    }

    function render() {
        const now = Date.now();
        let liveSec = state.baseSeconds;
        if (state.is_clocked_in && state.openSince) {
            liveSec += Math.max(0, Math.floor((now - state.openSince) / 1000));
        }

        timeDisp.textContent = fmtHMS(liveSec);
        elBreak.textContent  = state.breakMinutes;

        // Live earnings projection while clocked in
        let earnings = state.earnings;
        if (state.is_clocked_in && state.rateProxyHourly > 0) {
            const liveExtra = liveSec - state.baseSeconds;
            earnings = state.earnings + (liveExtra / 3600) * state.rateProxyHourly;
        }
        elEarnings.textContent = earnings.toFixed(2);

        // Extra minutes beyond an 8-hour day
        const STANDARD = 8 * 3600;
        const extraMin = Math.max(0, Math.floor((liveSec - STANDARD) / 60));
        elExtra.textContent = extraMin;

        if (state.is_clocked_in) {
            statusLbl.textContent = 'במשמרת';
            punchBtn.classList.remove('punch-in');
            punchBtn.classList.add('punch-out');
            punchIcon.textContent = '■';
            punchLabel.textContent = 'יציאה';
        } else {
            statusLbl.textContent = 'מחוץ לעבודה';
            punchBtn.classList.add('punch-in');
            punchBtn.classList.remove('punch-out');
            punchIcon.textContent = '▶';
            punchLabel.textContent = 'כניסה';
        }
    }

    function applyServerStatus(data) {
        const totalToday = data.today.seconds_worked;
        let openSec = 0;
        if (data.is_clocked_in && data.open_shift) {
            const start = parseUtc(data.open_shift.clock_in);
            openSec = Math.max(0, Math.floor((Date.now() - start) / 1000));
            state.openSince = start;
        } else {
            state.openSince = null;
        }

        state.is_clocked_in = !!data.is_clocked_in;
        state.baseSeconds   = Math.max(0, totalToday - openSec);
        state.breakMinutes  = data.today.break_minutes || 0;
        state.earnings      = data.today.earnings || 0;

        // Approximate hourly rate so live earnings can grow during a shift
        if (state.baseSeconds > 0 && state.earnings > 0) {
            state.rateProxyHourly = state.earnings / (state.baseSeconds / 3600);
        }
        render();
        ensureTicking();
    }

    function ensureTicking() {
        if (state.is_clocked_in && !tickHandle) {
            tickHandle = setInterval(render, 1000);
        } else if (!state.is_clocked_in && tickHandle) {
            clearInterval(tickHandle);
            tickHandle = null;
        }
    }

    async function fetchStatus() {
        try {
            const r = await fetch('/api/clock.php?action=status', { credentials: 'same-origin' });
            if (r.status === 401) { location.href = '/login.php'; return; }
            const data = await r.json();
            applyServerStatus(data);
        } catch (e) {
            console.error('status failed', e);
        }
    }

    async function punch(action) {
        punchBtn.disabled = true;
        try {
            const r = await fetch(`/api/clock.php?action=${action}`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf }),
            });
            if (r.status === 401) { location.href = '/login.php'; return; }
            const data = await r.json();
            if (!r.ok) {
                alert('שגיאה: ' + (data.error || r.status));
                return;
            }
            applyServerStatus(data);
        } catch (e) {
            alert('שגיאת רשת');
            console.error(e);
        } finally {
            punchBtn.disabled = false;
        }
    }

    punchBtn.addEventListener('click', () => {
        punch(state.is_clocked_in ? 'out' : 'in');
    });

    // Periodic re-sync to correct clock drift
    setInterval(fetchStatus, 60_000);

    fetchStatus();
})();
