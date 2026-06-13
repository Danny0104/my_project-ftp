/**
 * PJAX-safe bootstrap for messaging hubs.
 */
(function (global) {
    'use strict';

    function init() {
        if (!global.Messaging || !global.Messaging.Hub) return;
        if (global.Messaging.Hub.destroyAll) {
            global.Messaging.Hub.destroyAll();
        }
        global.Messaging.Hub.mountAll();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    document.addEventListener('pjax:end', init);
    document.addEventListener('yii:pjax:end', init);

    global.MessagingInit = init;
})(typeof window !== 'undefined' ? window : this);
