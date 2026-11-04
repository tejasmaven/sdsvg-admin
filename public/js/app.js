/**
 * SDSVG Admin Panel — app.js
 * Vanilla JS only. No frameworks. All behaviour driven by data-* attributes.
 */

(function () {
    'use strict';

    const config = window.SDSVG_CONFIG || { sessionLifetime: 3600, uploadMaxMb: 10 };

    // ── 1. Delete member confirmation ─────────────────────────────────────────
    document.querySelectorAll('[data-confirm-delete]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const name = form.dataset.memberName || 'this member';
            if (!confirm('Are you sure you want to delete ' + name + '? This cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // ── 2. Delete entire family — typed confirmation guard ────────────────────
    document.querySelectorAll('[data-typed-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const expected = (form.dataset.confirmValue || '').trim().toLowerCase();
            const input    = form.querySelector('[name="confirm_name"]');
            const typed    = input ? input.value.trim().toLowerCase() : '';
            if (typed !== expected) {
                e.preventDefault();
                alert('Name does not match. Family not deleted.');
            }
        });
    });

    // ── 3. Promote to Head guard ──────────────────────────────────────────────
    document.querySelectorAll('[data-has-head]').forEach(function (form) {
        if (form.dataset.hasHead !== 'true') return;
        form.addEventListener('submit', function (e) {
            if (!confirm('This family already has a Head. Are you sure you want to replace them?')) {
                e.preventDefault();
            }
        });
    });

    // ── 4. CSV file size check ────────────────────────────────────────────────
    const csvInput = document.querySelector('[data-max-mb]');
    if (csvInput) {
        csvInput.addEventListener('change', function () {
            const maxBytes = (parseFloat(csvInput.dataset.maxMb) || config.uploadMaxMb) * 1024 * 1024;
            if (csvInput.files.length && csvInput.files[0].size > maxBytes) {
                alert('File exceeds ' + (maxBytes / 1048576) + ' MB limit.');
                csvInput.value = '';
            }
        });
    }

    // ── 5. Session expiry warning ─────────────────────────────────────────────
    const sessionLifetimeMs = (config.sessionLifetime || 3600) * 1000;
    const warningBeforeMs   = 5 * 60 * 1000; // 5 minutes
    const warnAt            = sessionLifetimeMs - warningBeforeMs;

    let lastActivity = Date.now();
    let warningShown = false;
    const warningBanner = document.getElementById('sdsvg-session-warning');

    function resetTimer() {
        lastActivity = Date.now();
        if (warningBanner && warningShown) {
            warningBanner.classList.add('d-none');
            warningShown = false;
        }
    }

    ['mousemove', 'keydown', 'click', 'touchstart'].forEach(function (evt) {
        document.addEventListener(evt, resetTimer, { passive: true });
    });

    setInterval(function () {
        const idle = Date.now() - lastActivity;
        if (warningBanner && !warningShown && idle >= warnAt) {
            warningBanner.classList.remove('d-none');
            warningShown = true;
        }
    }, 10000); // check every 10 seconds

    // ── 6. Settings tab switching ─────────────────────────────────────────────
    const tabButtons = document.querySelectorAll('.nav-tab');
    const tabPanes   = document.querySelectorAll('.tab-pane');

    function activateTab(tabId) {
        tabPanes.forEach(function (pane) {
            pane.classList.add('d-none');
            pane.classList.remove('active-tab');
        });
        tabButtons.forEach(function (btn) {
            btn.classList.remove('active');
        });

        const targetPane = document.getElementById('tab-' + tabId);
        if (targetPane) {
            targetPane.classList.remove('d-none');
            targetPane.classList.add('active-tab');
        }
        tabButtons.forEach(function (btn) {
            if (btn.dataset.tab === tabId) btn.classList.add('active');
        });
        try { sessionStorage.setItem('sdsvg_active_tab', tabId); } catch (e) {}
    }

    if (tabButtons.length) {
        // Restore from sessionStorage or URL hash
        const savedTab  = (() => { try { return sessionStorage.getItem('sdsvg_active_tab'); } catch(e) { return null; } })();
        const hashTab   = window.location.hash ? window.location.hash.replace('#','') : '';
        const defaultTab = hashTab || savedTab || (tabButtons[0] && tabButtons[0].dataset.tab);
        if (defaultTab) activateTab(defaultTab);

        tabButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activateTab(btn.dataset.tab);
            });
        });
    }

    // ── 7. Committee table row management ────────────────────────────────────
    const addRowBtn   = document.getElementById('add-committee-row');
    const rowsContainer = document.getElementById('committee-rows');
    const committeeJsonInput = document.getElementById('committee-json');

    function removeRowListeners() {
        document.querySelectorAll('[data-remove-row]').forEach(function (btn) {
            btn.onclick = function () {
                const row = btn.closest('.committee-row');
                if (row) row.remove();
            };
        });
    }
    removeRowListeners();

    if (addRowBtn && rowsContainer) {
        addRowBtn.addEventListener('click', function () {
            const div = document.createElement('div');
            div.className = 'committee-row d-flex gap-2 mb-2';
            div.innerHTML =
                '<input type="text" class="form-control" placeholder="Role" data-committee-role>' +
                '<input type="text" class="form-control" placeholder="Name" data-committee-name>' +
                '<button type="button" class="btn btn-outline-danger" data-remove-row><i class="bi bi-trash"></i></button>';
            rowsContainer.appendChild(div);
            removeRowListeners();
        });
    }

    // Serialize committee rows into hidden JSON field on form submit
    const settingsForm = committeeJsonInput && committeeJsonInput.closest('form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function () {
            const rows = [];
            document.querySelectorAll('.committee-row').forEach(function (row) {
                const role = row.querySelector('[data-committee-role]');
                const name = row.querySelector('[data-committee-name]');
                if (role || name) {
                    rows.push({
                        role: role ? role.value.trim() : '',
                        name: name ? name.value.trim() : '',
                    });
                }
            });
            committeeJsonInput.value = JSON.stringify(rows);
        });
    }

})();
