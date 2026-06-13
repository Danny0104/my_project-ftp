/**
 * Message composer — drafts, validation, keyboard shortcuts, outbound preview.
 * @module Messaging.Composer
 */
(function (global) {
    'use strict';

    var DRAFT_PREFIX = 'msg-draft:';

    function Composer(root, policy, bus) {
        this.root = root;
        this.policy = policy;
        this.bus = bus;
        this.input = root && root.querySelector('[data-msg-composer-input]');
        this.sendBtn = root && root.querySelector('[data-msg-composer-send]');
        this.saveDraftBtn = root && root.querySelector('[data-msg-save-draft]');
        this.attachBtn = root && root.querySelector('[data-msg-attach]');
        this.emojiBtn = root && root.querySelector('[data-msg-emoji]');
        this.noteEl = root && root.querySelector('[data-msg-composer-note]');
        this.fileInput = null;
        this._pendingFile = null;
        this.maxLength = 4000;
        this.conversationId = null;
        this._enabled = false;
    }

    Composer.prototype.setConversationId = function (id) {
        this.conversationId = id;
        this.loadDraft();
    };

    Composer.prototype.setEnabled = function (enabled, reason) {
        this._enabled = !!enabled;
        if (this.input) {
            this.input.disabled = !enabled;
            this.input.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        }
        if (this.sendBtn) this.sendBtn.disabled = !enabled;
        if (this.attachBtn) this.attachBtn.disabled = !enabled || !this.policy.can('attachments');
        if (this.emojiBtn) this.emojiBtn.disabled = !enabled;
        if (this.noteEl && reason) this.noteEl.textContent = reason;
    };

    Composer.prototype.loadDraft = function () {
        if (!this.input || !this.conversationId) return;
        try {
            var draft = localStorage.getItem(DRAFT_PREFIX + this.conversationId);
            if (draft) this.input.value = draft;
        } catch (e) { /* private mode */ }
        this.autoResize();
    };

    Composer.prototype.saveDraft = function () {
        if (!this.input || !this.conversationId) return;
        try {
            var val = this.input.value.trim();
            if (val) localStorage.setItem(DRAFT_PREFIX + this.conversationId, val);
            else localStorage.removeItem(DRAFT_PREFIX + this.conversationId);
            this.bus.emit('composer:draft-saved', { conversationId: this.conversationId });
        } catch (e) { /* ignore */ }
    };

    Composer.prototype.clearDraft = function () {
        if (!this.conversationId) return;
        try { localStorage.removeItem(DRAFT_PREFIX + this.conversationId); } catch (e) { /* */ }
    };

    Composer.prototype.getValue = function () {
        return this.input ? this.input.value.trim() : '';
    };

    Composer.prototype.clear = function () {
        if (this.input) {
            this.input.value = '';
            this.autoResize();
        }
        this.clearAttachment();
        this.clearDraft();
    };

    Composer.prototype.getAttachment = function () {
        return this._pendingFile || null;
    };

    Composer.prototype.clearAttachment = function () {
        this._pendingFile = null;
        if (this.fileInput) this.fileInput.value = '';
    };

    Composer.prototype.validate = function () {
        var val = this.getValue();
        if (!val && !this._pendingFile) return { ok: false, error: 'Enter a message or attach a file' };
        if (val.length > this.maxLength) return { ok: false, error: 'Message is too long' };
        return { ok: true };
    };

    Composer.prototype.autoResize = function () {
        if (!this.input || this.input.tagName !== 'TEXTAREA') return;
        this.input.style.height = 'auto';
        this.input.style.height = Math.min(this.input.scrollHeight, 140) + 'px';
    };

    Composer.prototype.bind = function () {
        var self = this;
        if (!this.root) return;

        if (this.input) {
            this.input.addEventListener('input', function () {
                self.autoResize();
                self.bus.emit('composer:input', { conversationId: self.conversationId });
            });
            this.input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.bus.emit('composer:submit-request', {});
                }
            });
        }

        if (this.sendBtn) {
            this.sendBtn.addEventListener('click', function () {
                self.bus.emit('composer:submit-request', {});
            });
        }

        if (this.saveDraftBtn) {
            this.saveDraftBtn.addEventListener('click', function () {
                self.saveDraft();
            });
        }

        if (this.attachBtn && this.policy.can('attachments')) {
            if (!this.fileInput) {
                this.fileInput = document.createElement('input');
                this.fileInput.type = 'file';
                this.fileInput.hidden = true;
                this.fileInput.accept = '.pdf,.doc,.docx,.zip,image/*';
                this.root.appendChild(this.fileInput);
            }
            this.attachBtn.addEventListener('click', function () {
                if (!self._enabled) return;
                self.fileInput.click();
            });
            this.fileInput.addEventListener('change', function () {
                self._pendingFile = self.fileInput.files && self.fileInput.files[0] ? self.fileInput.files[0] : null;
                if (self._pendingFile && self.noteEl) {
                    self.noteEl.textContent = 'Attached: ' + self._pendingFile.name;
                }
                if (self.onAttachmentChange) self.onAttachmentChange(self._pendingFile);
            });
        }

        this.root.querySelectorAll('[data-msg-quick-reply]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!self.input || self.input.disabled) return;
                self.input.value = btn.getAttribute('data-msg-quick-reply') || '';
                self.autoResize();
                self.input.focus();
            });
        });

        if (this.emojiBtn) {
            this._bindEmojiPicker();
        }
    };

    Composer.prototype._bindEmojiPicker = function () {
        var self = this;
        var emojis = ['😀', '😊', '👍', '🎉', '❤️', '🙏', '💼', '📅', '✅', '🔥'];
        var panel = document.createElement('div');
        panel.className = 'msg-emoji-panel';
        panel.hidden = true;
        panel.setAttribute('role', 'menu');
        emojis.forEach(function (emoji) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'msg-emoji-btn';
            btn.textContent = emoji;
            btn.setAttribute('aria-label', 'Insert ' + emoji);
            btn.addEventListener('click', function () {
                if (!self.input || self.input.disabled) return;
                var start = self.input.selectionStart || self.input.value.length;
                var end = self.input.selectionEnd || self.input.value.length;
                var val = self.input.value;
                self.input.value = val.slice(0, start) + emoji + val.slice(end);
                self.autoResize();
                self.input.focus();
                panel.hidden = true;
            });
            panel.appendChild(btn);
        });
        this.root.appendChild(panel);

        this.emojiBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (!self._enabled) return;
            panel.hidden = !panel.hidden;
        });

        document.addEventListener('click', function () {
            panel.hidden = true;
        });
    };

    global.Messaging = global.Messaging || {};
    global.Messaging.Composer = Composer;
})(typeof window !== 'undefined' ? window : this);
