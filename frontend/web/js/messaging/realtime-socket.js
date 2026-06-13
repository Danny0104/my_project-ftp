/**
 * Registers Socket.IO client dependency hook (loaded via MessagingCoreAsset CDN).
 */
(function (global) {
    'use strict';
    global.Messaging = global.Messaging || {};
    global.Messaging.realtimeReady = typeof global.io !== 'undefined';
})(typeof window !== 'undefined' ? window : this);
