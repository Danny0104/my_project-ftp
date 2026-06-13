/**
 * Public page transitions — handled by premium-loading.js (PltLoading).
 * This file remains for asset bundle compatibility.
 */
(function () {
    'use strict';
    if (window.PltLoading) {
        return;
    }
    document.documentElement.classList.add('plt-ready');
})();
