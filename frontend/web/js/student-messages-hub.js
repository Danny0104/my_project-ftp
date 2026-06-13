/**
 * Student messaging hub — context panel & header avatar.
 */
(function (global) {
    'use strict';

    function setText(el, value) {
        if (el) el.textContent = value || '—';
    }

    function cloneAvatarFromItem(item, target) {
        if (!item || !target) return;
        var src = item.querySelector('.sp-conv-avatar, .ft-avatar');
        if (src) {
            target.innerHTML = src.innerHTML;
            var avatar = src.querySelector('.ft-avatar') || src;
            if (avatar.classList.contains('ft-avatar') || avatar.querySelector('.ft-avatar')) {
                target.innerHTML = avatar.outerHTML || src.innerHTML;
            }
        }
    }

    function updateStudentDetail(conv, hubEl) {
        var ctx = conv.ctx || {};
        var item = conv.element;

        setText(hubEl.querySelector('[data-msg-context-name]'), conv.title);
        setText(hubEl.querySelector('[data-msg-context-sub]'), ctx.subtitle || conv.subtitle || 'Organization conversation');
        setText(hubEl.querySelector('[data-msg-ctx-role]'), ctx.subtitle || conv.subtitle);
        setText(hubEl.querySelector('[data-msg-ctx-status]'), ctx.status || 'Active');
        setText(hubEl.querySelector('[data-msg-ctx-time]'), conv.time || ctx.time);
        setText(hubEl.querySelector('[data-msg-ctx-location]'), ctx.orgLocation);
        setText(hubEl.querySelector('[data-msg-ctx-industry]'), ctx.orgIndustry);

        cloneAvatarFromItem(item, hubEl.querySelector('[data-msg-context-avatar]'));
        cloneAvatarFromItem(item, hubEl.querySelector('[data-msg-header-avatar]'));

        var action = hubEl.querySelector('[data-msg-ctx-action]');
        if (action) {
            var url = conv.actionUrl || ctx.actionUrl || '';
            if (url) {
                action.href = url;
                action.textContent = conv.actionText || ctx.actionText || 'View details';
                action.hidden = false;
            } else {
                action.hidden = true;
            }
        }
    }

    global.MessagingConfig = global.MessagingConfig || {};
    global.MessagingConfig.onContextUpdate = function (conv, hubEl) {
        updateStudentDetail(conv, hubEl || document.querySelector('[data-messaging-hub]'));
    };
})(typeof window !== 'undefined' ? window : this);
