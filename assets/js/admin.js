(() => {
    'use strict';

    const shell    = document.querySelector('.admin-shell');
    const csrf     = shell.dataset.csrf;
    const usersBody  = document.getElementById('users-body');
    const statusBody = document.getElementById('status-body');

    const modal     = document.getElementById('modal');
    const form      = document.getElementById('user-form');
    const formError = document.getElementById('form-error');
    const pwHint    = document.getElementById('pw-hint');
    const titleEl   = document.getElementById('modal-title');
    const btnNew    = document.getElementById('btn-new-user');
    const btnCancel = document.getElementById('btn-cancel');

    let users = [];

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        })[c]);
    }

    function fmtSince(utcStr) {
        if (!utcStr) return '—';
        const ts = Date.parse(utcStr.replace(' ', 'T') + 'Z');
        const diff = Math.max(0, Math.floor((Date.now() - ts) / 1000));
        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const local = new Date(ts).toLocaleTimeString('he-IL', { hour:'2-digit', minute:'2-digit' });
        return `${local} (${h}ש' ${m}ד')`;
    }

    function renderStatus() {
        if (!users.length) {
            statusBody.innerHTML = '<tr><td colspan="4" class="muted">אין משתמשים</td></tr>';
            return;
        }
        statusBody.innerHTML = users.map(u => {
            const inShift = !!u.open_shift_id;
            const badge = inShift
                ? '<span class="badge badge-on">במשמרת</span>'
                : '<span class="badge badge-off">מחוץ למשמרת</span>';
            return `
                <tr>
                    <td>${escapeHtml(u.username)}</td>
                    <td>${escapeHtml(u.full_name || '—')}</td>
                    <td>${badge}</td>
                    <td>${inShift ? escapeHtml(fmtSince(u.open_shift_since)) : '—'}</td>
                </tr>
            `;
        }).join('');
    }

    function renderUsers() {
        if (!users.length) {
            usersBody.innerHTML = '<tr><td colspan="6" class="muted">אין משתמשים</td></tr>';
            return;
        }
        usersBody.innerHTML = users.map(u => `
            <tr data-id="${u.id}">
                <td><strong>${escapeHtml(u.username)}</strong></td>
                <td>${escapeHtml(u.full_name || '—')}</td>
                <td>${
                    +u.is_superuser
                        ? '<span class="badge badge-super">סופר-יוזר</span>'
                        : '<span class="badge badge-user">עובד</span>'
                }</td>
                <td>${(+u.hourly_rate).toFixed(2)}</td>
                <td>${
                    +u.is_active
                        ? '<span class="badge badge-on">פעיל</span>'
                        : '<span class="badge badge-off">מושבת</span>'
                }</td>
                <td class="row-actions">
                    <button class="btn btn-ghost" data-action="edit">ערוך</button>
                    <button class="btn btn-danger" data-action="delete">מחק</button>
                </td>
            </tr>
        `).join('');
    }

    async function loadUsers() {
        const r = await fetch('/api/users.php?action=list', { credentials:'same-origin' });
        if (r.status === 403) { location.href = '/clock.php'; return; }
        const data = await r.json();
        users = data.users || [];
        renderUsers();
        renderStatus();
    }

    function openModal(user) {
        formError.style.display = 'none';
        formError.textContent = '';
        form.reset();

        if (user) {
            titleEl.textContent = `עריכת משתמש — ${user.username}`;
            form.id.value             = user.id;
            form.username.value       = user.username;
            form.full_name.value      = user.full_name || '';
            form.hourly_rate.value    = +user.hourly_rate || 0;
            form.is_superuser.checked = !!+user.is_superuser;
            form.is_active.checked    = !!+user.is_active;
            form.password.value       = '';
            pwHint.style.display = 'inline';
        } else {
            titleEl.textContent = 'משתמש חדש';
            form.id.value = '';
            form.is_active.checked = true;
            pwHint.style.display = 'none';
        }
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    btnNew.addEventListener('click', () => openModal(null));
    btnCancel.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        formError.style.display = 'none';

        const fd = new FormData(form);
        const id = (fd.get('id') || '').toString();
        const payload = {
            csrf,
            username:     (fd.get('username') || '').toString().trim(),
            full_name:    (fd.get('full_name') || '').toString().trim(),
            hourly_rate:  parseFloat(fd.get('hourly_rate') || '0') || 0,
            is_superuser: fd.get('is_superuser') ? 1 : 0,
            is_active:    fd.get('is_active') ? 1 : 0,
        };
        const pw = (fd.get('password') || '').toString();
        if (pw) payload.password = pw;

        let url;
        if (id) {
            url = '/api/users.php?action=update';
            payload.id = +id;
        } else {
            url = '/api/users.php?action=create';
            if (!pw) {
                formError.textContent = 'יש להזין סיסמה ראשונית';
                formError.style.display = 'block';
                return;
            }
        }

        const r = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await r.json().catch(() => ({}));
        if (!r.ok) {
            formError.textContent = data.error || ('שגיאה ' + r.status);
            formError.style.display = 'block';
            return;
        }
        closeModal();
        loadUsers();
    });

    usersBody.addEventListener('click', async (e) => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const tr = btn.closest('tr');
        const id = +tr.dataset.id;
        const user = users.find(u => +u.id === id);
        if (!user) return;

        if (btn.dataset.action === 'edit') {
            openModal(user);
        } else if (btn.dataset.action === 'delete') {
            if (!confirm(`למחוק את המשתמש "${user.username}"?\nכל המשמרות של המשתמש יימחקו.`)) return;
            const r = await fetch('/api/users.php?action=delete', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf, id }),
            });
            const data = await r.json().catch(() => ({}));
            if (!r.ok) { alert(data.error || ('שגיאה ' + r.status)); return; }
            loadUsers();
        }
    });

    loadUsers();
    setInterval(loadUsers, 30_000);
})();
