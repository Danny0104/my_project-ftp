/**
 * Capability flags — never show UI states the backend cannot confirm.
 * @module Messaging.StatusPolicy
 */
(function (global) {
    'use strict';

    var DEFAULTS = {
        realtime: false,
        typingIndicators: false,
        onlinePresence: false,
        readReceipts: false,
        deliveryReceipts: false,
        attachments: false,
        twoWayChat: false,
        optimisticSend: true,
        pollUnreadMs: 45000,
    };

    function StatusPolicy(config) {
        this.capabilities = Object.assign({}, DEFAULTS, config && config.capabilities);
        this.role = (config && config.role) || 'student';
        if (config && config.chat && config.chat.ensureUrl) {
            this.capabilities.twoWayChat = true;
            this.capabilities.realtime = this.capabilities.realtime !== false;
            this.capabilities.attachments = true;
            this.capabilities.readReceipts = true;
            this.capabilities.deliveryReceipts = true;
            this.capabilities.typingIndicators = true;
            this.capabilities.onlinePresence = true;
        }
    }

    StatusPolicy.prototype.can = function (feature) {
        return !!this.capabilities[feature];
    };

    StatusPolicy.prototype.outboundStatusLabel = function (state) {
        if (!this.can('twoWayChat')) {
            switch (state) {
                case 'sending': return 'Sending…';
                case 'sent': return 'Sent';
                case 'failed': return 'Failed to send';
                default: return '';
            }
        }
        switch (state) {
            case 'sending': return 'Sending…';
            case 'sent': return 'Sent';
            case 'delivered': return 'Delivered';
            case 'read': return 'Seen';
            case 'failed': return 'Failed to send';
            case 'draft': return 'Draft';
            default: return '';
        }
    };

    StatusPolicy.prototype.inboundStatusLabel = function (ctx) {
        if (ctx && ctx.isRead) return 'Read';
        if (ctx && ctx.source === 'notification') return 'Notification';
        return 'Received';
    };

    global.Messaging = global.Messaging || {};
    global.Messaging.StatusPolicy = StatusPolicy;
})(typeof window !== 'undefined' ? window : this);
