/**
 * Field Training Platform — Student Dashboard interactions
 */
(function () {
    'use strict';

    var COLLAPSE_KEY = 'ftp_sidebar_collapsed';
    var THEME_KEY = 'ftp_theme';

    function getSavedIds() {
        try {
            return JSON.parse(localStorage.getItem('ftp_saved_positions') || '[]');
        } catch (e) {
            return [];
        }
    }

    function setSavedIds(ids) {
        localStorage.setItem('ftp_saved_positions', JSON.stringify(ids));
    }

    function updateSavedCount() {
        var el = document.getElementById('ftp-saved-count');
        if (el) {
            el.textContent = String(getSavedIds().length);
        }
    }

    function initSaveButtons() {
        var saved = getSavedIds();
        document.querySelectorAll('[data-save-id]').forEach(function (btn) {
            var id = parseInt(btn.getAttribute('data-save-id'), 10);
            if (saved.includes(id)) {
                btn.classList.add('saved');
                var icon = btn.querySelector('i');
                if (icon) icon.classList.replace('far', 'fas');
            }
            btn.addEventListener('click', function () {
                var ids = getSavedIds();
                var idx = ids.indexOf(id);
                if (idx >= 0) {
                    ids.splice(idx, 1);
                    btn.classList.remove('saved');
                    var ic = btn.querySelector('i');
                    if (ic) ic.classList.replace('fas', 'far');
                } else {
                    ids.push(id);
                    btn.classList.add('saved');
                    var ic2 = btn.querySelector('i');
                    if (ic2) ic2.classList.replace('far', 'fas');
                    showToast('Opportunity saved to your list');
                }
                setSavedIds(ids);
                updateSavedCount();
            });
        });
        updateSavedCount();
    }

    function isDrawerViewport() {
        return window.innerWidth <= 1023;
    }

    function initMobileSidebar() {
        var sidebar = document.getElementById('ftpSidebar');
        var overlay = document.getElementById('ftpSidebarOverlay');
        var toggle = document.getElementById('ftpSidebarToggle');
        var closeBtn = document.getElementById('ftpSidebarClose');
        if (!sidebar || !toggle) return;

        function lockScroll() {
            if (window.ftLayoutShell) window.ftLayoutShell.lockBodyScroll();
            else document.body.style.overflow = 'hidden';
        }

        function unlockScroll() {
            if (window.ftLayoutShell) window.ftLayoutShell.unlockBodyScroll();
            else document.body.style.overflow = '';
        }

        function open() {
            sidebar.classList.add('open');
            if (overlay) overlay.classList.add('show');
            lockScroll();
        }

        function close() {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('show');
            unlockScroll();
        }

        toggle.addEventListener('click', function () {
            sidebar.classList.contains('open') ? close() : open();
        });
        if (closeBtn) closeBtn.addEventListener('click', close);
        if (overlay) overlay.addEventListener('click', close);

        sidebar.querySelectorAll('.ftp-sidebar-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isDrawerViewport()) close();
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) close();
        });

        window.addEventListener('resize', function () {
            if (!isDrawerViewport()) close();
        });
    }

    function initDesktopCollapse() {
        var btn = document.getElementById('ftpSidebarCollapse');
        var icon = document.getElementById('ftpSidebarCollapseIcon');
        if (!btn) return;

        function apply(collapsed) {
            if (isDrawerViewport()) {
                document.body.classList.remove('ftp-sidebar-collapsed');
                return;
            }
            document.body.classList.toggle('ftp-sidebar-collapsed', collapsed);
            if (icon) {
                icon.classList.toggle('fa-angles-left', !collapsed);
                icon.classList.toggle('fa-angles-right', collapsed);
            }
            window.dispatchEvent(new Event('resize'));
        }

        if (localStorage.getItem(COLLAPSE_KEY) === '1' && !isDrawerViewport()) {
            apply(true);
        }

        btn.addEventListener('click', function () {
            if (isDrawerViewport()) return;
            var next = !document.body.classList.contains('ftp-sidebar-collapsed');
            apply(next);
            localStorage.setItem(COLLAPSE_KEY, next ? '1' : '0');
        });

        window.addEventListener('resize', function () {
            if (isDrawerViewport()) {
                document.body.classList.remove('ftp-sidebar-collapsed');
            }
        });
    }

    function initThemeToggle() {
        var btn = document.getElementById('ftpThemeToggle');
        var icon = document.getElementById('ftpThemeIcon');
        if (!btn) return;

        if (window.ftThemeBridge) {
            window.ftThemeBridge.init({ storageKey: THEME_KEY, defaultTheme: 'light' });
        }

        if (icon && window.ftThemeBridge && window.ftThemeBridge.isDark()) {
            icon.classList.replace('fa-moon', 'fa-sun');
        }

        btn.addEventListener('click', function () {
            var isDark = window.ftThemeBridge ? window.ftThemeBridge.isDark() : false;
            var next = isDark ? 'light' : 'dark';
            if (window.ftThemeBridge) {
                window.ftThemeBridge.apply(next, THEME_KEY);
            }
            if (icon) {
                if (next === 'dark') icon.classList.replace('fa-moon', 'fa-sun');
                else icon.classList.replace('fa-sun', 'fa-moon');
            }
        });
    }

    function initSearch() {
        var form = document.getElementById('ftpSearchForm');
        if (!form) return;
        form.addEventListener('submit', function (e) {
            var input = form.querySelector('input[type="search"]');
            if (input && !input.value.trim()) {
                e.preventDefault();
                window.location.href = form.getAttribute('action');
            }
        });
    }

    function showToast(message) {
        var stack = document.getElementById('ftpToastStack');
        if (!stack) return;
        var toast = document.createElement('div');
        toast.className = 'ftp-toast';
        toast.textContent = message;
        stack.appendChild(toast);
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            setTimeout(function () { toast.remove(); }, 300);
        }, 2800);
    }

    window.ftpShowToast = showToast;

    document.addEventListener('DOMContentLoaded', function () {
        initMobileSidebar();
        initDesktopCollapse();
        initThemeToggle();
        initSaveButtons();
        initSearch();
    });
})();
