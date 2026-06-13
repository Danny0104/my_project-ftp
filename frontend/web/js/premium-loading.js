/**
 * Premium loading — page overlay, route progress, counters, hero sequence, nav transitions.
 */
(function (global) {
    'use strict';

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var MIN_LOADER_MS = 380;
    var loaderShownAt = Date.now();
    var routeTimer = null;
    var routeProgress = 0;

    var TRANSITION_ROUTES = [
        '/site/index',
        'r=site%2Findex',
        '/position/index',
        'r=position%2Findex',
        '/site/about',
        'r=site%2Fabout',
        '/site/contact',
        'r=site%2Fcontact',
    ];

    var MESSAGES = {
        position: 'Preparing Opportunities...',
        default: 'Loading Your Experience...',
    };

    function $(id) {
        return document.getElementById(id);
    }

    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    /* ── Route progress bar ───────────────────────────────────── */

    function getProgressEls() {
        return {
            wrap: $('pltRouteProgress'),
            bar: $('pltRouteProgressBar'),
        };
    }

    function startRouteProgress() {
        var els = getProgressEls();
        if (!els.wrap || !els.bar) {
            return;
        }
        clearInterval(routeTimer);
        routeProgress = 0.08;
        els.bar.style.width = '8%';
        els.wrap.classList.add('is-active');
        els.wrap.classList.remove('is-complete');
        els.wrap.setAttribute('aria-hidden', 'false');

        routeTimer = setInterval(function () {
            if (routeProgress < 0.9) {
                routeProgress += (0.9 - routeProgress) * 0.08;
                els.bar.style.width = Math.min(routeProgress * 100, 90) + '%';
            }
        }, 120);
    }

    function completeRouteProgress() {
        var els = getProgressEls();
        if (!els.wrap || !els.bar) {
            return;
        }
        clearInterval(routeTimer);
        els.wrap.classList.add('is-complete');
        els.bar.style.width = '100%';
        setTimeout(function () {
            els.wrap.classList.remove('is-active', 'is-complete');
            els.bar.style.width = '0%';
            els.wrap.setAttribute('aria-hidden', 'true');
            routeProgress = 0;
        }, 320);
    }

    /* ── Page loader ──────────────────────────────────────────── */

    function showPageLoader(isRoute) {
        document.documentElement.classList.remove('plt-ready');
        if (isRoute) {
            document.documentElement.classList.add('plt-route-loading');
        }
        loaderShownAt = Date.now();
        var loader = $('pltPageLoader');
        if (loader) {
            loader.setAttribute('aria-busy', 'true');
        }
    }

    function hidePageLoader() {
        var elapsed = Date.now() - loaderShownAt;
        var wait = Math.max(0, MIN_LOADER_MS - elapsed);

        setTimeout(function () {
            document.documentElement.classList.add('plt-ready');
            document.documentElement.classList.remove('plt-route-loading', 'plt-leaving');
            var loader = $('pltPageLoader');
            if (loader) {
                loader.setAttribute('aria-busy', 'false');
            }
            completeRouteProgress();
        }, wait);
    }

    function detectLoaderMessage() {
        var title = $('pltLoaderTitle');
        if (!title) {
            return;
        }
        var path = window.location.pathname + window.location.search;
        if (path.indexOf('position') >= 0) {
            title.textContent = MESSAGES.position;
        } else {
            title.textContent = MESSAGES.default;
        }
    }

    /* ── Animated counters ────────────────────────────────────── */

    function animateCounter(el) {
        if (el.getAttribute('data-plt-counted') === '1') {
            return;
        }
        el.setAttribute('data-plt-counted', '1');

        var raw = el.getAttribute('data-count') ?? el.getAttribute('data-pm-count');
        var target = raw != null ? parseFloat(raw) : parseFloat((el.textContent || '0').replace(/[^\d.]/g, ''));
        if (Number.isNaN(target)) {
            return;
        }

        var suffix = el.getAttribute('data-suffix') || el.getAttribute('data-count-suffix') || '';
        var prefix = el.getAttribute('data-prefix') || el.getAttribute('data-count-prefix') || '';
        var decimals = parseInt(el.getAttribute('data-count-decimals') || '0', 10);
        var duration = reduced ? 0 : 1500;
        var start = performance.now();

        function frame(now) {
            var p = duration ? Math.min((now - start) / duration, 1) : 1;
            var val = target * easeOutCubic(p);
            var display = decimals > 0 ? val.toFixed(decimals) : String(Math.floor(val));
            el.textContent = prefix + display + suffix;
            if (p < 1) {
                requestAnimationFrame(frame);
            } else {
                el.textContent = prefix + (decimals > 0 ? target.toFixed(decimals) : String(Math.floor(target))) + suffix;
            }
        }

        if (reduced) {
            el.textContent = prefix + String(target) + suffix;
        } else {
            requestAnimationFrame(frame);
        }
    }

    function initCounters(root) {
        var scope = root || document;
        var nodes = scope.querySelectorAll(
            '[data-count], [data-pm-count], .stat-number[data-count], .stat-number[data-count-final]'
        );
        if (!nodes.length) {
            return;
        }

        if (reduced) {
            nodes.forEach(animateCounter);
            return;
        }

        var io = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }
                    animateCounter(entry.target);
                    io.unobserve(entry.target);
                });
            },
            { threshold: 0.25, rootMargin: '0px 0px -40px 0px' }
        );

        nodes.forEach(function (el) {
            io.observe(el);
        });
    }

    /* ── Hero image load sequence ─────────────────────────────── */

    function initHeroLoad() {
        var hero = document.querySelector('.home-page .hero-section[data-hero-load]');
        if (!hero) {
            return;
        }

        if (reduced) {
            hero.classList.add('hero-is-ready');
            return;
        }

        var slides = hero.querySelectorAll('.hero-slide');
        var pending = slides.length || 1;
        var done = false;

        function markReady() {
            if (done) {
                return;
            }
            done = true;
            hero.classList.add('hero-is-ready');
            var home = hero.closest('.home-page');
            if (home && window.PltLoading) {
                window.PltLoading.initCounters(home);
            }
        }

        function onOneLoaded() {
            pending -= 1;
            if (pending <= 0) {
                markReady();
            }
        }

        if (!slides.length) {
            markReady();
            return;
        }

        slides.forEach(function (slide) {
            var bg = window.getComputedStyle(slide).backgroundImage;
            var m = bg && bg.match(/url\(["']?([^"')]+)["']?\)/);
            if (!m || !m[1]) {
                onOneLoaded();
                return;
            }
            var img = new Image();
            img.onload = onOneLoaded;
            img.onerror = onOneLoaded;
            img.src = m[1];
        });

        setTimeout(markReady, 2800);
    }

    /* ── Internal navigation ──────────────────────────────────── */

    function isInternalNav(href) {
        if (!href || href.indexOf('#') === 0) {
            return false;
        }
        try {
            var url = new URL(href, window.location.href);
            if (url.origin !== window.location.origin) {
                return false;
            }
            var path = url.pathname + url.search;
            return TRANSITION_ROUTES.some(function (r) {
                return path.indexOf(r) >= 0;
            });
        } catch (e) {
            return false;
        }
    }

    function initNavTransitions() {
        var body = document.body;
        if (!body.classList.contains('site-public-page')) {
            return;
        }

        document.addEventListener('click', function (e) {
            var link = e.target.closest('a[href]');
            if (!link || link.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey) {
                return;
            }
            if (!isInternalNav(link.getAttribute('href'))) {
                return;
            }
            e.preventDefault();
            startRouteProgress();
            showPageLoader(true);
            document.documentElement.classList.add('plt-leaving');
            setTimeout(function () {
                window.location.href = link.href;
            }, reduced ? 0 : 260);
        });
    }

    /* ── Scroll reveal (shared) ───────────────────────────────── */

    function initReveal(root) {
        var scope = root || document;
        var items = scope.querySelectorAll('.pm-reveal, .pp-reveal');
        if (!items.length) {
            return;
        }
        if (reduced) {
            items.forEach(function (el) {
                el.classList.add('is-visible');
            });
            return;
        }
        var io = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        io.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.1, rootMargin: '0px 0px -24px 0px' }
        );
        items.forEach(function (el, i) {
            if (el.classList.contains('pm-reveal')) {
                el.style.transitionDelay = Math.min(i % 6, 5) * 0.06 + 's';
            }
            io.observe(el);
        });
    }

    /* ── Boot ─────────────────────────────────────────────────── */

    function onReady() {
        detectLoaderMessage();
        startRouteProgress();
        initReveal();
        initCounters();
        initHeroLoad();
        initNavTransitions();
    }

    function onLoaded() {
        hidePageLoader();
    }

    if (document.body && document.body.classList.contains('site-public-page')) {
        showPageLoader(false);
        startRouteProgress();

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', onReady);
        } else {
            onReady();
        }

        if (document.readyState === 'complete') {
            onLoaded();
        } else {
            window.addEventListener('load', onLoaded);
        }
    }

    global.PltLoading = {
        show: showPageLoader,
        hide: hidePageLoader,
        startProgress: startRouteProgress,
        completeProgress: completeRouteProgress,
        animateCounter: animateCounter,
        initCounters: initCounters,
    };
})();
