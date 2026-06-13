/**
 * Field Training Platform — Admin shell interactions
 */
(function (window) {
    'use strict';

    var SIDEBAR_COLLAPSE_KEY = 'ftp_ap_sidebar_collapsed';
    var MOBILE_BP = 992;

    function isMobile() {
        return window.matchMedia && window.matchMedia('(max-width: ' + (MOBILE_BP - 1) + 'px)').matches;
    }

    function animateCounters() {
        document.querySelectorAll('[data-ap-count]').forEach(function (el) {
            if (el.__apDone) return;
            el.__apDone = true;
            var target = parseInt(el.getAttribute('data-ap-count'), 10) || 0;
            var start = performance.now();
            var duration = 1200;
            function tick(now) {
                var p = Math.min((now - start) / duration, 1);
                var eased = 1 - Math.pow(1 - p, 3);
                el.textContent = String(Math.floor(target * eased));
                if (p < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        });
    }

    function triggerChartsResize() {
        window.setTimeout(function () {
            window.dispatchEvent(new Event('resize'));
        }, 280);
    }

    function initResizeObserver() {
        var main = document.querySelector('.ap-main');
        if (!main || typeof ResizeObserver === 'undefined') return;

        var ro = new ResizeObserver(function () {
            window.dispatchEvent(new Event('resize'));
        });
        ro.observe(main);
    }

    function initSidebarTooltips() {
        var tooltipEl = document.getElementById('apNavTooltip');
        if (!tooltipEl) {
            tooltipEl = document.createElement('div');
            tooltipEl.id = 'apNavTooltip';
            tooltipEl.className = 'ap-nav-tooltip';
            tooltipEl.setAttribute('role', 'tooltip');
            document.body.appendChild(tooltipEl);
        }

        function hideTooltip() {
            tooltipEl.classList.remove('is-visible');
        }

        function showTooltip(link) {
            if (!document.body.classList.contains('ap-sidebar-collapsed') && !document.getElementById('apSidebar').classList.contains('is-collapsed')) {
                return;
            }
            if (isMobile()) return;

            var label = link.getAttribute('data-ap-nav-tooltip') || link.querySelector('.ap-nav-text');
            if (!label) return;
            var text = typeof label === 'string' ? label : (label.textContent || '');
            if (!text) return;

            tooltipEl.textContent = text;
            var rect = link.getBoundingClientRect();
            tooltipEl.style.left = (rect.right + 12) + 'px';
            tooltipEl.style.top = (rect.top + rect.height / 2 - tooltipEl.offsetHeight / 2) + 'px';
            tooltipEl.classList.add('is-visible');
        }

        document.querySelectorAll('[data-ap-nav-tooltip]').forEach(function (link) {
            link.addEventListener('mouseenter', function () { showTooltip(link); });
            link.addEventListener('mouseleave', hideTooltip);
            link.addEventListener('focus', function () { showTooltip(link); });
            link.addEventListener('blur', hideTooltip);
        });
    }

    function setCollapsed(collapsed) {
        var sidebar = document.getElementById('apSidebar');
        if (!sidebar) return;

        document.body.classList.toggle('ap-sidebar-collapsed', collapsed);
        sidebar.classList.toggle('is-collapsed', collapsed);

        try {
            localStorage.setItem(SIDEBAR_COLLAPSE_KEY, collapsed ? '1' : '0');
        } catch (e) { /* ignore */ }

        triggerChartsResize();
    }

    function initSidebar() {
        var sidebar = document.getElementById('apSidebar');
        var overlay = document.getElementById('apSidebarOverlay');
        var toggle = document.getElementById('apSidebarToggle');
        var collapseBtn = document.getElementById('apSidebarCollapse');
        if (!sidebar) return;

        function lockScroll() {
            if (window.ftLayoutShell) window.ftLayoutShell.lockBodyScroll();
        }
        function unlockScroll() {
            if (window.ftLayoutShell) window.ftLayoutShell.unlockBodyScroll();
        }
        function openMobile() {
            sidebar.classList.add('is-open');
            if (overlay) overlay.classList.add('is-visible');
            lockScroll();
        }
        function closeMobile() {
            sidebar.classList.remove('is-open');
            if (overlay) overlay.classList.remove('is-visible');
            unlockScroll();
        }

        function syncCollapseFromStorage() {
            if (isMobile()) {
                document.body.classList.remove('ap-sidebar-collapsed');
                sidebar.classList.remove('is-collapsed');
                return;
            }
            var collapsed = false;
            try {
                collapsed = localStorage.getItem(SIDEBAR_COLLAPSE_KEY) === '1';
            } catch (e) { /* ignore */ }
            setCollapsed(collapsed);
        }

        function toggleDesktopCollapse() {
            var collapsed = !document.body.classList.contains('ap-sidebar-collapsed');
            setCollapsed(collapsed);
            closeMobile();
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                if (isMobile()) {
                    sidebar.classList.contains('is-open') ? closeMobile() : openMobile();
                    return;
                }
                toggleDesktopCollapse();
            });
        }

        if (collapseBtn) {
            collapseBtn.addEventListener('click', function () {
                if (isMobile()) return;
                toggleDesktopCollapse();
            });
        }

        if (overlay) {
            overlay.addEventListener('click', closeMobile);
        }

        window.addEventListener('resize', function () {
            if (!isMobile()) {
                closeMobile();
                syncCollapseFromStorage();
            } else {
                document.body.classList.remove('ap-sidebar-collapsed');
                sidebar.classList.remove('is-collapsed');
            }
            triggerChartsResize();
        });

        syncCollapseFromStorage();
        initSidebarTooltips();
    }

    function initProfileMenu() {
        var btn = document.getElementById('apProfileMenuBtn');
        var menu = document.getElementById('apProfileDropdown');
        if (!btn || !menu) return;

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = menu.hidden;
            menu.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function () {
            menu.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
        });
        menu.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    function initTheme() {
        var btn = document.getElementById('apThemeToggle');
        if (!btn) return;
        var storageKey = 'ftp_admin_theme';
        var legacyKey = 'apAdminTheme';

        function syncAdminShell(dark) {
            document.body.classList.toggle('ap-dark', dark);
            var icon = btn.querySelector('i');
            if (icon) icon.className = dark ? 'fas fa-sun' : 'fas fa-moon';
        }

        function readStoredTheme() {
            try {
                return localStorage.getItem(storageKey)
                    || localStorage.getItem(legacyKey)
                    || null;
            } catch (e) {
                return null;
            }
        }

        function persistTheme(theme) {
            try {
                localStorage.setItem(storageKey, theme);
                localStorage.setItem(legacyKey, theme);
            } catch (e) { /* ignore */ }
        }

        var stored = readStoredTheme() || window.ftAdminThemePreference || null;
        if (stored && window.ftThemeBridge) {
            window.ftThemeBridge.apply(stored, storageKey);
            syncAdminShell(window.ftThemeBridge.isDark());
        } else if (stored === 'dark') {
            syncAdminShell(true);
            if (window.ftThemeBridge) {
                window.ftThemeBridge.apply('dark', storageKey);
            }
        } else if (document.body.classList.contains('ap-dark')) {
            syncAdminShell(true);
            if (window.ftThemeBridge) {
                window.ftThemeBridge.apply('dark', storageKey);
            }
        } else if (window.ftThemeBridge) {
            syncAdminShell(window.ftThemeBridge.isDark());
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var dark = !document.body.classList.contains('ap-dark');
            syncAdminShell(dark);
            var theme = dark ? 'dark' : 'light';
            persistTheme(theme);
            if (window.ftThemeBridge) {
                window.ftThemeBridge.apply(theme, storageKey);
            }
        });

        window.addEventListener('ft-theme-change', function (event) {
            if (!event || !event.detail) return;
            syncAdminShell(!!event.detail.dark);
        });
    }

    function initCommandPalette() {
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                var input = document.querySelector('.ap-global-search input');
                if (input) input.focus();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        animateCounters();
        initSidebar();
        initProfileMenu();
        initTheme();
        initCommandPalette();
        initResizeObserver();
    });
})(window);
