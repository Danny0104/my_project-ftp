(function () {
    'use strict';

    var root = document.querySelector('.home-page');
    if (!root) return;

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function initAos() {
        if (typeof AOS === 'undefined' || reduced) return;
        AOS.init({
            once: true,
            offset: 72,
            easing: 'ease-out-cubic',
            duration: 650,
            disable: reduced,
        });
        AOS.refresh();
    }

    function splitHeadline() {
        var h1 = root.querySelector('[data-split-headline]');
        if (!h1 || reduced) return;
        var text = h1.textContent.trim();
        h1.textContent = '';
        h1.setAttribute('aria-label', text);
        text.split(/\s+/).forEach(function (word, i) {
            var span = document.createElement('span');
            span.className = 'hero-headline-word';
            span.style.setProperty('--word-i', String(i));
            span.textContent = word;
            h1.appendChild(span);
            if (i < text.split(/\s+/).length - 1) {
                h1.appendChild(document.createTextNode(' '));
            }
        });
    }

    function parseCount(el) {
        var raw = el.getAttribute('data-count');
        if (raw != null) return parseFloat(raw);
        var t = (el.getAttribute('data-count-final') || el.textContent).trim();
        return parseFloat(t.replace(/[^\d.]/g, '')) || 0;
    }

    function animateCount(el) {
        if (el.dataset.counted === '1') return;
        var target = parseCount(el);
        var suffix = el.getAttribute('data-count-suffix') || '';
        var prefix = el.getAttribute('data-count-prefix') || '';
        var decimals = parseInt(el.getAttribute('data-count-decimals') || '0', 10);
        var duration = reduced ? 0 : 1600;
        var start = performance.now();
        el.dataset.counted = '1';

        function tick(now) {
            var p = duration ? Math.min((now - start) / duration, 1) : 1;
            var eased = 1 - Math.pow(1 - p, 3);
            var val = target * eased;
            var display = decimals > 0 ? val.toFixed(decimals) : String(Math.floor(val));
            el.textContent = prefix + display + suffix;
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }

    function initCountUp() {
        if (window.PltLoading) {
            window.PltLoading.initCounters(root);
            return;
        }
        var stats = root.querySelectorAll(':scope > .stats-section .stat-number[data-count], :scope > .stats-section .stat-number[data-count-final]');
        if (!stats.length) return;

        if (reduced) return;

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                animateCount(entry.target);
                io.unobserve(entry.target);
            });
        }, { threshold: 0.35 });

        stats.forEach(function (el) { io.observe(el); });
    }

    function initScrollReveal() {
        var items = root.querySelectorAll('[data-reveal], :scope > .stats-section .stat-card, .positions-section .position-card, .positions-section .section-header');
        if (!items.length) return;

        if (reduced) {
            items.forEach(function (el) { el.classList.add('is-revealed'); });
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el = entry.target;
                var delay = parseInt(el.getAttribute('data-reveal-delay') || '0', 10);
                var stagger = parseInt(el.getAttribute('data-stagger-index') || '0', 10);
                var totalDelay = delay + stagger * 90;
                setTimeout(function () {
                    el.classList.add('is-revealed');
                }, totalDelay);
                io.unobserve(el);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        items.forEach(function (el) { io.observe(el); });
    }

    function initHeroParallax() {
        if (reduced) return;
        var hero = root.querySelector('.hero-section');
        var slides = root.querySelectorAll('.hero-slide');
        var visual = root.querySelector('[data-parallax-visual]');
        var orbs = root.querySelectorAll('.hero-orb');
        if (!hero) return;

        var ticking = false;
        window.addEventListener('scroll', function () {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(function () {
                var rect = hero.getBoundingClientRect();
                var progress = Math.max(0, Math.min(1, -rect.top / (rect.height || 1)));
                var y = progress * 40;
                slides.forEach(function (slide) {
                    slide.style.transform = 'translate3d(0, ' + (y * 0.35) + 'px, 0) scale(' + (1.05 + progress * 0.03) + ')';
                });
                if (visual) {
                    visual.style.transform = 'translate3d(0, ' + (y * 0.2) + 'px, 0)';
                }
                orbs.forEach(function (orb, i) {
                    orb.style.transform = 'translate3d(0, ' + (y * (0.15 + i * 0.05)) + 'px, 0)';
                });
                ticking = false;
            });
        }, { passive: true });
    }

    function initMagneticButtons() {
        if (reduced || !window.matchMedia('(hover: hover)').matches) return;
        root.querySelectorAll('[data-magnetic]').forEach(function (btn) {
            btn.addEventListener('mousemove', function (e) {
                var r = btn.getBoundingClientRect();
                var x = e.clientX - r.left - r.width / 2;
                var y = e.clientY - r.top - r.height / 2;
                btn.style.transform = 'translate3d(' + (x * 0.12) + 'px, ' + (y * 0.12 - 2) + 'px, 0)';
            });
            btn.addEventListener('mouseleave', function () {
                btn.style.transform = '';
            });
        });
    }

    function initRipple() {
        root.querySelectorAll('.btn-hero').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                var ripple = document.createElement('span');
                ripple.className = 'btn-ripple';
                var size = Math.max(btn.offsetWidth, btn.offsetHeight);
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = (e.clientX - btn.getBoundingClientRect().left - size / 2) + 'px';
                ripple.style.top = (e.clientY - btn.getBoundingClientRect().top - size / 2) + 'px';
                btn.appendChild(ripple);
                setTimeout(function () { ripple.remove(); }, 650);
            });
        });
    }

    function initCardTilt() {
        if (reduced || !window.matchMedia('(hover: hover)').matches) return;
        root.querySelectorAll('[data-tilt]').forEach(function (card) {
            card.addEventListener('mousemove', function (e) {
                var r = card.getBoundingClientRect();
                var x = (e.clientX - r.left) / r.width - 0.5;
                var y = (e.clientY - r.top) / r.height - 0.5;
                card.style.transform = 'perspective(800px) rotateX(' + (-y * 6) + 'deg) rotateY(' + (x * 6) + 'deg) translate3d(0, -6px, 0)';
            });
            card.addEventListener('mouseleave', function () {
                if (card.classList.contains('is-revealed')) {
                    card.style.transform = '';
                }
            });
        });
    }

    function initSpotlight() {
        var grid = root.querySelector('.hm-spotlight-grid');
        if (!grid || !window.matchMedia('(hover: hover)').matches) return;
        grid.addEventListener('mousemove', function (e) {
            var r = grid.getBoundingClientRect();
            grid.style.setProperty('--spot-x', ((e.clientX - r.left) / r.width * 100) + '%');
            grid.style.setProperty('--spot-y', ((e.clientY - r.top) / r.height * 100) + '%');
            grid.classList.add('is-spotlight-active');
        });
        grid.addEventListener('mouseleave', function () {
            grid.classList.remove('is-spotlight-active');
        });
    }

    function initSmoothAnchors() {
        document.querySelectorAll('.home-page a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (e) {
                var id = this.getAttribute('href');
                if (!id || id === '#') return;
                var target = document.querySelector(id);
                if (!target) return;
                e.preventDefault();
                target.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
            });
        });
    }

    function preloadHeroImages() {
        root.querySelectorAll('.hero-slide').forEach(function (slide) {
            var bg = getComputedStyle(slide).backgroundImage;
            var m = bg && bg.match(/url\(["']?([^"')]+)["']?\)/);
            if (m && m[1]) {
                var img = new Image();
                img.src = m[1];
                img.onload = function () { slide.classList.add('hm-img-fade', 'is-loaded'); };
            }
        });
    }

    function boot() {
        splitHeadline();
        initScrollReveal();
        initCountUp();
        initHeroParallax();
        initMagneticButtons();
        initRipple();
        initCardTilt();
        initSpotlight();
        initSmoothAnchors();
        preloadHeroImages();
        initAos();

        root.querySelectorAll('.positions-section .position-card').forEach(function (card, i) {
            card.setAttribute('data-stagger-index', String(i));
        });
        root.querySelectorAll(':scope > .stats-section .stat-card').forEach(function (card, i) {
            card.setAttribute('data-stagger-index', String(i));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
