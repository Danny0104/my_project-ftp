/**
 * Premium message bubbles and thread layout.
 * @module Messaging.MessageRenderer
 */
(function (global) {
    'use strict';

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str == null ? '' : String(str);
        return d.innerHTML;
    }

    function parseBody(body) {
        body = body || '';
        var quote = '';
        var main = body;
        if (body.indexOf('> ') === 0) {
            var parts = body.split(/\n\n/);
            if (parts.length > 1 && parts[0].indexOf('> ') === 0) {
                quote = parts[0].replace(/^> /gm, '').replace(/\n> /g, '\n');
                main = parts.slice(1).join('\n\n');
            }
        }
        return { quote: quote, main: main };
    }

    function MessageRenderer(policy) {
        this.policy = policy;
    }

    MessageRenderer.prototype.groupLabel = function (dateKey) {
        return '<div class="msg-date-divider" data-msg-date="' + escapeHtml(dateKey) + '">' +
            escapeHtml(dateKey) + '</div>';
    };

    MessageRenderer.prototype.attachmentHtml = function (att) {
        if (!att || !att.url) return '';
        var name = escapeHtml(att.name || 'Attachment');
        var url = escapeHtml(att.url);
        if (att.mime && att.mime.indexOf('image/') === 0) {
            return '<a class="msg-attachment msg-attachment--image" href="' + url + '" data-msg-lightbox="' + url + '">' +
                '<img src="' + url + '" alt="' + name + '" loading="lazy"></a>';
        }
        var icon = 'fa-paperclip';
        if (att.mime && att.mime.indexOf('pdf') >= 0) icon = 'fa-file-pdf';
        else if (att.mime && att.mime.indexOf('zip') >= 0) icon = 'fa-file-zipper';
        else if (att.mime && att.mime.indexOf('word') >= 0) icon = 'fa-file-word';
        return '<a class="msg-attachment" href="' + url + '" target="_blank" rel="noopener">' +
            '<i class="fas ' + icon + '"></i> ' + name + '</a>';
    };

    MessageRenderer.prototype.bubbleActions = function () {
        return '<div class="msg-bubble-actions" aria-hidden="true">' +
            '<button type="button" class="msg-bubble-action" data-msg-action="react" title="React"><i class="fas fa-face-smile"></i></button>' +
            '<button type="button" class="msg-bubble-action" data-msg-action="reply" title="Reply"><i class="fas fa-reply"></i></button>' +
            '<button type="button" class="msg-bubble-action" data-msg-action="forward" title="Forward"><i class="fas fa-share"></i></button>' +
            '</div>';
    };

    MessageRenderer.prototype.bubble = function (msg) {
        var dir = msg.direction === 'out' ? 'out' : 'in';
        var parsed = parseBody(msg.body || '');
        var body = escapeHtml(parsed.main);
        if (body === '[Attachment]' && msg.attachment) body = '';
        var quoteHtml = parsed.quote
            ? '<blockquote class="msg-bubble-quote">' + escapeHtml(parsed.quote) + '</blockquote>'
            : '';
        var att = this.attachmentHtml(msg.attachment);
        var time = msg.timeLabel ? '<span class="msg-bubble-time">' + escapeHtml(msg.timeLabel) + '</span>' : '';
        var status = msg.statusLabel
            ? '<span class="msg-bubble-meta">' + escapeHtml(msg.statusLabel) + '</span>'
            : '';
        var footer = (time || status)
            ? '<div class="msg-bubble-footer">' + time + status + '</div>'
            : '';

        return '<div class="msg-bubble-wrap msg-bubble-wrap--' + dir + '" data-msg-id="' + escapeHtml(String(msg.id || '')) + '">' +
            this.bubbleActions() +
            '<div class="msg-bubble msg-bubble--' + dir + '" data-msg-state="' + escapeHtml(msg.state || '') + '">' +
            quoteHtml +
            (body ? '<p class="msg-bubble-body">' + body + '</p>' : '') +
            att +
            footer +
            '</div></div>';
    };

    MessageRenderer.prototype.renderThread = function (container, messages, options) {
        if (!container) return;
        options = options || {};
        var html = '';
        var lastDate = null;

        (messages || []).forEach(function (msg) {
            var dk = msg.dateKey || options.defaultDateKey || 'Today';
            if (dk !== lastDate) {
                html += this.groupLabel(dk);
                lastDate = dk;
            }
            html += this.bubble(msg);
        }, this);

        container.innerHTML = html;
        if (options.typingEl) {
            container.appendChild(options.typingEl);
        }
        this.scrollToBottom(container, options.smooth);
    };

    MessageRenderer.prototype.appendBubble = function (container, msg, policy) {
        if (!container) return null;
        var wrap = document.createElement('div');
        wrap.innerHTML = this.bubble(msg);
        var el = wrap.firstElementChild;
        container.appendChild(el);
        if (global.Messaging && global.Messaging.Features && container.closest('[data-messaging-hub]')) {
            var hub = container.closest('[data-messaging-hub]').__msgHub;
            if (hub && hub.features) hub.features.enhanceBubble(el, msg);
        }
        this.scrollToBottom(container, true);
        return el;
    };

    MessageRenderer.prototype.prependBubble = function (container, msg, policy) {
        if (!container) return null;
        var dk = msg.dateKey || 'Earlier';
        var firstDivider = container.querySelector('.msg-date-divider');
        if (!firstDivider || firstDivider.getAttribute('data-msg-date') !== dk) {
            var divider = document.createElement('div');
            divider.innerHTML = this.groupLabel(dk);
            var dividerEl = divider.firstElementChild;
            var typing = container.querySelector('[data-msg-typing]');
            if (typing) container.insertBefore(dividerEl, container.firstChild);
            else container.insertBefore(dividerEl, container.firstChild);
        }
        var wrap = document.createElement('div');
        wrap.innerHTML = this.bubble(msg);
        var el = wrap.firstElementChild;
        var insertBefore = container.querySelector('.msg-bubble-wrap, .msg-welcome, .msg-loading, .msg-announcement');
        if (insertBefore) container.insertBefore(el, insertBefore);
        else container.insertBefore(el, container.firstChild);
        return el;
    };

    MessageRenderer.prototype.scrollToBottom = function (container, smooth) {
        var scrollParent = container.closest('[data-msg-messages-scroll]')
            || container.closest('.msg-thread-scroll')
            || container.closest('.org-msg-messages-scroll')
            || container.parentElement;
        if (!scrollParent) return;
        scrollParent.scrollTo({
            top: scrollParent.scrollHeight,
            behavior: smooth ? 'smooth' : 'auto',
        });
    };

    MessageRenderer.prototype.buildFromNotification = function (ctx) {
        var body = ctx.message || '';
        if (ctx.title && body.indexOf(ctx.title) !== 0) {
            body = ctx.title + (body ? '\n\n' + body : '');
        }
        return [{
            id: 'n-' + (ctx.id || ''),
            direction: 'in',
            body: body,
            dateKey: ctx.time || 'Today',
            statusLabel: this.policy.inboundStatusLabel({ source: 'notification', isRead: ctx.isRead }),
            state: 'delivered',
        }];
    };

    MessageRenderer.prototype.buildFromApplication = function (ctx) {
        return [{
            id: 'app-in-' + ctx.applicationId,
            direction: 'in',
            body: (ctx.studentName || 'Applicant') + ' applied for ' + (ctx.roleTitle || 'this role') + '. Status: ' + (ctx.status || '—'),
            dateKey: ctx.applied || 'Today',
            statusLabel: 'Application event · ' + (ctx.applied || ''),
            state: 'system',
        }];
    };

    global.Messaging = global.Messaging || {};
    global.Messaging.MessageRenderer = MessageRenderer;
    global.Messaging.escapeHtml = escapeHtml;
})(typeof window !== 'undefined' ? window : this);
