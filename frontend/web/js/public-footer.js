(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    ready(function () {
        var btn = document.getElementById('backToTop');
        if (!btn) {
            return;
        }

        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function onScroll() {
            btn.classList.toggle('is-visible', window.pageYOffset > 300);
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        btn.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
        });
    });
})();
