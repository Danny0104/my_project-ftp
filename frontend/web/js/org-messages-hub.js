/**
 * Organization messaging — candidate context panel & workspace polish.
 */
(function (global) {
    'use strict';

    function setText(el, value) {
        if (el) el.textContent = (value !== null && value !== undefined && value !== '') ? value : '—';
    }

    function cloneAvatarFromItem(item, target) {
        if (!item || !target) return;
        var src = item.querySelector('.sp-conv-avatar .ft-avatar, .sp-conv-avatar');
        if (src) target.innerHTML = src.outerHTML;
    }

    function updateOrgContext(conv) {
        var ctx = conv.ctx || {};
        var item = conv.element;

        if (conv.source === 'application' || conv.source === 'chat') {
            setText(document.querySelector('[data-msg-context-name]'), ctx.studentName || conv.title);
            setText(document.querySelector('[data-msg-context-sub]'), ctx.field || ctx.subtitle || 'Applicant');
            setText(document.querySelector('[data-msg-ctx-role]'), ctx.roleTitle || conv.subtitle);
            setText(document.querySelector('[data-msg-ctx-status]'), ctx.status);
            setText(document.querySelector('[data-msg-ctx-gpa]'), ctx.gpa != null ? String(ctx.gpa) : null);
            setText(document.querySelector('[data-msg-ctx-skills]'), ctx.skills);
            setText(document.querySelector('[data-msg-ctx-field]'), ctx.field);
            setText(document.querySelector('[data-msg-ctx-interview]'),
                /interview|approved|university/i.test(ctx.statusKey || '') ? 'In pipeline' : 'Not scheduled');

            var primary = document.getElementById('orgCtxPrimary') || document.querySelector('[data-msg-ctx-action]');
            if (primary) {
                if (ctx.viewUrl) {
                    primary.href = ctx.viewUrl;
                    primary.textContent = 'View application';
                    primary.hidden = false;
                } else {
                    primary.hidden = true;
                }
            }
            var interview = document.getElementById('orgMsgInterview');
            if (interview) {
                interview.hidden = !/interview|approved|university/i.test(ctx.statusKey || '');
            }
        } else {
            setText(document.querySelector('[data-msg-context-name]'), ctx.title || conv.title);
            setText(document.querySelector('[data-msg-context-sub]'), 'Platform message');
        }

        cloneAvatarFromItem(item, document.querySelector('[data-msg-context-avatar]'));
        cloneAvatarFromItem(item, document.querySelector('[data-msg-header-avatar]'));
    }

    function initCounters() {
        document.querySelectorAll('.org-msg-page [data-count]').forEach(function (el) {
            var target = parseInt(el.getAttribute('data-count'), 10) || 0;
            if (el.textContent === String(target)) return;
            var start = performance.now();
            function tick(now) {
                var p = Math.min((now - start) / 900, 1);
                el.textContent = String(Math.floor(target * (1 - Math.pow(1 - p, 3))));
                if (p < 1) requestAnimationFrame(tick);
            }
            requestAnimationFrame(tick);
        });
    }

    global.MessagingConfig = global.MessagingConfig || {};
    global.MessagingConfig.onContextUpdate = function (conv) {
        updateOrgContext(conv);
    };
    if (global.orgToast) {
        global.MessagingConfig.onToast = function (msg, type) {
            global.orgToast({ title: type === 'error' ? 'Error' : 'Notice', message: msg, variant: type || 'info' });
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.querySelector('[data-messaging-hub].org-messages-hub')) return;
        initCounters();
    });
})(typeof window !== 'undefined' ? window : this);
