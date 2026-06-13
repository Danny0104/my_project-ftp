/**
 * Student portfolio profile — share link & bio expand
 */
(function () {
    'use strict';

    function initAboutExpand() {
        var btn = document.getElementById('spProfAboutToggle');
        var body = document.getElementById('spProfAboutText');
        if (!btn || !body) return;

        btn.addEventListener('click', function () {
            var collapsed = body.classList.toggle('is-collapsed');
            btn.textContent = collapsed ? 'Read more' : 'Show less';
        });
    }

    function initShare() {
        var btn = document.getElementById('spProfShareBtn');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var url = window.location.href;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () {
                    if (window.ftpShowToast) {
                        window.ftpShowToast('Profile link copied');
                    }
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAboutExpand();
        initShare();
    });
})();
