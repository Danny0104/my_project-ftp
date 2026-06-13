/**
 * Lightweight pub/sub for messaging hub (WebSocket-ready).
 * @module Messaging.EventBus
 */
(function (global) {
    'use strict';

    function EventBus() {
        this._handlers = Object.create(null);
    }

    EventBus.prototype.on = function (event, fn) {
        if (!this._handlers[event]) this._handlers[event] = [];
        this._handlers[event].push(fn);
        return function () {
            this.off(event, fn);
        }.bind(this);
    };

    EventBus.prototype.off = function (event, fn) {
        var list = this._handlers[event];
        if (!list) return;
        this._handlers[event] = list.filter(function (h) { return h !== fn; });
    };

    EventBus.prototype.emit = function (event, payload) {
        var list = this._handlers[event];
        if (!list) return;
        list.slice().forEach(function (fn) {
            try { fn(payload); } catch (e) { console.error('[Messaging]', event, e); }
        });
    };

    global.Messaging = global.Messaging || {};
    global.Messaging.EventBus = EventBus;
})(typeof window !== 'undefined' ? window : this);
