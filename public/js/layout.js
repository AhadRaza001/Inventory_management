/**
 * FinanceOS — Layout JS
 * Handles: sidebar toggle/collapse, mobile sidebar, dropdowns,
 *          theme toggle, global search, sidebar search filter,
 *          accordion menus, notifications
 */

(function () {
    'use strict';

    /* ── Elements ─────────────────────────────────────────────── */
    const body             = document.body;
    const sidebar          = document.getElementById('sidebar');
    const sidebarToggle    = document.getElementById('sidebarToggle');
    const sidebarCloseBtn  = document.getElementById('sidebarCloseBtn');
    const sidebarOverlay   = document.getElementById('sidebarOverlay');
    const themeToggle      = document.getElementById('themeToggle');
    const themeIcon        = document.getElementById('themeIcon');
    const sidebarSearch    = document.getElementById('sidebarSearch');
    const sidebarNav       = document.getElementById('sidebarNav');
    const globalSearchTrig = document.getElementById('globalSearchTrigger');
    const globalSearchBox  = document.getElementById('globalSearchBox');
    const globalSearchInp  = document.getElementById('globalSearchInput');
    const notifBtn         = document.getElementById('notifBtn');
    const notifPanel       = document.getElementById('notifPanel');
    const notifBadge       = document.getElementById('notifBadge');
    const markAllRead      = document.getElementById('markAllRead');
    const userBtn          = document.getElementById('userBtn');
    const userPanel        = document.getElementById('userPanel');
    const userDropdown     = document.getElementById('userDropdown');
    const notifDropdown    = document.getElementById('notifDropdown');

    /* ── Helpers ──────────────────────────────────────────────── */
    const isMobile = () => window.innerWidth <= 1024;

    function savePref(key, val) {
        try { localStorage.setItem('financeos_' + key, JSON.stringify(val)); } catch(_) {}
    }
    function loadPref(key, def) {
        try {
            const v = localStorage.getItem('financeos_' + key);
            return v !== null ? JSON.parse(v) : def;
        } catch(_) { return def; }
    }

    /* ── Sidebar Collapse (desktop) ───────────────────────────── */
    function setSidebarCollapsed(collapsed) {
        if (collapsed) {
            body.classList.add('sidebar-collapsed');
            // Persist via AJAX so PHP session knows
            fetch('/layout/sidebar-state', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ collapsed: true })
            }).catch(() => {});
        } else {
            body.classList.remove('sidebar-collapsed');
            fetch('/layout/sidebar-state', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ collapsed: false })
            }).catch(() => {});
        }
        savePref('sidebarCollapsed', collapsed);
    }

    /* ── Sidebar Mobile Open/Close ────────────────────────────── */
    function openMobileSidebar() {
        sidebar.classList.add('mobile-open');
        sidebarOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeMobileSidebar() {
        sidebar.classList.remove('mobile-open');
        sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    /* Toggle button */
    sidebarToggle?.addEventListener('click', () => {
        if (isMobile()) {
            sidebar.classList.contains('mobile-open') ? closeMobileSidebar() : openMobileSidebar();
        } else {
            setSidebarCollapsed(!body.classList.contains('sidebar-collapsed'));
        }
    });
    sidebarCloseBtn?.addEventListener('click', closeMobileSidebar);
    sidebarOverlay?.addEventListener('click', closeMobileSidebar);

    /* ── Accordion Menu ───────────────────────────────────────── */
    sidebarNav?.querySelectorAll('.nav-accordion-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            const item     = this.closest('.nav-item');
            const children = item.querySelector('.nav-children');
            const isOpen   = item.classList.contains('open');

            // Close all siblings
            item.closest('.nav-group')?.querySelectorAll('.nav-item.has-children').forEach(other => {
                if (other !== item) {
                    other.classList.remove('open');
                    other.querySelector('.nav-children').style.display = 'none';
                    other.querySelector('.nav-accordion-toggle')?.setAttribute('aria-expanded', 'false');
                }
            });

            if (isOpen) {
                item.classList.remove('open');
                children.style.display = 'none';
                btn.setAttribute('aria-expanded', 'false');
            } else {
                item.classList.add('open');
                children.style.display = 'block';
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    /* ── Sidebar Search Filter ────────────────────────────────── */
    sidebarSearch?.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        sidebarNav?.querySelectorAll('.nav-item, .nav-child-link').forEach(el => {
            const label = el.dataset.search || el.textContent.toLowerCase();
            const visible = !q || label.includes(q);
            el.style.display = visible ? '' : 'none';
        });
        // Show parent groups with at least one visible child
        sidebarNav?.querySelectorAll('.nav-group').forEach(group => {
            const visible = Array.from(group.querySelectorAll('.nav-item')).some(i => i.style.display !== 'none');
            group.style.display = visible ? '' : 'none';
        });
    });

    /* ── Theme Toggle ─────────────────────────────────────────── */
    const savedTheme = loadPref('theme', 'light');
    applyTheme(savedTheme);

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
    }
    themeToggle?.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        applyTheme(next);
        savePref('theme', next);
    });

    /* ── Dropdowns ────────────────────────────────────────────── */
    function openPanel(btn, panel, wrapper) {
        panel.classList.add('open');
        wrapper?.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
    }
    function closePanel(btn, panel, wrapper) {
        panel.classList.remove('open');
        wrapper?.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
    }
    function togglePanel(btn, panel, wrapper) {
        panel.classList.contains('open') ? closePanel(btn, panel, wrapper) : openPanel(btn, panel, wrapper);
    }

    notifBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        closePanel(userBtn, userPanel, userDropdown);
        togglePanel(notifBtn, notifPanel, notifDropdown);
    });
    userBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        closePanel(notifBtn, notifPanel, notifDropdown);
        togglePanel(userBtn, userPanel, userDropdown);
    });
    document.addEventListener('click', () => {
        closePanel(notifBtn, notifPanel, notifDropdown);
        closePanel(userBtn, userPanel, userDropdown);
    });
    [notifPanel, userPanel].forEach(p => p?.addEventListener('click', e => e.stopPropagation()));

    /* ── Mark All Notifications Read ─────────────────────────── */
    markAllRead?.addEventListener('click', () => {
        document.querySelectorAll('.notif-item.unread').forEach(i => i.classList.remove('unread'));
        if (notifBadge) notifBadge.style.display = 'none';
    });

    /* ── Global Search ────────────────────────────────────────── */
    globalSearchTrig?.addEventListener('click', () => {
        globalSearchBox?.classList.add('active');
        globalSearchTrig.style.display = 'none';
        globalSearchInp?.focus();
    });
    globalSearchInp?.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            globalSearchBox?.classList.remove('active');
            globalSearchTrig.style.display = '';
        }
    });
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            globalSearchBox?.classList.add('active');
            globalSearchTrig.style.display = 'none';
            globalSearchInp?.focus();
        }
    });

    /* ── Restore Sidebar State (desktop) ─────────────────────── */
    if (!isMobile()) {
        const collapsed = loadPref('sidebarCollapsed', false);
        if (collapsed) body.classList.add('sidebar-collapsed');
    }

    /* ── Resize handler ───────────────────────────────────────── */
    window.addEventListener('resize', () => {
        if (!isMobile()) {
            closeMobileSidebar();
            body.style.overflow = '';
        }
    });

    /* ── Highlight active nav-link tooltips ───────────────────── */
    sidebarNav?.querySelectorAll('.nav-link').forEach(link => {
        const label = link.querySelector('.nav-label');
        if (label) link.setAttribute('data-tooltip', label.textContent.trim());
    });

})();
