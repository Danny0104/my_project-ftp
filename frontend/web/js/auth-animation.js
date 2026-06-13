/**
 * Field Training Platform — Login ↔ Signup panel transitions
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'ftpAuthTransition';
    var DURATION_MS = 680;
    var CACHE_KEY = 'ftpAuthCacheV2';

    function prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function getAuthRoot() {
        return document.querySelector('.auth-bg[data-auth-page]');
    }

    function getUnifiedCard() {
        return document.querySelector('.auth-unified[data-auth-state]');
    }

    function getPane(name) {
        return document.querySelector('.auth-form-pane[data-auth-pane="' + name + '"]');
    }

    function setUnifiedState(nextState) {
        var card = getUnifiedCard();
        if (!card) return;
        card.setAttribute('data-auth-state', nextState);

        var loginPane = getPane('login');
        var signupPane = getPane('signup');
        if (loginPane) {
            loginPane.classList.toggle('is-active', nextState === 'login');
            loginPane.setAttribute('aria-hidden', nextState === 'signup' ? 'true' : 'false');
        }
        if (signupPane) {
            signupPane.classList.toggle('is-active', nextState === 'signup');
            signupPane.setAttribute('aria-hidden', nextState === 'login' ? 'true' : 'false');
        }
    }

    function parseHtml(html) {
        var parser = new window.DOMParser();
        return parser.parseFromString(html, 'text/html');
    }

    function extractPaneHtml(doc, paneName) {
        var pane = doc.querySelector('.auth-form-pane[data-auth-pane="' + paneName + '"]');
        if (!pane) return null;
        return pane.innerHTML;
    }

    function syncRegisterShell(doc, targetState) {
        var card = getUnifiedCard();
        var root = getAuthRoot();
        if (!card || !doc) return;

        var fetchedCard = doc.querySelector('.auth-unified[data-auth-state]');
        var isRegister = fetchedCard && fetchedCard.classList.contains('auth-unified--register');
        card.classList.toggle('auth-unified--register', !!isRegister);

        if (root) {
            root.setAttribute('data-auth-page', targetState === 'signup' ? 'signup' : 'login');
            root.classList.toggle('auth-bg--signup', targetState === 'signup');
            root.classList.toggle('auth-bg--login', targetState === 'login');
        }
    }

    function swapAuthPanes(doc, targetState) {
        var targetPane = targetState;
        var oppositePane = targetState === 'signup' ? 'login' : 'signup';
        var paneHtml = extractPaneHtml(doc, targetPane);
        if (!paneHtml) return false;

        var paneEl = getPane(targetPane);
        if (paneEl) {
            paneEl.innerHTML = paneHtml;
        }

        var oppositeHtml = extractPaneHtml(doc, oppositePane);
        if (oppositeHtml) {
            var oppositeEl = getPane(oppositePane);
            if (oppositeEl) {
                oppositeEl.innerHTML = oppositeHtml;
            }
        }

        syncRegisterShell(doc, targetState);
        return true;
    }

    function cacheSet(url, html) {
        try {
            var cache = JSON.parse(sessionStorage.getItem(CACHE_KEY) || '{}');
            cache[url] = { html: html, t: Date.now() };
            sessionStorage.setItem(CACHE_KEY, JSON.stringify(cache));
        } catch (e) {
            // ignore
        }
    }

    function cacheGet(url) {
        try {
            var cache = JSON.parse(sessionStorage.getItem(CACHE_KEY) || '{}');
            return cache[url] ? cache[url].html : null;
        } catch (e) {
            return null;
        }
    }

    function fetchPage(url) {
        var cached = cacheGet(url);
        if (cached) {
            return Promise.resolve(cached);
        }

        return window.fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            if (!res.ok) throw new Error('Failed to load');
            return res.text();
        }).then(function (html) {
            cacheSet(url, html);
            return html;
        });
    }

    function rebindDynamicAuthHandlers() {
        // signup password toggle depends on global fn used in field template
        window.togglePassword = function togglePassword(fieldId) {
            var field = document.getElementById(fieldId);
            if (!field) return;
            var toggle = field.nextElementSibling;
            if (field.type === 'password') {
                field.type = 'text';
                if (toggle) toggle.textContent = '👁️‍🗨️';
            } else {
                field.type = 'password';
                if (toggle) toggle.textContent = '👁️';
            }
        };

        var passwordField = document.getElementById('signupform-password');
        if (passwordField && !passwordField.__ftpBound) {
            passwordField.__ftpBound = true;
            passwordField.addEventListener('input', function () {
                var password = passwordField.value || '';
                var strengthBar = document.getElementById('password-strength');
                var reqLen = document.getElementById('req-length');
                var reqUp = document.getElementById('req-uppercase');
                var reqLow = document.getElementById('req-lowercase');
                var reqNum = document.getElementById('req-number');

                var hasLength = password.length >= 8;
                var hasUppercase = /[A-Z]/.test(password);
                var hasLowercase = /[a-z]/.test(password);
                var hasNumber = /\d/.test(password);

                if (reqLen) reqLen.className = hasLength ? 'valid' : '';
                if (reqUp) reqUp.className = hasUppercase ? 'valid' : '';
                if (reqLow) reqLow.className = hasLowercase ? 'valid' : '';
                if (reqNum) reqNum.className = hasNumber ? 'valid' : '';

                var strength = 0;
                if (hasLength) strength++;
                if (hasUppercase) strength++;
                if (hasLowercase) strength++;
                if (hasNumber) strength++;

                if (!strengthBar) return;
                strengthBar.className = 'password-strength';
                if (password.length === 0) {
                    strengthBar.className = 'password-strength';
                } else if (strength <= 2) {
                    strengthBar.className = 'password-strength weak';
                } else if (strength === 3) {
                    strengthBar.className = 'password-strength medium';
                } else {
                    strengthBar.className = 'password-strength strong';
                }
            });
        }

        var roleSelect = document.getElementById('role-select');
        var orgFields = document.getElementById('organization-fields');
        if (roleSelect && orgFields && !roleSelect.__ftpBound) {
            roleSelect.__ftpBound = true;
            roleSelect.addEventListener('change', function () {
                if (roleSelect.value === 'organization') {
                    orgFields.classList.add('show');
                } else {
                    orgFields.classList.remove('show');
                }
            });
        }

        var signupForm = document.getElementById('form-signup');
        if (signupForm && !signupForm.__ftpBound) {
            signupForm.__ftpBound = true;
            var submitBtn = signupForm.querySelector('[type="submit"]');
            if (submitBtn) {
                signupForm.addEventListener('submit', function () {
                    submitBtn.classList.add('is-loading');
                    submitBtn.disabled = true;
                });
            }
        }

        document.dispatchEvent(new CustomEvent('ftp:auth-rebind'));
    }

    function runEnterAnimation() {
        var root = getAuthRoot();
        if (!root) {
            return;
        }

        // ensure handlers after any swap
        rebindDynamicAuthHandlers();

        if (prefersReducedMotion()) {
            sessionStorage.removeItem(STORAGE_KEY);
            return;
        }

        var transition = sessionStorage.getItem(STORAGE_KEY);
        sessionStorage.removeItem(STORAGE_KEY);

        if (transition === 'to-signup') {
            root.setAttribute('data-auth-enter', 'from-right');
        } else if (transition === 'to-login') {
            root.setAttribute('data-auth-enter', 'from-left');
        } else {
            root.classList.add('auth-initial-enter');
        }
    }

    function navigateWithTransition(url, direction) {
        var root = getAuthRoot();
        var card = getUnifiedCard();

        // If we have the unified card, do a fetch + swap (no hard reload).
        if (card && !prefersReducedMotion()) {
            var targetState = direction === 'to-signup' ? 'signup' : 'login';
            var nextHtmlPromise = fetchPage(url);

            // lock UI during transition
            root.classList.add('is-auth-transitioning');
            card.classList.add('is-auth-swapping');

            nextHtmlPromise.then(function (html) {
                var doc = parseHtml(html);
                if (!swapAuthPanes(doc, targetState)) {
                    window.location.href = url;
                    return;
                }

                // animate overlay + panes
                window.requestAnimationFrame(function () {
                    setUnifiedState(targetState);
                });

                // update URL and title
                try {
                    window.history.pushState({ auth: targetState }, '', url);
                    var title = doc.querySelector('title');
                    if (title) document.title = title.textContent;
                } catch (e) {
                    // ignore
                }

                window.setTimeout(function () {
                    root.classList.remove('is-auth-transitioning');
                    card.classList.remove('is-auth-swapping');
                    rebindDynamicAuthHandlers();
                    bindSwitchLinks(); // re-bind for injected links
                }, DURATION_MS);
            }).catch(function () {
                window.location.href = url;
            });

            return;
        }

        if (!root || prefersReducedMotion()) {
            window.location.href = url;
            return;
        }

        sessionStorage.setItem(STORAGE_KEY, direction);
        root.classList.add('is-auth-transitioning');

        if (direction === 'to-signup') {
            root.setAttribute('data-auth-exit', 'to-left');
        } else {
            root.setAttribute('data-auth-exit', 'to-right');
        }

        window.setTimeout(function () {
            window.location.href = url;
        }, DURATION_MS);
    }

    function bindSwitchLinks() {
        document.querySelectorAll('.auth-switch-link').forEach(function (link) {
            link.addEventListener('click', function (event) {
                var target = link.getAttribute('data-auth-target');
                var href = link.getAttribute('href');

                if (!target || !href) {
                    return;
                }

                event.preventDefault();

                var direction = target === 'signup' ? 'to-signup' : 'to-login';
                navigateWithTransition(href, direction);
            });
        });
    }

    function bindPopState() {
        window.addEventListener('popstate', function () {
            var url = window.location.href;
            var card = getUnifiedCard();
            var root = getAuthRoot();
            if (!card || !root || prefersReducedMotion()) {
                return;
            }

            var isSignup = /\/site\/signup\b/.test(url);
            var state = isSignup ? 'signup' : 'login';
            fetchPage(url).then(function (html) {
                var doc = parseHtml(html);
                if (!swapAuthPanes(doc, state)) return;
                setUnifiedState(state);
                rebindDynamicAuthHandlers();
                bindSwitchLinks();
            }).catch(function () {
                // ignore
            });
        });
    }

    function init() {
        runEnterAnimation();
        bindSwitchLinks();
        bindPopState();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
