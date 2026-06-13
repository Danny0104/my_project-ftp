/**
 * Messaging hub — live two-way chat with Socket.IO + HTTP fallback.
 * @module Messaging.Hub
 */
(function (global) {
    'use strict';

    var M = global.Messaging;

    function parseConfig(el) {
        var config = {};
        var script = document.getElementById('messaging-hub-config');
        if (script && script.textContent) {
            try { config = JSON.parse(script.textContent); } catch (e) { /* */ }
        }
        if (!Object.keys(config).length) {
            var raw = el.getAttribute('data-messaging-config');
            if (raw) {
                try { config = JSON.parse(raw); } catch (e) { /* */ }
            }
        }
        if (global.MessagingConfig) {
            if (global.MessagingConfig.onContextUpdate) config.onContextUpdate = global.MessagingConfig.onContextUpdate;
            if (global.MessagingConfig.onToast) config.onToast = global.MessagingConfig.onToast;
        }
        return Hub.normalizeConfig(config);
    }

    Hub.normalizeConfig = function (config) {
        config = config || {};
        if (!config.csrfParam || !config.csrfToken) {
            var paramMeta = document.querySelector('meta[name="csrf-param"]');
            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (paramMeta) config.csrfParam = paramMeta.getAttribute('content');
            if (tokenMeta) config.csrfToken = tokenMeta.getAttribute('content');
        }
        if (!config.chat) config.chat = {};
        var routeBase = Hub.inferRouteBase(config);
        var routes = {
            ensureUrl: 'message/ensure',
            threadUrl: 'message/thread',
            sendUrl: 'message/send',
            pollUrl: 'message/poll',
            typingUrl: 'message/typing',
            heartbeatUrl: 'message/heartbeat',
            markReadUrl: 'message/mark-read',
            markUnreadUrl: 'message/mark-unread',
            archiveUrl: 'message/archive',
        };
        Object.keys(routes).forEach(function (key) {
            if (!config.chat[key]) {
                config.chat[key] = routeBase + routes[key];
            }
        });
        return config;
    };

    Hub.truncatePreview = function (text, max) {
        if (!text) return '';
        max = max || 120;
        if (typeof Intl !== 'undefined' && Intl.Segmenter) {
            var seg = new Intl.Segmenter(undefined, { granularity: 'grapheme' });
            var out = '';
            var n = 0;
            var segments = seg.segment(text);
            var iter = segments[Symbol.iterator] ? segments[Symbol.iterator]() : null;
            if (iter) {
                var step;
                while (!(step = iter.next()).done && n < max) {
                    out += step.value.segment;
                    n += 1;
                }
                return out.length < text.length ? out + '…' : out;
            }
        }
        var chars = Array.from(text);
        if (chars.length <= max) return text;
        return chars.slice(0, max).join('') + '…';
    };

    Hub.prototype._updateGlobalUnreadBadges = function (count) {
        document.querySelectorAll('[data-msg-unread-badge]').forEach(function (el) {
            el.textContent = String(count);
            el.setAttribute('data-count', String(count));
            if (count > 0) {
                el.hidden = false;
            } else {
                el.hidden = true;
            }
        });
        document.querySelectorAll('[data-nav-badge="messages"]').forEach(function (el) {
            if (count > 0) {
                el.textContent = String(count);
                el.hidden = false;
            } else {
                el.textContent = '';
                el.hidden = true;
            }
        });
    };

    Hub.inferRouteBase = function (config) {
        var sample = config.unreadCountUrl || config.markReadUrl || '';
        var marker = 'r=';
        var idx = sample.indexOf(marker);
        if (idx >= 0) {
            return sample.substring(0, idx + marker.length);
        }
        var path = global.location.pathname || '/index.php';
        if (path.indexOf('index.php') < 0) path = path.replace(/\/?$/, '/index.php');
        return path + '?r=';
    };

    function Hub(el) {
        var self = this;
        this.el = el;
        this.config = parseConfig(el);
        this.bus = new M.EventBus();
        this.policy = new M.StatusPolicy(this.config);
        this.store = new M.ConversationStore(this.bus);
        this.renderer = new M.MessageRenderer(this.policy);
        this.transport = new M.HttpChatTransport(this.config);
        this.realtime = new M.SocketIOTransport(this.config, this.bus);
        this.composer = new M.Composer(el.querySelector('[data-msg-composer]'), this.policy, this.bus);

        this.threadBody = el.querySelector('[data-msg-thread-body]');
        this.emptyState = el.querySelector('[data-msg-empty]');
        this.threadPanel = el.querySelector('[data-msg-thread]');
        this.threadTitle = el.querySelector('[data-msg-thread-title]');
        this.presenceEl = el.querySelector('[data-msg-presence]');
        this.typingEl = el.querySelector('[data-msg-typing]');
        if (!this.typingEl) {
            this.typingEl = document.createElement('div');
            this.typingEl.className = 'msg-typing-indicator';
            this.typingEl.setAttribute('data-msg-typing', '');
            this.typingEl.hidden = true;
        }
        this.detailPanel = el.querySelector('[data-msg-detail]');
        this.toastStack = document.getElementById(this.config.toastStackId || 'msgToastStack');

        this._lastMessageId = 0;
        this._pollTimer = null;
        this._heartbeatTimer = null;
        this._typingTimer = null;
        this._loadingThread = false;
        this._socketOk = false;

        this._bindStore();
        this._bindComposer();
        this._bindRealtime();
        this._initConversations();
        this._bindFilters();
        this._bindSearch();
        this._bindMobile();
        this._bindArchive();
        this._startUnreadPoll();
        this._startHeartbeat();

        if (M.Features) {
            this.features = new M.Features(this);
            this.composer.onAttachmentChange = function (file) {
                self.features.updateAttachmentPreview(file);
            };
        }

        if (this.policy.can('realtime')) {
            this.realtime.connect().then(function (ok) {
                self._socketOk = !!ok;
            });
        }
    }

    Hub.prototype.toast = function (msg, type) {
        if (this.config.onToast) {
            this.config.onToast(msg, type);
            return;
        }
        if (!this.toastStack) return;
        var el = document.createElement('div');
        el.className = 'msg-toast' + (type ? ' msg-toast--' + type : '');
        el.textContent = msg;
        this.toastStack.appendChild(el);
        setTimeout(function () {
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 280);
        }, 3200);
    };

    Hub.prototype._bindStore = function () {
        var self = this;
        this.bus.on('conversation:changed', function (conv) {
            if (!conv) return;
            self.el.setAttribute('data-active-id', conv.id);
            if (self.threadPanel) {
                self.threadPanel.classList.add('msg-thread-switching');
                setTimeout(function () {
                    if (self.threadPanel) self.threadPanel.classList.remove('msg-thread-switching');
                }, 220);
            }
            self._loadChatThread(conv);
            self._updateDetail(conv);
            self._updateToolbar(conv);
            if (self.features) self.features.onConversationChanged(conv);
        });

        this.bus.on('message:outbound-preview', function (msg) {
            if (self.threadBody && self.policy.can('optimisticSend')) {
                self.renderer.appendBubble(self.threadBody, msg, self.policy);
            }
        });

        this.bus.on('message:outbound-confirmed', function (payload) {
            var bubble = self.threadBody && self.threadBody.querySelector('[data-msg-id="' + payload.tempId + '"]');
            if (bubble && payload.server) {
                bubble.setAttribute('data-msg-id', String(payload.server.id));
                bubble.setAttribute('data-msg-state', payload.server.state || 'sent');
                var meta = bubble.querySelector('.msg-bubble-meta');
                if (meta) meta.textContent = payload.server.statusLabel || self.policy.outboundStatusLabel('sent');
            }
        });

        this.bus.on('chat:message', function (msg) {
            self._onIncomingMessage(msg);
        });

        this.bus.on('chat:typing', function (ev) {
            self._showTyping(ev && ev.typing);
        });
    };

    Hub.prototype._bindRealtime = function () {
        var self = this;
        this.bus.on('realtime:disconnected', function () {
            self._socketOk = false;
            self._startPollLoop();
        });
        this.bus.on('realtime:connected', function () {
            self._socketOk = true;
        });
    };

    Hub.prototype._bindComposer = function () {
        var self = this;
        this.composer.bind();
        this.bus.on('composer:submit-request', function () { self._handleSend(); });
        this.bus.on('composer:input', function () { self._handleTypingInput(); });
    };

    Hub.prototype._initConversations = function () {
        var self = this;
        var items = this.el.querySelectorAll('[data-conversation-id]');
        items.forEach(function (item) {
            self.store.registerFromDom(item);
            item.addEventListener('click', function () { self._selectItem(item); });
            item.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    self._selectItem(item);
                }
            });
        });

        var activeId = this.el.getAttribute('data-active-id');
        var first = activeId
            ? this.el.querySelector('[data-conversation-id="' + activeId + '"]')
            : (this.el.querySelector('[data-conversation-id].is-active') || items[0]);
        if (first) this._selectItem(first);
    };

    Hub.prototype._selectItem = function (item) {
        this.el.querySelectorAll('[data-conversation-id]').forEach(function (i) {
            i.classList.remove('is-active');
            i.setAttribute('aria-selected', 'false');
        });
        item.classList.add('is-active');
        item.setAttribute('aria-selected', 'true');

        if (this.emptyState) this.emptyState.hidden = true;
        if (this.threadPanel) this.threadPanel.hidden = false;
        if (this.detailPanel) {
            this.detailPanel.removeAttribute('hidden');
        }

        var id = item.getAttribute('data-conversation-id');
        this.store.setActive(id);

        if (item.classList.contains('unread') && item.getAttribute('data-conv-source') === 'chat') {
            this._markChatReadSilently(item.getAttribute('data-chat-conversation-id') || id);
        } else if (item.classList.contains('unread') && item.getAttribute('data-conv-source') === 'notification') {
            this._markNotificationReadSilently(id);
        }
    };

    Hub.prototype._isReadOnlyNotification = function (conv) {
        if (!conv || conv.source !== 'notification') return false;
        var sender = conv.senderType || (conv.ctx && conv.ctx.senderType) || '';
        return sender === 'admin' || sender === 'system';
    };

    Hub.prototype._loadChatThread = function (conv) {
        var self = this;
        if (this.threadTitle) this.threadTitle.textContent = conv.title;

        if (conv.source === 'chat') {
            conv.chatConversationId = conv.chatConversationId || parseInt(conv.id, 10);
            conv.chatEnabled = true;
        }

        if (this._isReadOnlyNotification(conv)) {
            this._renderAnnouncementThread(conv);
            this._updateComposerFor(conv, false, 'announcement');
            this._stopPollLoop();
            this.realtime.unsubscribeConversation();
            return;
        }

        this._updateComposerFor(conv, true);
        if (conv.chatConversationId) {
            this._renderMessages(conv.messages || []);
            this._subscribeChat(conv.chatConversationId);
            this._startPollLoop();
            return;
        }

        if (!this._shouldAttemptChat(conv)) {
            this._renderLegacyThread(conv);
            this._updateComposerFor(conv, false, 'readonly');
            this._stopPollLoop();
            this.realtime.unsubscribeConversation();
            return;
        }

        if (this._loadingThread) return;
        this._loadingThread = true;
        if (this.threadBody) {
            this.threadBody.innerHTML = '<div class="msg-loading">Loading conversation…</div>';
        }

        var params = this._ensureParams(conv);

        this.transport.ensure(params).then(function (res) {
            self._loadingThread = false;
            if (!res || !res.success) {
                self.toast((res && res.message) || 'Could not open live chat', 'error');
                self._renderLegacyThread(conv);
                self._updateComposerFor(conv, false, 'failed');
                return;
            }
            conv.chatConversationId = res.conversation.id;
            conv.chatEnabled = true;
            conv.messages = res.messages || [];
            self._lastMessageId = self._maxMessageId(conv.messages);
            self.store.conversations.set(conv.id, conv);
            if (conv.messages.length) {
                self._renderMessages(conv.messages);
            } else {
                self._renderWelcomeThread(conv);
            }
            self._subscribeChat(conv.chatConversationId);
            self._updateComposerFor(conv, true);
            self._startPollLoop();
            if (self.presenceEl) {
                self.presenceEl.innerHTML = '<span class="msg-presence-dot msg-presence-dot--online"></span> Live chat';
            }
            if (conv.messages.length) {
                self._updateInboxPreview(conv, conv.messages[conv.messages.length - 1]);
            }
        }).catch(function (err) {
            self._loadingThread = false;
            var msg = (err && err.message) ? err.message : 'Network error loading chat';
            self.toast(msg, 'error');
            self._renderLegacyThread(conv);
            self._updateComposerFor(conv, false, 'failed');
        });
    };

    Hub.prototype._shouldAttemptChat = function (conv) {
        if (!conv) return false;
        if (conv.source === 'chat') return true;
        if (conv.source === 'application') return true;
        if (conv.source === 'notification' && !this._isReadOnlyNotification(conv)) return true;
        return !!(this.config.chat && this.config.chat.ensureUrl);
    };

    Hub.prototype._ensureParams = function (conv) {
        var params = {};
        if (conv.source === 'chat' || conv.chatConversationId) {
            params.conversationId = parseInt(conv.chatConversationId || conv.id, 10);
            return params;
        }
        if (conv.source === 'application') {
            var appId = (conv.ctx && conv.ctx.applicationId)
                || parseInt(String(conv.id).replace(/^app-/, ''), 10)
                || parseInt(conv.element && conv.element.getAttribute('data-application-id'), 10);
            if (appId) params.applicationId = appId;
        } else if (conv.source === 'notification' && conv.ctx) {
            if (conv.ctx.id) params.notificationId = conv.ctx.id;
            var actionUrl = conv.ctx.actionUrl || conv.actionUrl || '';
            var match = actionUrl.match(/[?&]chat=(\d+)/);
            if (match) params.conversationId = parseInt(match[1], 10);
        }
        return params;
    };

    Hub.prototype._renderAnnouncementThread = function (conv) {
        if (!this.threadBody) return;
        var title = (conv.ctx && conv.ctx.title) || conv.title || 'Platform announcement';
        var body = (conv.ctx && conv.ctx.message) || conv.preview || '';
        var time = (conv.ctx && conv.ctx.time) || conv.time || '';
        var sender = conv.senderType || (conv.ctx && conv.ctx.senderType) || 'system';
        var senderLabel = sender === 'admin' ? 'Admin' : 'Platform';
        this.threadBody.innerHTML =
            '<div class="msg-announcement">' +
            '<div class="msg-announcement-badge"><i class="fas fa-bullhorn"></i> ' + senderLabel + ' announcement</div>' +
            '<h3 class="msg-announcement-title">' + this._escape(title) + '</h3>' +
            '<p class="msg-announcement-body">' + this._escape(body) + '</p>' +
            (time ? '<p class="msg-announcement-meta">Received ' + this._escape(time) + '</p>' : '') +
            '<p class="msg-announcement-hint">This is not a chat thread. When a recruiter messages you, it will appear under <strong>Organizations</strong> with a <strong>Live chat</strong> badge.</p>' +
            '</div>';
        if (this.presenceEl) {
            this.presenceEl.innerHTML = '<span class="msg-presence-dot"></span> Announcement · read only';
        }
    };

    Hub.prototype._escape = function (str) {
        var d = document.createElement('div');
        d.textContent = str == null ? '' : String(str);
        return d.innerHTML;
    };

    Hub.prototype._renderWelcomeThread = function (conv) {
        if (!this.threadBody) return;
        var name = (conv.ctx && conv.ctx.studentName) || conv.title || 'there';
        var role = (conv.ctx && conv.ctx.roleTitle) || '';
        var intro = this.config.role === 'organization'
            ? 'Say hello to ' + name + (role ? ' about ' + role : '') + '. Messages deliver instantly.'
            : 'Reply to your recruiter here. Messages deliver instantly.';
        this.threadBody.innerHTML =
            '<div class="msg-welcome">' +
            '<i class="fas fa-comments"></i>' +
            '<h3>Start the conversation</h3>' +
            '<p>' + intro + '</p>' +
            '</div>';
        if (this.typingEl && this.typingEl.parentNode !== this.threadBody) {
            this.threadBody.appendChild(this.typingEl);
        }
    };

    Hub.prototype._renderLegacyThread = function (conv) {
        var messages = [];
        if (conv.source === 'application') {
            messages = this.renderer.buildFromApplication(conv.ctx);
        } else {
            messages = this.renderer.buildFromNotification({
                id: conv.id,
                title: conv.ctx.title || conv.title,
                message: conv.ctx.message || conv.preview,
                isRead: !conv.isUnread,
                time: conv.ctx.time || conv.time,
            });
        }
        this.renderer.renderThread(this.threadBody, messages, { defaultDateKey: 'Today' });
        if (this.presenceEl) this.presenceEl.textContent = 'Read-only thread';
    };

    Hub.prototype._renderMessages = function (messages) {
        this.renderer.renderThread(this.threadBody, messages, {
            defaultDateKey: 'Today',
            typingEl: this.typingEl,
        });
        if (this.features && this.threadBody) {
            var self = this;
            this.threadBody.querySelectorAll('.msg-bubble-wrap').forEach(function (wrap) {
                var id = wrap.getAttribute('data-msg-id');
                var msg = (messages || []).find(function (m) { return String(m.id) === String(id); });
                if (msg) self.features.enhanceBubble(wrap, msg);
            });
        }
    };

    Hub.prototype._subscribeChat = function (conversationId) {
        if (this.policy.can('realtime') && conversationId) {
            this.realtime.subscribeConversation(conversationId);
        }
    };

    Hub.prototype._maxMessageId = function (messages) {
        var max = 0;
        (messages || []).forEach(function (m) {
            var id = parseInt(m.id, 10);
            if (id > max) max = id;
        });
        return max;
    };

    Hub.prototype._onIncomingMessage = function (msg) {
        if (!msg || !msg.conversationId) return;
        var conv = this.store.getActive();
        if (!conv || conv.chatConversationId !== msg.conversationId) return;
        var msgId = parseInt(msg.id, 10);
        if (msgId && this.threadBody && this.threadBody.querySelector('[data-msg-id="' + msgId + '"]')) return;
        if (msgId <= this._lastMessageId) return;
        this._lastMessageId = parseInt(msg.id, 10);
        var welcome = this.threadBody && this.threadBody.querySelector('.msg-welcome');
        if (welcome) welcome.remove();
        if (!conv.messages) conv.messages = [];
        conv.messages.push(msg);
        this.renderer.appendBubble(this.threadBody, msg, this.policy);
        this._updateInboxPreview(conv, msg);
        if (msg.direction === 'in') {
            this.toast('New message', 'info');
            if (this.policy.can('readReceipts')) {
                /* read marked on poll/thread load */
            }
        }
    };

    Hub.prototype._updateInboxPreview = function (conv, lastMsg) {
        if (!conv.element || !lastMsg) return;
        var preview = conv.element.querySelector('.sp-conv-preview');
        if (preview) preview.textContent = Hub.truncatePreview(lastMsg.body || '', 120);
        var time = conv.element.querySelector('.sp-conv-time');
        if (time && lastMsg.timeLabel) time.textContent = lastMsg.timeLabel;
        var list = conv.element.parentElement;
        if (list && conv.element.parentNode) {
            list.insertBefore(conv.element, list.firstChild);
        }
    };

    Hub.prototype._updateComposerFor = function (conv, chatReady, reasonCode) {
        this.composer.setConversationId(conv.chatConversationId || conv.id);
        if (chatReady && conv.chatConversationId) {
            if (this.composer.root) this.composer.root.classList.remove('msg-composer--readonly');
            this.composer.setEnabled(true, 'Enter to send · Shift+Enter for new line');
            return;
        }
        if (chatReady && !conv.chatConversationId) {
            this.composer.setEnabled(false, 'Opening live chat…');
            return;
        }
        var sender = conv.senderType || (conv.ctx && conv.ctx.senderType) || '';
        var note;
        if (reasonCode === 'announcement') {
            note = 'Platform announcements cannot be replied to.';
            if (this.composer.root) this.composer.root.classList.add('msg-composer--readonly');
        } else if (this.composer.root) {
            this.composer.root.classList.remove('msg-composer--readonly');
        }
        if (reasonCode !== 'announcement') {
            if (reasonCode === 'failed') {
            note = conv.source === 'application'
                ? 'Could not load chat — hard refresh (Ctrl+F5) and try again.'
                : (sender === 'organization'
                    ? 'Could not load chat — refresh and try again.'
                    : 'Select a recruiter thread to reply.');
        } else if (reasonCode === 'readonly') {
            note = conv.source === 'application'
                ? 'Chat unavailable for this thread.'
                : (sender === 'admin' || sender === 'system'
                    ? 'Platform announcements are read-only.'
                    : 'Select a conversation you can reply to.');
        } else if (sender === 'admin' || sender === 'system') {
            note = 'Platform announcements are read-only.';
        } else {
            note = 'Select a conversation to message.';
        }
        }
        this.composer.setEnabled(false, note);
    };

    Hub.prototype._updateDetail = function (conv) {
        if (this.config.onContextUpdate) this.config.onContextUpdate(conv, this.el);
    };

    Hub.prototype._updateToolbar = function (conv) {
        var markBtn = document.getElementById('spMarkReadActive');
        var delBtn = document.getElementById('spDeleteActive');
        var isNotif = conv.source === 'notification' && !conv.chatConversationId;
        if (markBtn) markBtn.style.display = isNotif ? '' : 'none';
        if (delBtn) delBtn.style.display = isNotif ? '' : 'none';
    };

    Hub.prototype._handleSend = function () {
        var conv = this.store.getActive();
        if (!conv || !conv.chatConversationId) return;

        var validation = this.composer.validate();
        if (!validation.ok) {
            this.toast(validation.error, 'warn');
            return;
        }

        var body = this.composer.getValue();
        if (this.features && this.features.getReplyPrefix()) {
            body = this.features.getReplyPrefix() + body;
        }
        var attachment = this.composer.getAttachment();
        var tempId = 'tmp-' + Date.now();
        var preview = {
            id: tempId,
            direction: 'out',
            body: body || (attachment ? '[Attachment]' : ''),
            dateKey: 'Today',
            statusLabel: this.policy.outboundStatusLabel('sending'),
            state: 'sending',
        };

        if (this.policy.can('optimisticSend')) {
            var welcome = this.threadBody && this.threadBody.querySelector('.msg-welcome');
            if (welcome) welcome.remove();
            this.store.addOutboundPreview(preview);
        }

        var sendBtn = this.composer.sendBtn;
        if (sendBtn) sendBtn.classList.add('is-sending');
        this.composer.setEnabled(false, 'Sending…');

        var self = this;
        this.transport.sendMessage({
            conversationId: conv.chatConversationId,
            message: body,
            attachment: attachment,
        }).then(function (res) {
            if (sendBtn) sendBtn.classList.remove('is-sending');
            self.composer.setEnabled(true, 'Enter to send · Shift+Enter for new line');
            self.composer.clear();
            if (self.features) {
                self.features._clearReply();
                self.features.updateAttachmentPreview(null);
            }
            if (res && res.success && res.message) {
                self.store.confirmOutbound(tempId, res.message);
                self._lastMessageId = Math.max(self._lastMessageId, parseInt(res.message.id, 10));
                if (!conv.messages) conv.messages = [];
                conv.messages.push(res.message);
                self._updateInboxPreview(conv, res.message);
                self.transport.setTyping(conv.chatConversationId, false);
            } else {
                self._failOutbound(tempId, (res && res.message) || 'Send failed');
            }
        }).catch(function (err) {
            if (sendBtn) sendBtn.classList.remove('is-sending');
            self.composer.setEnabled(true, 'Enter to send · Shift+Enter for new line');
            self._failOutbound(tempId, (err && err.message) ? err.message : 'Network error — try again');
        });
    };

    Hub.prototype._failOutbound = function (tempId, msg) {
        var bubble = this.threadBody && this.threadBody.querySelector('[data-msg-id="' + tempId + '"]');
        if (bubble) {
            var meta = bubble.querySelector('.msg-bubble-meta');
            if (meta) meta.textContent = this.policy.outboundStatusLabel('failed');
            bubble.setAttribute('data-msg-state', 'failed');
        }
        this.toast(msg, 'error');
    };

    Hub.prototype._handleTypingInput = function () {
        var conv = this.store.getActive();
        if (!conv || !conv.chatConversationId || !this.policy.can('typingIndicators')) return;
        var self = this;
        this.transport.setTyping(conv.chatConversationId, true);
        clearTimeout(this._typingTimer);
        this._typingTimer = setTimeout(function () {
            self.transport.setTyping(conv.chatConversationId, false);
        }, 2000);
    };

    Hub.prototype._showTyping = function (typingData) {
        if (!this.typingEl || !this.policy.can('typingIndicators')) return;
        var isTyping = false;
        var name = '';
        if (Array.isArray(typingData) && typingData.length) {
            isTyping = true;
            name = typingData[0].name || (this.config.role === 'organization' ? 'Student' : 'Recruiter');
        } else if (typingData === true) {
            isTyping = true;
            name = this.config.role === 'organization' ? 'Student' : 'Recruiter';
        }
        if (isTyping && name) {
            this.typingEl.innerHTML = '<span class="msg-typing-dots"><span></span><span></span><span></span></span> ' +
                this._escape(name) + ' is typing…';
        } else {
            this.typingEl.textContent = '';
        }
        this.typingEl.hidden = !isTyping;
    };

    Hub.prototype._updatePresence = function (presenceList) {
        if (!this.presenceEl || !this.policy.can('onlinePresence') || !presenceList || !presenceList.length) return;
        var p = presenceList[0];
        var label = this.presenceEl.querySelector('[data-msg-presence-label]') || this.presenceEl;
        var dot = '<span class="msg-presence-dot';
        var text = '';
        if (p.online) {
            dot += ' msg-presence-dot--online"></span>';
            text = 'Online';
        } else if (p.lastSeen && (Date.now() / 1000 - p.lastSeen) < 300) {
            dot += '"></span>';
            text = 'Away · last seen ' + new Date(p.lastSeen * 1000).toLocaleTimeString();
        } else if (p.lastSeen) {
            dot += '"></span>';
            text = 'Last seen ' + new Date(p.lastSeen * 1000).toLocaleString();
        } else {
            dot += '"></span>';
            text = 'Offline';
        }
        if (label === this.presenceEl) {
            this.presenceEl.innerHTML = dot + ' ' + text;
        } else {
            this.presenceEl.innerHTML = dot;
            label.textContent = text;
        }
    };

    Hub.prototype._startPollLoop = function () {
        var self = this;
        var ms = this.policy.capabilities.pollChatMs || 2500;
        this._stopPollLoop();
        this._pollTimer = setInterval(function () { self._pollActive(); }, ms);
        this._pollActive();
    };

    Hub.prototype._stopPollLoop = function () {
        if (this._pollTimer) {
            clearInterval(this._pollTimer);
            this._pollTimer = null;
        }
    };

    Hub.prototype._pollActive = function () {
        var conv = this.store.getActive();
        if (!conv || !conv.chatConversationId) return;
        var self = this;
        this.transport.poll(conv.chatConversationId, this._lastMessageId).then(function (res) {
            if (!res || !res.success) return;
            (res.messages || []).forEach(function (m) { self._onIncomingMessage(m); });
            if (res.typing && res.typing.length) self._showTyping(res.typing);
            else self._showTyping(false);
            if (res.presence) self._updatePresence(res.presence);
        }).catch(function () { /* silent */ });
    };

    Hub.prototype._startHeartbeat = function () {
        var self = this;
        this.transport.heartbeat().catch(function () { /* */ });
        this._heartbeatTimer = setInterval(function () {
            self.transport.heartbeat().catch(function () { /* */ });
        }, 30000);
    };

    Hub.prototype._markChatReadSilently = function (conversationId) {
        var self = this;
        var url = this.config.chat && this.config.chat.markReadUrl;
        if (!url || !this.config.csrfToken || !conversationId) return;
        var body = new URLSearchParams();
        body.set(this.config.csrfParam || '_csrf', this.config.csrfToken);
        body.set('conversation_id', conversationId);
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        }).then(function (res) { return res.json(); }).then(function (data) {
            var item = document.querySelector('[data-chat-conversation-id="' + conversationId + '"]');
            if (item) {
                item.classList.remove('unread');
                item.classList.add('read');
                var dot = item.querySelector('.sp-conv-unread');
                if (dot) dot.remove();
            }
            if (data && typeof data.unread === 'number') {
                self._updateGlobalUnreadBadges(data.unread);
            } else {
                self.transport.fetchUnreadCount().then(function (countData) {
                    if (countData && typeof countData.count === 'number') {
                        self._updateGlobalUnreadBadges(countData.count);
                    }
                }).catch(function () { /* */ });
            }
        }).catch(function () { /* */ });
    };

    Hub.prototype._markNotificationReadSilently = function (id) {
        var url = this.config.markReadUrl;
        if (!url || !this.config.csrfToken) return;
        var body = new URLSearchParams();
        body.set(this.config.csrfParam || '_csrf', this.config.csrfToken);
        body.set('notification_id', id);
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        }).then(function () {
            var item = document.querySelector('[data-conversation-id="' + id + '"]');
            if (item) {
                item.classList.remove('unread');
                item.classList.add('read');
                var dot = item.querySelector('.sp-conv-unread');
                if (dot) dot.remove();
            }
        }).catch(function () { /* */ });
    };

    Hub.prototype._bindFilters = function () {
        var self = this;
        var attr = this.config.filterAttr || 'data-conv-filter';
        this.el.querySelectorAll('[' + attr + ']').forEach(function (tab) {
            tab.addEventListener('click', function () {
                self.el.querySelectorAll('[' + attr + ']').forEach(function (t) { t.classList.remove('is-active'); });
                tab.classList.add('is-active');
                var f = tab.getAttribute(attr);
                self.el.querySelectorAll('[data-conversation-id]').forEach(function (item) {
                    item.hidden = !self._filterMatch(item, f);
                });
            });
        });
    };

    Hub.prototype._filterMatch = function (item, filter) {
        var archived = item.getAttribute('data-is-archived') === '1';
        if (filter === 'all') return !archived;
        if (filter === 'archived') return archived;
        if (filter === 'unread') return !archived && item.classList.contains('unread');
        if (archived) return false;
        var tags = (item.getAttribute('data-conv-filter-tags') || '').toLowerCase();
        var type = item.getAttribute('data-sender-type') || '';
        if (this.config.role === 'student') {
            if (filter === 'organizations') return type === 'organization';
            if (filter === 'students') return type === 'student';
            if (filter === 'support') return type === 'admin' || type === 'system';
            if (filter === 'interviews') {
                var t = (item.querySelector('.sp-conv-title')?.textContent || '').toLowerCase();
                var p = (item.querySelector('.sp-conv-preview')?.textContent || '').toLowerCase();
                return /interview|shortlist|schedule|meeting|call/.test(t + ' ' + p);
            }
        }
        if (this.config.role === 'organization' && filter === 'students') {
            return type === 'student' || tags.indexOf('applicants') >= 0;
        }
        return tags.indexOf(filter) >= 0;
    };

    Hub.prototype._bindSearch = function () {
        var search = this.el.querySelector('[data-msg-search]');
        if (!search) return;
        search.addEventListener('input', function () {
            var q = search.value.toLowerCase().trim();
            this.el.querySelectorAll('[data-conversation-id]').forEach(function (item) {
                var text = (item.getAttribute('data-search-text') || item.textContent).toLowerCase();
                item.hidden = q !== '' && text.indexOf(q) < 0;
            });
        }.bind(this));
    };

    Hub.prototype._bindArchive = function () {
        var self = this;
        var archiveBtn = this.el.querySelector('[data-msg-archive]');
        if (!archiveBtn) return;

        archiveBtn.addEventListener('click', function () {
            var active = self.store.getActive();
            var convId = active && (active.chatConversationId || (active.source === 'chat' ? active.id : null));
            if (!convId) return;

            var url = self.config.chat && self.config.chat.archiveUrl;
            if (!url) return;

            var body = new URLSearchParams();
            body.set(self.config.csrfParam || '_csrf', self.config.csrfToken || '');
            body.set('conversation_id', String(convId));
            body.set('archived', '1');

            archiveBtn.disabled = true;
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body.toString(),
            }).then(function (r) { return r.json(); }).then(function (res) {
                if (res && res.success) {
                    var item = self.el.querySelector('[data-conversation-id="' + convId + '"]');
                    if (item) {
                        item.setAttribute('data-is-archived', '1');
                        item.hidden = true;
                    }
                    self.toast(res.message || 'Archived', 'success');
                } else {
                    self.toast((res && res.message) || 'Could not archive', 'danger');
                }
            }).catch(function () {
                self.toast('Could not archive conversation', 'danger');
            }).finally(function () {
                archiveBtn.disabled = false;
            });
        });
    };

    Hub.prototype._bindMobile = function () {
        var sidebar = this.el.querySelector('[data-msg-sidebar]');
        var context = this.el.querySelector('[data-msg-detail]');
        var openBtn = this.el.querySelector('[data-msg-open-sidebar]');
        var contextBtn = this.el.querySelector('[data-msg-open-context]');
        if (!sidebar && !context) return;

        var backdrop = document.querySelector('[data-msg-mobile-backdrop]');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'msg-mobile-backdrop';
            backdrop.setAttribute('data-msg-mobile-backdrop', '');
            backdrop.hidden = true;
            document.body.appendChild(backdrop);
        }

        var self = this;
        function closePanels() {
            if (sidebar) sidebar.classList.remove('is-open');
            if (context) context.classList.remove('is-open');
            backdrop.hidden = true;
        }

        if (openBtn && sidebar) {
            openBtn.addEventListener('click', function () {
                if (context) context.classList.remove('is-open');
                sidebar.classList.toggle('is-open');
                backdrop.hidden = !sidebar.classList.contains('is-open');
            });
        }
        if (contextBtn && context) {
            contextBtn.addEventListener('click', function () {
                if (sidebar) sidebar.classList.remove('is-open');
                context.classList.toggle('is-open');
                backdrop.hidden = !context.classList.contains('is-open');
            });
        }
        backdrop.addEventListener('click', closePanels);
        this.el.querySelectorAll('[data-conversation-id]').forEach(function (item) {
            item.addEventListener('click', closePanels);
        });
    };

    Hub.prototype._startUnreadPoll = function () {
        var ms = this.policy.capabilities.pollUnreadMs;
        if (!ms || !this.config.unreadCountUrl) return;
        var self = this;
        var refresh = function () {
            self.transport.fetchUnreadCount().then(function (data) {
                if (data && typeof data.count === 'number') {
                    self._updateGlobalUnreadBadges(data.count);
                }
            }).catch(function () { /* */ });
        };
        refresh();
        setInterval(refresh, ms);
    };

    Hub.instances = [];

    Hub.mountAll = function () {
        document.querySelectorAll('[data-messaging-hub]').forEach(function (el) {
            if (el.__msgHub) return;
            el.__msgHub = new Hub(el);
            Hub.instances.push(el.__msgHub);
        });
    };

    Hub.destroyAll = function () {
        Hub.instances.forEach(function (h) {
            h._stopPollLoop();
            if (h._heartbeatTimer) clearInterval(h._heartbeatTimer);
            if (h.realtime) h.realtime.disconnect();
        });
        Hub.instances = [];
        document.querySelectorAll('[data-messaging-hub]').forEach(function (el) {
            delete el.__msgHub;
        });
    };

    global.Messaging = global.Messaging || {};
    global.Messaging.Hub = Hub;
})(typeof window !== 'undefined' ? window : this);
