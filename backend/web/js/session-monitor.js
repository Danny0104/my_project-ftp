/**
 * Frontend session monitoring layer.
 * Combines client inactivity tracking with server-side logout invalidation.
 */
(function (global) {
    'use strict';

    var cfg = global.ftSessionConfig || {};
    if (!cfg || !cfg.enabled) {
        return;
    }

    var SESSION_REDIRECT_KEY = 'ft:post-login-redirect';
    var LOGOUT_BROADCAST_KEY = 'ft:logout-at';
    var activityEvents = ['mousemove', 'keydown', 'touchstart', 'click', 'scroll'];

    var state = {
        warningOpen: false,
        warningTimer: null,
        expireTimer: null,
        heartbeatTimer: null,
        lastActivityAt: Date.now(),
        lastHeartbeatAt: 0,
        heartbeatInFlight: false,
        handled: false,
    };

    function warningBeforeMs() {
        return cfg.warningBeforeMs || (5 * 60 * 1000);
    }

    function heartbeatIntervalMs() {
        return cfg.heartbeatIntervalMs || (60 * 1000);
    }

    function sameOriginPath(url) {
        try {
            var parsed = new URL(url, global.location.origin);
            if (parsed.origin !== global.location.origin) {
                return null;
            }
            return parsed.pathname + parsed.search + parsed.hash;
        } catch (e) {
            return null;
        }
    }

    function toast(message, type) {
        if (global.orgToast) {
            global.orgToast({ title: 'Session', message: message, variant: type || 'warning' });
            return;
        }
        var stack = document.getElementById('ftSessionToastStack');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'ftSessionToastStack';
            stack.style.position = 'fixed';
            stack.style.right = '16px';
            stack.style.bottom = '16px';
            stack.style.zIndex = '2000';
            stack.style.display = 'grid';
            stack.style.gap = '8px';
            document.body.appendChild(stack);
        }
        var item = document.createElement('div');
        item.textContent = message;
        item.style.padding = '10px 14px';
        item.style.borderRadius = '10px';
        item.style.background = type === 'danger' ? '#b91c1c' : '#1f2937';
        item.style.color = '#fff';
        item.style.boxShadow = '0 10px 30px rgba(0,0,0,.25)';
        stack.appendChild(item);
        setTimeout(function () {
            item.remove();
        }, 3800);
    }

    function clearLocalAuthState() {
        var keys = [];
        try {
            keys = keys.concat(Object.keys(global.localStorage || {}));
        } catch (e) { /* ignore */ }
        try {
            keys = keys.concat(Object.keys(global.sessionStorage || {}));
        } catch (e2) { /* ignore */ }
        var seen = {};
        keys.forEach(function (key) {
            if (seen[key]) return;
            seen[key] = true;
            var k = String(key || '').toLowerCase();
            if (
                k.indexOf('token') >= 0 ||
                k.indexOf('auth') >= 0 ||
                k.indexOf('session') >= 0 ||
                k.indexOf('user') >= 0
            ) {
                try { global.localStorage.removeItem(key); } catch (e3) { /* ignore */ }
                try { global.sessionStorage.removeItem(key); } catch (e4) { /* ignore */ }
            }
        });
    }

    function loginUrlWithReturn() {
        var destination = global.location.pathname + global.location.search + global.location.hash;
        try {
            global.sessionStorage.setItem(SESSION_REDIRECT_KEY, destination);
        } catch (e) { /* ignore */ }
        if (!cfg.loginUrl) return '/site/login';
        var url = new URL(cfg.loginUrl, global.location.origin);
        url.searchParams.set('returnUrl', destination);
        return url.toString();
    }

    function redirectToLogin(expired) {
        var url = loginUrlWithReturn();
        if (expired) {
            var parsed = new URL(url, global.location.origin);
            parsed.searchParams.set('expired', '1');
            url = parsed.toString();
        }
        global.location.replace(url);
    }

    function looksLikeAuthFailure(payload) {
        if (!payload) return false;
        var text = '';
        if (typeof payload === 'string') text = payload;
        else if (payload.message) text = String(payload.message);
        else text = JSON.stringify(payload);
        text = text.toLowerCase();
        return (
            text.indexOf('expired token') >= 0 ||
            text.indexOf('invalid token') >= 0 ||
            text.indexOf('missing token') >= 0 ||
            text.indexOf('session expired') >= 0 ||
            text.indexOf('unable to verify your data submission') >= 0 ||
            text.indexOf('csrf validation failed') >= 0 ||
            text.indexOf('bad request') >= 0 && text.indexOf('csrf') >= 0
        );
    }

    function isErrorStatus(status) {
        return typeof status === 'number' && status >= 400;
    }

    function performLogout(type) {
        if (state.handled) return;
        state.handled = true;

        try {
            global.localStorage.setItem(LOGOUT_BROADCAST_KEY, String(Date.now()));
        } catch (e) { /* ignore */ }
        clearLocalAuthState();

        if (cfg.logoutUrl) {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = cfg.logoutUrl;

            if (cfg.csrfParam && cfg.csrfToken) {
                var csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = cfg.csrfParam;
                csrfInput.value = cfg.csrfToken;
                form.appendChild(csrfInput);
            }

            var typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'type';
            typeInput.value = type === 'manual' ? 'manual' : 'auto';
            form.appendChild(typeInput);

            document.body.appendChild(form);
            form.submit();
            return;
        }

        redirectToLogin(true);
    }

    function notifySessionExpired() {
        toast('Your session has expired. Please log in again.', 'danger');
        performLogout('auto');
    }

    function handleUnauthorizedResponse(response, bodyText) {
        var status = response && response.status;
        if (status === 401) {
            notifySessionExpired();
            return true;
        }
        if (status === 403 && looksLikeAuthFailure(bodyText)) {
            notifySessionExpired();
            return true;
        }
        if (isErrorStatus(status) && looksLikeAuthFailure(bodyText)) {
            notifySessionExpired();
            return true;
        }
        return false;
    }

    function installFetchInterceptor() {
        if (!global.fetch || global.__ftSessionFetchWrapped) return;
        var originalFetch = global.fetch.bind(global);
        global.fetch = function (input, init) {
            return originalFetch(input, init).then(function (response) {
                if (!response) return response;
                if (response.status === 401) {
                    handleUnauthorizedResponse(response, null);
                    return response;
                }
                if (isErrorStatus(response.status)) {
                    var clone = response.clone();
                    clone.text().then(function (txt) {
                        handleUnauthorizedResponse(response, txt);
                    }).catch(function () { /* ignore */ });
                }
                return response;
            }).catch(function (err) {
                if (looksLikeAuthFailure(err && err.message ? err.message : '')) {
                    notifySessionExpired();
                }
                throw err;
            });
        };
        global.__ftSessionFetchWrapped = true;
    }

    function installXhrInterceptor() {
        if (!global.XMLHttpRequest || global.__ftSessionXhrWrapped) return;
        var originalOpen = global.XMLHttpRequest.prototype.open;
        global.XMLHttpRequest.prototype.open = function () {
            this.addEventListener('load', function () {
                if (!isErrorStatus(this.status)) return;
                var body = '';
                try { body = this.responseText || ''; } catch (e) { /* ignore */ }
                handleUnauthorizedResponse({ status: this.status }, body);
            });
            return originalOpen.apply(this, arguments);
        };
        global.__ftSessionXhrWrapped = true;
    }

    function consumePostLoginRedirect() {
        if (!cfg.isAuthenticated) return;
        var target = null;
        try {
            target = global.sessionStorage.getItem(SESSION_REDIRECT_KEY);
            if (target) global.sessionStorage.removeItem(SESSION_REDIRECT_KEY);
        } catch (e) { /* ignore */ }
        if (!target) return;
        var safe = sameOriginPath(target);
        var current = global.location.pathname + global.location.search + global.location.hash;
        if (safe && safe !== current) {
            global.location.replace(safe);
        }
    }

    function ensureProtectedRoute() {
        if (!cfg.protectedRoute) return;
        if (!cfg.isAuthenticated) {
            redirectToLogin(false);
        }
    }

    function closeWarningModal() {
        var modal = document.getElementById('ftSessionWarningModal');
        if (!modal) return;
        modal.style.display = 'none';
        state.warningOpen = false;
    }

    function showWarningModal() {
        if (state.warningOpen) return;
        var modal = document.getElementById('ftSessionWarningModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'ftSessionWarningModal';
            modal.innerHTML =
                '<div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:2100;display:grid;place-items:center;">' +
                '<div style="background:#111827;color:#fff;max-width:420px;width:90%;border-radius:14px;padding:18px;">' +
                '<h3 style="margin:0 0 8px;font-size:18px;">Session Timeout Warning</h3>' +
                '<p style="margin:0 0 16px;opacity:.9;">You will be logged out due to inactivity. Click &quot;Stay Logged In&quot; to continue your session.</p>' +
                '<div style="display:flex;gap:8px;justify-content:flex-end;">' +
                '<button type="button" data-ft-stay class="btn btn-sm btn-primary">Stay Logged In</button>' +
                '<button type="button" data-ft-logout class="btn btn-sm btn-outline-light">Logout</button>' +
                '</div></div></div>';
            document.body.appendChild(modal);
            modal.querySelector('[data-ft-stay]').addEventListener('click', function () {
                state.lastActivityAt = Date.now();
                resetInactivityTimers();
                closeWarningModal();
                sendHeartbeat(true);
            });
            modal.querySelector('[data-ft-logout]').addEventListener('click', function () {
                performLogout('manual');
            });
        }
        modal.style.display = 'block';
        state.warningOpen = true;
    }

    function resetInactivityTimers() {
        if (!cfg.inactivityTimeoutMs || !cfg.isAuthenticated || !cfg.protectedRoute) return;
        clearTimeout(state.warningTimer);
        clearTimeout(state.expireTimer);
        var warningMs = Math.max(1000, cfg.inactivityTimeoutMs - warningBeforeMs());
        state.warningTimer = setTimeout(function () {
            showWarningModal();
        }, warningMs);
        state.expireTimer = setTimeout(function () {
            notifySessionExpired();
        }, cfg.inactivityTimeoutMs);
    }

    function sendHeartbeat(force) {
        if (!cfg.heartbeatUrl || state.handled || state.heartbeatInFlight) {
            return Promise.resolve();
        }

        var interval = heartbeatIntervalMs();
        var now = Date.now();
        if (!force && (now - state.lastHeartbeatAt) < interval) {
            return Promise.resolve();
        }

        state.heartbeatInFlight = true;
        var body = new URLSearchParams();
        if (cfg.csrfParam && cfg.csrfToken) {
            body.append(cfg.csrfParam, cfg.csrfToken);
        }

        return global.fetch(cfg.heartbeatUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        }).then(function (response) {
            if (response.status === 401 || response.status === 403) {
                handleUnauthorizedResponse(response, null);
                return null;
            }
            return response.json().catch(function () { return null; });
        }).then(function (payload) {
            if (!payload) return;
            if (!payload.ok) {
                handleUnauthorizedResponse({ status: 401 }, JSON.stringify(payload));
                return;
            }
            state.lastHeartbeatAt = Date.now();
        }).catch(function () {
            /* transient network errors are ignored */
        }).finally(function () {
            state.heartbeatInFlight = false;
        });
    }

    function maybeHeartbeatFromActivity() {
        if (!cfg.heartbeatUrl) return;
        var idleMs = Date.now() - state.lastActivityAt;
        if (idleMs > heartbeatIntervalMs() * 2) return;
        sendHeartbeat(false);
    }

    function bindHeartbeat() {
        if (!cfg.heartbeatUrl || !cfg.isAuthenticated || !cfg.protectedRoute) return;

        clearInterval(state.heartbeatTimer);
        state.heartbeatTimer = setInterval(function () {
            if (document.hidden || state.handled) return;
            var idleMs = Date.now() - state.lastActivityAt;
            if (idleMs > heartbeatIntervalMs() * 2) return;
            sendHeartbeat(false);
        }, heartbeatIntervalMs());

        sendHeartbeat(true);
    }

    function bindActivityMonitor() {
        if (!cfg.inactivityTimeoutMs || !cfg.isAuthenticated || !cfg.protectedRoute) return;
        var onActivity = function () {
            state.lastActivityAt = Date.now();
            if (state.warningOpen) closeWarningModal();
            resetInactivityTimers();
            maybeHeartbeatFromActivity();
        };
        activityEvents.forEach(function (evt) {
            global.addEventListener(evt, onActivity, { passive: true });
        });
        resetInactivityTimers();
    }

    function bindLogoutClickBroadcast() {
        document.addEventListener('click', function (event) {
            var link = event.target && event.target.closest ? event.target.closest('a[data-method="post"]') : null;
            if (!link) return;
            var href = link.getAttribute('href') || '';
            if (href.indexOf('site/logout') >= 0) {
                try {
                    global.localStorage.setItem(LOGOUT_BROADCAST_KEY, String(Date.now()));
                } catch (e) { /* ignore */ }
            }
        });
    }

    function bindStorageSync() {
        global.addEventListener('storage', function (event) {
            if (event.key !== LOGOUT_BROADCAST_KEY) return;
            if (!cfg.isAuthenticated) return;
            toast('Your session has expired. Please log in again.', 'danger');
            setTimeout(function () {
                redirectToLogin(true);
            }, 250);
        });
    }

    function init() {
        ensureProtectedRoute();
        consumePostLoginRedirect();
        installFetchInterceptor();
        installXhrInterceptor();
        bindActivityMonitor();
        bindHeartbeat();
        bindLogoutClickBroadcast();
        bindStorageSync();
        global.ftSessionMonitor = {
            expireNow: function () { notifySessionExpired(); },
            handleAuthFailure: function () { notifySessionExpired(); },
            refreshActivity: function () {
                state.lastActivityAt = Date.now();
                resetInactivityTimers();
                sendHeartbeat(true);
            },
            heartbeat: function () { return sendHeartbeat(true); },
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(typeof window !== 'undefined' ? window : this);
