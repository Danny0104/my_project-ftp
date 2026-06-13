/**
 * Premium messaging UX — reactions, reply, forward, search, prefs (localStorage only).
 * @module Messaging.Features
 */
(function (global) {
    'use strict';

    var REACTIONS = ['👍', '❤️', '👏', '✅'];
    var PREFS_KEY = 'msg-conv-prefs:';
    var REACTIONS_KEY = 'msg-reactions:';

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str == null ? '' : String(str);
        return d.innerHTML;
    }

    function Features(hub) {
        this.hub = hub;
        this.el = hub.el;
        this._replyTo = null;
        this._lightbox = null;
        this._forwardModal = null;
        this._threadSearchOpen = false;
        this._olderLoading = false;
        this._bindUi();
    }

    Features.prototype._bindUi = function () {
        var self = this;
        var threadSearchBtn = this.el.querySelector('[data-msg-thread-search-toggle]');
        var threadSearchInput = this.el.querySelector('[data-msg-thread-search]');
        var contextToggle = this.el.querySelector('[data-msg-open-context]');
        var contextPanel = this.el.querySelector('[data-msg-detail]');
        var pinBtn = this.el.querySelector('[data-msg-pin]');
        var starBtn = this.el.querySelector('[data-msg-star]');
        var muteBtn = this.el.querySelector('[data-msg-mute]');
        var markUnreadBtn = this.el.querySelector('[data-msg-mark-unread]');

        if (threadSearchBtn && threadSearchInput) {
            var searchBar = threadSearchInput.closest('.msg-thread-search');
            threadSearchBtn.addEventListener('click', function () {
                self._threadSearchOpen = !self._threadSearchOpen;
                if (searchBar) searchBar.classList.toggle('is-open', self._threadSearchOpen);
                threadSearchBtn.classList.toggle('is-active', self._threadSearchOpen);
                if (self._threadSearchOpen) threadSearchInput.focus();
                else {
                    threadSearchInput.value = '';
                    self._clearSearchHits();
                }
            });
            threadSearchInput.addEventListener('input', function () {
                self._searchInThread(threadSearchInput.value.trim());
            });
        }

        if (contextToggle && contextPanel) {
            contextToggle.addEventListener('click', function () {
                contextPanel.classList.toggle('is-open');
            });
        }

        [pinBtn, starBtn, muteBtn].forEach(function (btn) {
            if (!btn) return;
            btn.addEventListener('click', function () {
                var conv = self.hub.store.getActive();
                if (!conv) return;
                var key = btn.getAttribute('data-msg-pin') !== null ? 'pinned'
                    : btn.getAttribute('data-msg-star') !== null ? 'starred' : 'muted';
                self._togglePref(conv, key);
                self._syncActionButtons(conv);
            });
        });

        if (markUnreadBtn) {
            markUnreadBtn.addEventListener('click', function () {
                var conv = self.hub.store.getActive();
                if (!conv || !conv.element) return;
                var conversationId = conv.chatConversationId || conv.id;
                var url = self.hub.config.chat && self.hub.config.chat.markUnreadUrl;
                if (!url || !self.hub.config.csrfToken || !conversationId) return;

                var body = new URLSearchParams();
                body.set(self.hub.config.csrfParam || '_csrf', self.hub.config.csrfToken);
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
                    if (!data || !data.success) {
                        self.hub.toast((data && data.message) || 'Could not mark unread', 'error');
                        return;
                    }
                    conv.element.classList.add('unread');
                    conv.element.classList.remove('read');
                    if (!conv.element.querySelector('.sp-conv-unread')) {
                        var dot = document.createElement('span');
                        dot.className = 'sp-conv-unread sp-pulse';
                        dot.setAttribute('aria-label', 'Unread');
                        conv.element.appendChild(dot);
                    }
                    if (typeof data.unread === 'number') {
                        self.hub._updateGlobalUnreadBadges(data.unread);
                    }
                    self.hub.toast('Marked as unread', 'success');
                }).catch(function () {
                    self.hub.toast('Could not mark unread', 'error');
                });
            });
        }

        this._initLightbox();
        this._initForwardModal();
        this._bindInfiniteScroll();
        this._bindBubbleDelegation();
    };

    Features.prototype._getPrefs = function (convId) {
        try {
            var raw = localStorage.getItem(PREFS_KEY + convId);
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    };

    Features.prototype._setPrefs = function (convId, prefs) {
        try {
            localStorage.setItem(PREFS_KEY + convId, JSON.stringify(prefs));
        } catch (e) { /* */ }
    };

    Features.prototype._togglePref = function (conv, key) {
        var id = String(conv.chatConversationId || conv.id);
        var prefs = this._getPrefs(id);
        prefs[key] = !prefs[key];
        this._setPrefs(id, prefs);
        if (conv.element) {
            conv.element.classList.toggle('is-pinned', !!prefs.pinned);
            conv.element.classList.toggle('is-starred', !!prefs.starred);
            conv.element.classList.toggle('is-muted', !!prefs.muted);
            if (prefs.pinned && conv.element.parentNode) {
                conv.element.parentNode.insertBefore(conv.element, conv.element.parentNode.firstChild);
            }
        }
        var labels = { pinned: 'Pinned', starred: 'Starred', muted: 'Muted' };
        this.hub.toast((prefs[key] ? '' : 'Un') + (labels[key] || key), 'success');
    };

    Features.prototype._syncActionButtons = function (conv) {
        if (!conv) return;
        var id = String(conv.chatConversationId || conv.id);
        var prefs = this._getPrefs(id);
        var pinBtn = this.el.querySelector('[data-msg-pin]');
        var starBtn = this.el.querySelector('[data-msg-star]');
        var muteBtn = this.el.querySelector('[data-msg-mute]');
        if (pinBtn) pinBtn.classList.toggle('is-active', !!prefs.pinned);
        if (starBtn) starBtn.classList.toggle('is-active', !!prefs.starred);
        if (muteBtn) muteBtn.classList.toggle('is-active', !!prefs.muted);
        if (conv.element) {
            conv.element.classList.toggle('is-pinned', !!prefs.pinned);
            conv.element.classList.toggle('is-starred', !!prefs.starred);
        }
    };

    Features.prototype.onConversationChanged = function (conv) {
        this._replyTo = null;
        this._updateReplyBar();
        this._syncActionButtons(conv);
        if (this._threadSearchOpen) {
            var input = this.el.querySelector('[data-msg-thread-search]');
            if (input) {
                input.value = '';
                this._clearSearchHits();
            }
        }
    };

    Features.prototype.getReplyPrefix = function () {
        if (!this._replyTo) return '';
        var text = (this._replyTo.body || '').substring(0, 200);
        return '> ' + text.replace(/\n/g, '\n> ') + '\n\n';
    };

    Features.prototype._setReply = function (msg) {
        this._replyTo = msg;
        this._updateReplyBar();
        var input = this.hub.composer.input;
        if (input && !input.disabled) input.focus();
    };

    Features.prototype._updateReplyBar = function () {
        var bar = this.el.querySelector('[data-msg-reply-bar]');
        var text = this.el.querySelector('[data-msg-reply-text]');
        if (!bar) return;
        if (this._replyTo) {
            bar.classList.add('is-active');
            if (text) text.textContent = (this._replyTo.body || '').substring(0, 80);
        } else {
            bar.classList.remove('is-active');
        }
    };

    Features.prototype._clearReply = function () {
        this._replyTo = null;
        this._updateReplyBar();
    };

    Features.prototype._getReactions = function (msgId) {
        try {
            var raw = localStorage.getItem(REACTIONS_KEY + msgId);
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    };

    Features.prototype._setReaction = function (msgId, emoji) {
        var reactions = this._getReactions(msgId);
        reactions[emoji] = (reactions[emoji] || 0) + 1;
        try {
            localStorage.setItem(REACTIONS_KEY + msgId, JSON.stringify(reactions));
        } catch (e) { /* */ }
        return reactions;
    };

    Features.prototype._renderReactionsHtml = function (msgId) {
        var reactions = this._getReactions(msgId);
        var keys = Object.keys(reactions);
        if (!keys.length) return '';
        var html = '<div class="msg-bubble-reactions">';
        keys.forEach(function (emoji) {
            html += '<span class="msg-reaction-pill" data-msg-react="' + escapeHtml(emoji) + '">' +
                emoji + ' ' + reactions[emoji] + '</span>';
        });
        html += '</div>';
        return html;
    };

    Features.prototype._bindBubbleDelegation = function () {
        var self = this;
        var body = this.hub.threadBody;
        if (!body) return;

        body.addEventListener('click', function (e) {
            var reactBtn = e.target.closest('[data-msg-action="react"]');
            var replyBtn = e.target.closest('[data-msg-action="reply"]');
            var forwardBtn = e.target.closest('[data-msg-action="forward"]');
            var imgLink = e.target.closest('.msg-attachment--image img');
            var clearReply = e.target.closest('[data-msg-reply-clear]');

            if (clearReply) {
                self._clearReply();
                return;
            }

            var wrap = e.target.closest('.msg-bubble-wrap');
            if (!wrap) {
                if (imgLink) self._openLightbox(imgLink.src);
                return;
            }

            var msgId = wrap.getAttribute('data-msg-id');
            var bodyEl = wrap.querySelector('.msg-bubble-body');
            var msg = {
                id: msgId,
                body: bodyEl ? bodyEl.textContent : '',
            };

            if (reactBtn) {
                self._showReactionPicker(wrap, msgId);
                return;
            }
            if (replyBtn) {
                self._setReply(msg);
                return;
            }
            if (forwardBtn) {
                self._openForward(msg);
                return;
            }
            if (imgLink) {
                e.preventDefault();
                self._openLightbox(imgLink.src);
            }
        });
    };

    Features.prototype._showReactionPicker = function (wrap, msgId) {
        var self = this;
        var existing = wrap.querySelector('.msg-reaction-picker');
        if (existing) {
            existing.remove();
            return;
        }
        var picker = document.createElement('div');
        picker.className = 'msg-bubble-actions msg-reaction-picker';
        picker.style.opacity = '1';
        picker.style.pointerEvents = 'auto';
        REACTIONS.forEach(function (emoji) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'msg-bubble-action';
            btn.textContent = emoji;
            btn.addEventListener('click', function (ev) {
                ev.stopPropagation();
                self._setReaction(msgId, emoji);
                var reactionsEl = wrap.querySelector('.msg-bubble-reactions');
                var html = self._renderReactionsHtml(msgId);
                if (reactionsEl) reactionsEl.outerHTML = html;
                else if (html) wrap.insertAdjacentHTML('beforeend', html);
                picker.remove();
            });
            picker.appendChild(btn);
        });
        wrap.appendChild(picker);
    };

    Features.prototype.enhanceBubble = function (wrapEl, msg) {
        if (!wrapEl || !msg) return;
        var reactionsHtml = this._renderReactionsHtml(String(msg.id));
        if (reactionsHtml) wrapEl.insertAdjacentHTML('beforeend', reactionsHtml);
    };

    Features.prototype._searchInThread = function (query) {
        var body = this.hub.threadBody;
        if (!body) return;
        this._clearSearchHits();
        if (!query) return;
        var q = query.toLowerCase();
        var hits = body.querySelectorAll('.msg-bubble-body');
        var first = null;
        hits.forEach(function (el) {
            if (el.textContent.toLowerCase().indexOf(q) >= 0) {
                el.closest('.msg-bubble').classList.add('msg-search-hit');
                if (!first) first = el;
            }
        });
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    Features.prototype._clearSearchHits = function () {
        var body = this.hub.threadBody;
        if (!body) return;
        body.querySelectorAll('.msg-search-hit').forEach(function (el) {
            el.classList.remove('msg-search-hit');
        });
    };

    Features.prototype._initLightbox = function () {
        var self = this;
        if (document.getElementById('msgImageLightbox')) {
            this._lightbox = document.getElementById('msgImageLightbox');
            return;
        }
        var lb = document.createElement('div');
        lb.id = 'msgImageLightbox';
        lb.className = 'msg-lightbox';
        lb.innerHTML = '<button type="button" class="msg-lightbox-close" aria-label="Close"><i class="fas fa-xmark"></i></button><img src="" alt="">';
        document.body.appendChild(lb);
        this._lightbox = lb;
        lb.querySelector('.msg-lightbox-close').addEventListener('click', function () {
            lb.classList.remove('is-open', 'is-zoomed');
        });
        lb.addEventListener('click', function (e) {
            if (e.target === lb) lb.classList.remove('is-open', 'is-zoomed');
        });
        lb.querySelector('img').addEventListener('click', function () {
            lb.classList.toggle('is-zoomed');
        });
    };

    Features.prototype._openLightbox = function (src) {
        if (!this._lightbox) return;
        var img = this._lightbox.querySelector('img');
        img.src = src;
        this._lightbox.classList.add('is-open');
        this._lightbox.classList.remove('is-zoomed');
    };

    Features.prototype._initForwardModal = function () {
        if (document.getElementById('msgForwardModal')) {
            this._forwardModal = document.getElementById('msgForwardModal');
            return;
        }
        var backdrop = document.createElement('div');
        backdrop.id = 'msgForwardModal';
        backdrop.className = 'msg-modal-backdrop';
        backdrop.innerHTML =
            '<div class="msg-modal" role="dialog" aria-label="Forward message">' +
            '<div class="msg-modal-head">Forward to conversation</div>' +
            '<div class="msg-modal-list" data-msg-forward-list></div>' +
            '</div>';
        document.body.appendChild(backdrop);
        this._forwardModal = backdrop;
        var self = this;
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) backdrop.classList.remove('is-open');
        });
    };

    Features.prototype._openForward = function (msg) {
        var self = this;
        var list = this._forwardModal.querySelector('[data-msg-forward-list]');
        list.innerHTML = '';
        this.el.querySelectorAll('[data-conversation-id][data-conv-source="chat"]').forEach(function (item) {
            var title = item.querySelector('.sp-conv-title');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'msg-modal-item';
            btn.textContent = title ? title.textContent : 'Conversation';
            btn.addEventListener('click', function () {
                self._forwardModal.classList.remove('is-open');
                self._forwardTo(item, msg);
            });
            list.appendChild(btn);
        });
        this._forwardModal.classList.add('is-open');
    };

    Features.prototype._forwardTo = function (targetItem, msg) {
        var self = this;
        var chatId = targetItem.getAttribute('data-chat-conversation-id');
        if (!chatId) return;
        var body = 'Forwarded:\n' + (msg.body || '');
        this.hub.transport.sendMessage({
            conversationId: parseInt(chatId, 10),
            message: body,
            attachment: null,
        }).then(function (res) {
            if (res && res.success) {
                self.hub.toast('Message forwarded', 'success');
            } else {
                self.hub.toast((res && res.message) || 'Forward failed', 'error');
            }
        }).catch(function () {
            self.hub.toast('Forward failed', 'error');
        });
    };

    Features.prototype._bindInfiniteScroll = function () {
        var self = this;
        var scrollParent = this.el.querySelector('[data-msg-messages-scroll]')
            || this.el.querySelector('.msg-thread-scroll');
        if (!scrollParent) return;

        scrollParent.addEventListener('scroll', function () {
            if (scrollParent.scrollTop > 80 || self._olderLoading) return;
            var conv = self.hub.store.getActive();
            if (!conv || !conv.chatConversationId || !conv.messages || !conv.messages.length) return;
            var oldest = conv.messages.reduce(function (min, m) {
                var id = parseInt(m.id, 10);
                return (!min || id < min) ? id : min;
            }, 0);
            if (!oldest || oldest < 2) return;
            self._olderLoading = true;
            self.hub.transport.fetchThread(conv.chatConversationId, oldest).then(function (res) {
                self._olderLoading = false;
                if (!res || !res.success || !res.messages || !res.messages.length) return;
                var prevHeight = scrollParent.scrollHeight;
                res.messages.reverse().forEach(function (m) {
                    if (conv.messages.some(function (x) { return String(x.id) === String(m.id); })) return;
                    conv.messages.unshift(m);
                    self.hub.renderer.prependBubble(self.hub.threadBody, m, self.hub.policy);
                });
                scrollParent.scrollTop = scrollParent.scrollHeight - prevHeight;
            }).catch(function () {
                self._olderLoading = false;
            });
        });
    };

    Features.prototype.updateAttachmentPreview = function (file) {
        var preview = this.el.querySelector('[data-msg-attach-preview]');
        if (!preview) return;
        if (!file) {
            preview.classList.remove('is-active');
            preview.innerHTML = '';
            return;
        }
        preview.classList.add('is-active');
        var html = '<span class="msg-attach-preview-name"><i class="fas fa-file"></i> ' + escapeHtml(file.name) + '</span>';
        if (file.type && file.type.indexOf('image/') === 0) {
            var url = URL.createObjectURL(file);
            html = '<img src="' + url + '" alt="">' + html;
        }
        preview.innerHTML = html +
            '<button type="button" class="msg-toolbar-btn" data-msg-attach-clear aria-label="Remove"><i class="fas fa-xmark"></i></button>';
        var self = this;
        var clear = preview.querySelector('[data-msg-attach-clear]');
        if (clear) {
            clear.addEventListener('click', function () {
                self.hub.composer.clearAttachment();
                self.updateAttachmentPreview(null);
            });
        }
    };

    global.Messaging = global.Messaging || {};
    global.Messaging.Features = Features;
})(typeof window !== 'undefined' ? window : this);
