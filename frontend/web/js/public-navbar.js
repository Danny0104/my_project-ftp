/**
 * Premium public navbar — scroll state, progress bar, mobile drawer, micro-interactions.
 */
(function () {
    'use strict';

    var SCROLL_THRESHOLD = 12;

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function setScrollProgress(progressEl) {
        var scrollTop = window.scrollY || document.documentElement.scrollTop;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        var pct = docHeight > 0 ? Math.min(100, (scrollTop / docHeight) * 100) : 0;
        progressEl.style.width = pct + '%';
    }

    function initScroll(header, progressEl) {
        var ticking = false;

        function update() {
            var scrolled = (window.scrollY || document.documentElement.scrollTop) > SCROLL_THRESHOLD;
            header.classList.toggle('is-scrolled', scrolled);
            setScrollProgress(progressEl);
            ticking = false;
        }

        function onScroll() {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(update);
            }
        }

        update();
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
    }

    function initSpotlight(header) {
        var spotlight = header.querySelector('.site-public-navbar__spotlight');
        if (!spotlight) {
            return;
        }

        header.addEventListener('mousemove', function (e) {
            var rect = header.getBoundingClientRect();
            var x = ((e.clientX - rect.left) / rect.width) * 100;
            var y = ((e.clientY - rect.top) / rect.height) * 100;
            spotlight.style.setProperty('--spotlight-x', x + '%');
            spotlight.style.setProperty('--spotlight-y', y + '%');
        });

        header.addEventListener('mouseleave', function () {
            spotlight.style.setProperty('--spotlight-x', '50%');
            spotlight.style.setProperty('--spotlight-y', '0%');
        });
    }

    function initRipples(root) {
        root.querySelectorAll('.site-public-navbar__btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                var rect = btn.getBoundingClientRect();
                var size = Math.max(rect.width, rect.height) * 2;
                var ripple = document.createElement('span');
                ripple.className = 'site-public-navbar__ripple';
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = e.clientX - rect.left - size / 2 + 'px';
                ripple.style.top = e.clientY - rect.top - size / 2 + 'px';
                btn.appendChild(ripple);
                ripple.addEventListener('animationend', function () {
                    ripple.remove();
                });
            });
        });
    }

    function initDrawer(toggle, drawer, backdrop, closeBtn) {
        if (!toggle || !drawer) {
            return;
        }

        function openDrawer() {
            drawer.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            toggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('site-public-navbar-drawer-open');
        }

        function closeDrawer() {
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            toggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('site-public-navbar-drawer-open');
        }

        toggle.addEventListener('click', function () {
            if (drawer.classList.contains('is-open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });

        if (backdrop) {
            backdrop.addEventListener('click', closeDrawer);
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeDrawer);
        }

        drawer.querySelectorAll('a[href]').forEach(function (link) {
            link.addEventListener('click', closeDrawer);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
                closeDrawer();
                toggle.focus();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992 && drawer.classList.contains('is-open')) {
                closeDrawer();
            }
        });
    }

    ready(function () {
        var header = document.getElementById('sitePublicNavbar');
        if (!header) {
            return;
        }

        var progressEl = document.getElementById('siteNavbarProgress');
        if (progressEl) {
            initScroll(header, progressEl);
        }

        initSpotlight(header);
        initRipples(header);

        var drawer = document.getElementById('sitePublicNavbarDrawer');
        initDrawer(
            document.getElementById('sitePublicNavbarToggle'),
            drawer,
            document.getElementById('sitePublicNavbarBackdrop'),
            document.getElementById('sitePublicNavbarClose')
        );

        if (drawer) {
            initRipples(drawer);
        }
    });
})();
