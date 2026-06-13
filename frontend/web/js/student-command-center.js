/**
 * Student Command Center — cinematic reveals, counters, tilt, magnetic controls.
 */
(function () {
    'use strict';

    var REDUCE = function () {
        return document.body.classList.contains('sp-reduce-motion')
            || window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    };

    function animateCount(el, target, suffix) {
        if (REDUCE()) {
            el.textContent = String(target) + (suffix || '');
            return;
        }
        var duration = 1400;
        var start = performance.now();
        function tick(now) {
            var p = Math.min((now - start) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 4);
            el.textContent = String(Math.floor(target * eased)) + (suffix || '');
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }

    function initCounters(root) {
        root.querySelectorAll('[data-scc-count]').forEach(function (el) {
            if (el.__sccCounted) return;
            el.__sccCounted = true;
            var target = parseInt(el.getAttribute('data-scc-count'), 10) || 0;
            var suffix = el.getAttribute('data-scc-suffix') || '';
            if (window.PltLoading && typeof window.PltLoading.initCounters === 'function') {
                el.setAttribute('data-count', String(target));
                return;
            }
            animateCount(el, target, suffix);
        });
        if (window.PltLoading && typeof window.PltLoading.initCounters === 'function') {
            window.PltLoading.initCounters(root);
        }
    }

    function initReveal(root) {
        if (REDUCE()) {
            root.querySelectorAll('.scc-reveal').forEach(function (el) {
                el.classList.add('is-visible');
            });
            return;
        }

        var items = root.querySelectorAll('.scc-reveal');
        if (!('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                io.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

        items.forEach(function (el) { io.observe(el); });
    }

    function initHeroSequence(root) {
        var hero = root.querySelector('.scc-hero');
        if (!hero || REDUCE()) {
            root.classList.add('is-ready');
            return;
        }
        window.setTimeout(function () {
            hero.classList.add('is-mounted');
            window.setTimeout(function () {
                root.classList.add('is-ready');
            }, 420);
        }, 80);
    }

    function initTilt(root) {
        if (REDUCE()) return;
        root.querySelectorAll('[data-scc-tilt]').forEach(function (card) {
            card.addEventListener('mousemove', function (e) {
                var rect = card.getBoundingClientRect();
                var x = (e.clientX - rect.left) / rect.width - 0.5;
                var y = (e.clientY - rect.top) / rect.height - 0.5;
                card.style.setProperty('--scc-tilt-x', (y * -6).toFixed(2) + 'deg');
                card.style.setProperty('--scc-tilt-y', (x * 6).toFixed(2) + 'deg');
            });
            card.addEventListener('mouseleave', function () {
                card.style.setProperty('--scc-tilt-x', '0deg');
                card.style.setProperty('--scc-tilt-y', '0deg');
            });
        });
    }

    function initMagnetic(root) {
        if (REDUCE() || window.matchMedia('(max-width: 768px)').matches) return;
        root.querySelectorAll('[data-scc-magnetic]').forEach(function (btn) {
            btn.addEventListener('mousemove', function (e) {
                var rect = btn.getBoundingClientRect();
                var x = e.clientX - rect.left - rect.width / 2;
                var y = e.clientY - rect.top - rect.height / 2;
                btn.style.transform = 'translate(' + (x * 0.18).toFixed(1) + 'px,' + (y * 0.22).toFixed(1) + 'px)';
            });
            btn.addEventListener('mouseleave', function () {
                btn.style.transform = '';
            });
        });
    }

    function initOppCards(root) {
        root.querySelectorAll('.scc-opp').forEach(function (card) {
            var viewLink = card.querySelector('a[href*="position/view"]');
            if (!viewLink) return;
            card.style.cursor = 'pointer';
            card.addEventListener('click', function (e) {
                if (e.target.closest('button, .sp-save-btn')) return;
                if (e.target.closest('a')) return;
                window.location.href = viewLink.href;
            });
        });
    }

    function initSaveRipple(root) {
        root.querySelectorAll('[data-save-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                btn.classList.add('is-pulse');
                window.setTimeout(function () { btn.classList.remove('is-pulse'); }, 600);
                if (window.ftpShowToast) {
                    window.ftpShowToast(btn.classList.contains('saved') ? 'Removed from saved' : 'Opportunity saved');
                }
            });
        });
    }

    function hydrate(root) {
        root.classList.remove('is-loading');
        initHeroSequence(root);
        initReveal(root);
        initCounters(root);
        initTilt(root);
        initMagnetic(root);
        initSaveRipple(root);
        initOppCards(root);
    }

    function initPageTransition() {
        if (REDUCE()) return;
        document.querySelectorAll('.ftp-content a[href]').forEach(function (a) {
            if (a.origin !== window.location.origin) return;
            if (a.hasAttribute('data-method') || a.getAttribute('target') === '_blank') return;
            if (a.getAttribute('href').charAt(0) === '#') return;
            a.addEventListener('click', function (e) {
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                document.body.classList.add('scc-page-leaving');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('studentCommandCenter');
        if (!root) return;

        if (document.documentElement.getAttribute('data-theme') === 'dark') {
            root.classList.add('is-dark');
        }

        initPageTransition();

        window.setTimeout(function () {
            hydrate(root);
        }, 120);
    });
})();
