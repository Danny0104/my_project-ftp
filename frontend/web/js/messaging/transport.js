/**
 * HTTP chat transport + Socket.IO realtime adapter.
 * @module Messaging.Transport
 */
(function (global) {
    'use strict';

    function resolveUrl(path) {
        if (!path) return path;
        if (/^https?:\/\//i.test(path)) return path;
        try {
            return new URL(path, global.location.href).href;
        } catch (e) {
            return path;
        }
    }

    function getCsrf(config) {
        config = config || {};
        var param = config.csrfParam;
        var token = config.csrfToken;
        if (!param || !token) {
            var paramMeta = document.querySelector('meta[name="csrf-param"]');
            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (paramMeta) param = paramMeta.getAttribute('content');
            if (tokenMeta) token = tokenMeta.getAttribute('content');
        }
        return {
            param: param || '_csrf-frontend',
            token: token || '',
        };
    }

    function csrfBody(config) {
        var csrf = getCsrf(config);
        var body = new URLSearchParams();
        body.set(csrf.param, csrf.token);
        return body;
    }

    function jsonFetch(url, options) {
        options = options || {};
        options.credentials = 'same-origin';
        options.headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }, options.headers || {});
        return fetch(resolveUrl(url), options).then(function (r) {
            var ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                throw new Error('Server returned non-JSON response (' + r.status + ')');
            }
            return r.json().then(function (data) {
                if (!r.ok && data && !data.message) {
                    data.message = 'Request failed (' + r.status + ')';
                }
                return data;
            });
        });
    }

    function HttpChatTransport(config) {
        this.config = config || {};
        this.chat = this.config.chat || {};
    }

    HttpChatTransport.prototype.ensure = function (params) {
        var url = new URL(resolveUrl(this.chat.ensureUrl));
        if (params.applicationId) url.searchParams.set('application_id', String(params.applicationId));
        if (params.notificationId) url.searchParams.set('notification_id', String(params.notificationId));
        if (params.conversationId) url.searchParams.set('conversation_id', String(params.conversationId));
        return jsonFetch(url.toString(), { method: 'GET' });
    };

    HttpChatTransport.prototype.fetchThread = function (conversationId, beforeId) {
        var url = new URL(resolveUrl(this.chat.threadUrl));
        url.searchParams.set('conversation_id', String(conversationId));
        if (beforeId) url.searchParams.set('before_id', String(beforeId));
        return jsonFetch(url.toString(), { method: 'GET' });
    };

    HttpChatTransport.prototype.sendMessage = function (payload) {
        var csrf = getCsrf(this.config);
        var form = new FormData();
        form.set(csrf.param, csrf.token);
        form.set('conversation_id', String(payload.conversationId));
        form.set('message', payload.message || '');
        if (payload.attachment) form.append('attachment', payload.attachment);
        return jsonFetch(this.chat.sendUrl, { method: 'POST', body: form });
    };

    HttpChatTransport.prototype.poll = function (conversationId, sinceId) {
        var url = new URL(resolveUrl(this.chat.pollUrl));
        url.searchParams.set('conversation_id', String(conversationId));
        url.searchParams.set('since_id', String(sinceId || 0));
        return jsonFetch(url.toString(), { method: 'GET' });
    };

    HttpChatTransport.prototype.setTyping = function (conversationId, typing) {
        var body = csrfBody(this.config);
        body.set('conversation_id', String(conversationId));
        body.set('typing', typing ? '1' : '0');
        return jsonFetch(this.chat.typingUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        });
    };

    HttpChatTransport.prototype.heartbeat = function () {
        var body = csrfBody(this.config);
        return jsonFetch(this.chat.heartbeatUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        });
    };

    HttpChatTransport.prototype.fetchUnreadCount = function () {
        var url = this.config.unreadCountUrl;
        if (!url) return Promise.resolve({ count: 0 });
        return jsonFetch(url, { method: 'GET' });
    };

    function SocketIOTransport(config, bus) {
        this.config = config || {};
        this.bus = bus;
        this.socket = null;
        this.connected = false;
        this._joinedConversation = null;
    }

    SocketIOTransport.prototype.connect = function () {
        var self = this;
        var url = this.config.websocketUrl;
        if (!url || typeof global.io === 'undefined') {
            return Promise.resolve(false);
        }
        return new Promise(function (resolve) {
            try {
                self.socket = global.io(url, {
                    transports: ['websocket', 'polling'],
                    query: { userId: String(self.config.currentUserId || '') },
                });
                self.socket.on('connect', function () {
                    self.connected = true;
                    self.bus.emit('realtime:connected', {});
                    resolve(true);
                });
                self.socket.on('disconnect', function () {
                    self.connected = false;
                    self.bus.emit('realtime:disconnected', {});
                });
                self.socket.on('message_sent', function (p) { self.bus.emit('chat:message', p); });
                self.socket.on('message_received', function (p) { self.bus.emit('chat:message', p); });
                self.socket.on('typing_started', function (p) { self.bus.emit('chat:typing', { typing: true, data: p }); });
                self.socket.on('typing_stopped', function (p) { self.bus.emit('chat:typing', { typing: false, data: p }); });
                self.socket.on('user_online', function (p) { self.bus.emit('chat:presence', p); });
                setTimeout(function () {
                    if (!self.connected) resolve(false);
                }, 4000);
            } catch (e) {
                resolve(false);
            }
        });
    };

    SocketIOTransport.prototype.disconnect = function () {
        if (this.socket) this.socket.disconnect();
        this.socket = null;
        this.connected = false;
    };

    SocketIOTransport.prototype.subscribeConversation = function (conversationId) {
        if (!this.socket || !conversationId) return;
        if (this._joinedConversation) {
            this.socket.emit('leave_conversation', this._joinedConversation);
        }
        this._joinedConversation = conversationId;
        this.socket.emit('join_conversation', conversationId);
    };

    SocketIOTransport.prototype.unsubscribeConversation = function () {
        if (this.socket && this._joinedConversation) {
            this.socket.emit('leave_conversation', this._joinedConversation);
        }
        this._joinedConversation = null;
    };

    global.Messaging = global.Messaging || {};
    global.Messaging.HttpChatTransport = HttpChatTransport;
    global.Messaging.SocketIOTransport = SocketIOTransport;
    global.Messaging.resolveUrl = resolveUrl;
})(typeof window !== 'undefined' ? window : this);
