(function () {
    'use strict';

    var page = document.getElementById('pmPage');
    if (!page) {
        return;
    }

    var config = window.pmMarketplaceConfig || {};
    var results = document.getElementById('pmResults');
    var form = document.getElementById('pmFiltersForm');
    var sortSelect = document.querySelector('[data-pm-sort]');

    document.documentElement.classList.add('pm-initial-load');

    function setLoading(on) {
        if (!results) {
            return;
        }
        results.classList.toggle('is-loading', on);
        results.setAttribute('aria-busy', on ? 'true' : 'false');
    }

    function finishInitialLoad() {
        document.documentElement.classList.add('pm-ready');
        document.documentElement.classList.remove('pm-initial-load');
        setLoading(false);
    }

    if (document.readyState === 'complete') {
        window.requestAnimationFrame(finishInitialLoad);
    } else {
        window.addEventListener('load', finishInitialLoad);
    }

    if (form) {
        form.addEventListener('submit', function () {
            setLoading(true);
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            var params = new URLSearchParams(window.location.search);
            params.set('sort', sortSelect.value);
            if (form) {
                ['title', 'location', 'field', 'organization_id', 'duration'].forEach(function (name) {
                    var el = form.querySelector('[name="' + name + '"]');
                    if (!el) {
                        return;
                    }
                    var val = el.value;
                    if (val) {
                        params.set(name, val);
                    } else {
                        params.delete(name);
                    }
                });
            }
            params.delete('page');
            setLoading(true);
            window.location.href = (config.baseUrl || window.location.pathname) + '?' + params.toString();
        });
    }

    document.querySelectorAll('.pm-pagination__list a').forEach(function (link) {
        link.addEventListener('click', function () {
            setLoading(true);
        });
    });

    if (window.PltLoading) {
        window.PltLoading.initCounters(page);
    }
})();
