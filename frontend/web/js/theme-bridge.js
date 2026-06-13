/**
 * Central dual-theme bridge — syncs data-theme + .theme-light / .theme-dark on html & body.
 * No backend or layout changes required.
 */
(function (global) {
    'use strict';

    function resolveDark(theme) {
        if (theme === 'dark') return true;
        if (theme === 'light') return false;
        if (theme === 'system' || !theme) {
            return global.matchMedia && global.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        return false;
    }

    function setClasses(dark) {
        var root = global.document.documentElement;
        var body = global.document.body;

        root.classList.toggle('theme-dark', dark);
        root.classList.toggle('theme-light', !dark);

        if (body) {
            body.classList.toggle('theme-dark', dark);
            body.classList.toggle('theme-light', !dark);
        }

        root.setAttribute('data-theme', dark ? 'dark' : 'light');
    }

    function applyTheme(theme, storageKey) {
        var dark = resolveDark(theme);
        setClasses(dark);

        if (storageKey) {
            try {
                global.localStorage.setItem(storageKey, theme);
            } catch (e) { /* ignore */ }
        }

        try {
            global.dispatchEvent(new CustomEvent('ft-theme-change', {
                detail: { theme: theme, dark: dark },
            }));
        } catch (e2) { /* ignore */ }

        return dark;
    }

    function readStoredTheme(keys) {
        var list = keys || ['ftp_theme', 'ftp_org_theme', 'ft_theme'];
        for (var i = 0; i < list.length; i++) {
            try {
                var value = global.localStorage.getItem(list[i]);
                if (value) return value;
            } catch (e) { /* ignore */ }
        }
        return null;
    }

    function bootstrapFromDom() {
        var root = global.document.documentElement;
        if (root.classList.contains('theme-dark') || root.getAttribute('data-theme') === 'dark') {
            setClasses(true);
            return 'dark';
        }
        if (root.classList.contains('theme-light') || root.getAttribute('data-theme') === 'light') {
            setClasses(false);
            return 'light';
        }
        return null;
    }

    global.ftThemeBridge = {
        apply: applyTheme,
        isDark: function () {
            return global.document.documentElement.classList.contains('theme-dark')
                || global.document.documentElement.getAttribute('data-theme') === 'dark';
        },
        init: function (options) {
            options = options || {};
            var storageKey = options.storageKey || 'ftp_theme';
            var stored = null;
            try {
                stored = global.localStorage.getItem(storageKey);
            } catch (e) { /* ignore */ }

            if (!stored) {
                stored = readStoredTheme(options.fallbackKeys);
            }

            if (!stored) {
                stored = bootstrapFromDom();
            }

            if (!stored) {
                stored = options.defaultTheme || 'light';
            }

            return applyTheme(stored, storageKey);
        },
    };

    function detectStorageKey() {
        var body = global.document.body;
        if (!body) return 'ftp_theme';
        if (body.classList.contains('org-body') || body.classList.contains('organization-dashboard')) {
            return 'ftp_org_theme';
        }
        if (body.classList.contains('ap-admin-layout') || body.classList.contains('ap-admin-body')) {
            return 'ftp_admin_theme';
        }
        return 'ftp_theme';
    }

    function boot() {
        var key = detectStorageKey();
        var defaults = {
            ftp_org_theme: 'dark',
            ftp_theme: 'light',
            ftp_admin_theme: 'light',
        };
        global.ftThemeBridge.init({
            storageKey: key,
            defaultTheme: defaults[key] || 'light',
        });
    }

    if (global.document.readyState === 'loading') {
        global.document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(typeof window !== 'undefined' ? window : this);
