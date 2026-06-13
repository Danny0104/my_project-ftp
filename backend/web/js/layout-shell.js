/**
 * Shared dashboard shell utilities (scroll lock, sidebar helpers).
 */
(function (global) {
    'use strict';

    var LOCK_CLASS = 'ft-shell-sidebar-open';

    function lockBodyScroll() {
        document.body.classList.add(LOCK_CLASS);
    }

    function unlockBodyScroll() {
        document.body.classList.remove(LOCK_CLASS);
    }

    global.ftLayoutShell = {
        lockBodyScroll: lockBodyScroll,
        unlockBodyScroll: unlockBodyScroll,
    };
})(window);
