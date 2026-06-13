(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    function animateCounter(el) {
        var target = parseInt(el.getAttribute('data-count'), 10);
        var suffix = el.getAttribute('data-suffix') || '';
        var prefix = el.getAttribute('data-prefix') || '';
        if (Number.isNaN(target)) {
            return;
        }
        var duration = 1400;
        var start = performance.now();

        function frame(now) {
            var progress = Math.min(1, (now - start) / duration);
            var eased = 1 - Math.pow(1 - progress, 3);
            var value = Math.floor(target * eased);
            el.textContent = prefix + value.toLocaleString() + suffix;
            if (progress < 1) {
                requestAnimationFrame(frame);
            } else {
                el.textContent = prefix + target.toLocaleString() + suffix;
            }
        }

        requestAnimationFrame(frame);
    }

    function initCounters(root) {
        if (window.PltLoading) {
            window.PltLoading.initCounters(root);
            return;
        }
        var counters = root.querySelectorAll('[data-count]:not([data-count-done])');
        if (!counters.length) {
            return;
        }

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }
                    var el = entry.target;
                    el.setAttribute('data-count-done', 'true');
                    animateCounter(el);
                    observer.unobserve(el);
                });
            },
            { threshold: 0.35, rootMargin: '0px 0px -40px 0px' }
        );

        counters.forEach(function (el) {
            observer.observe(el);
        });
    }

    function initReveal(root) {
        var items = root.querySelectorAll('.pp-reveal');
        if (!items.length) {
            return;
        }

        var observer = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -30px 0px' }
        );

        items.forEach(function (el) {
            observer.observe(el);
        });
    }

    function initFaq(root) {
        root.querySelectorAll('[data-pp-faq-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('[data-pp-faq-item]');
                if (!item) {
                    return;
                }
                var isOpen = item.classList.contains('is-open');
                root.querySelectorAll('[data-pp-faq-item].is-open').forEach(function (openItem) {
                    openItem.classList.remove('is-open');
                    var toggle = openItem.querySelector('[data-pp-faq-toggle]');
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                });
                if (!isOpen) {
                    item.classList.add('is-open');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });
    }

    function initContactForm(root) {
        var form = root.querySelector('#contact-form');
        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                form.classList.add('was-validated');
                return;
            }
            var btn = form.querySelector('[type="submit"]');
            if (btn && !btn.classList.contains('is-loading')) {
                btn.classList.add('is-loading');
                btn.setAttribute('aria-busy', 'true');
            }
            form.classList.add('was-validated');
        });

        form.querySelectorAll('.pp-float-field input, .pp-float-field textarea').forEach(function (input) {
            function sync() {
                input.classList.toggle('has-value', input.value.trim() !== '');
            }
            input.addEventListener('input', sync);
            input.addEventListener('blur', sync);
            sync();
        });
    }

    function initSmoothAnchors(root) {
        root.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
            anchor.addEventListener('click', function (e) {
                var id = anchor.getAttribute('href');
                if (!id || id === '#') {
                    return;
                }
                var target = root.querySelector(id);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    ready(function () {
        var page = document.querySelector('.pp-page');
        if (!page) {
            return;
        }
        initCounters(page);
        initReveal(page);
        initFaq(page);
        initContactForm(page);
        initSmoothAnchors(page);
    });
})();
