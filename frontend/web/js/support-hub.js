/* Support hub polling + composer (lightweight, dashboard-friendly). */
(function () {
    'use strict';

    var root = document.querySelector('[data-support-thread]');
    if (!root) return;

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var code = root.getAttribute('data-ticket-code') || '';
    if (!code) return;

    var bodyEl = document.getElementById('supportThreadBody');
    var form = document.getElementById('supportComposer');

    var sinceId = 0;
    if (bodyEl) {
        var last = bodyEl.querySelector('[data-msg-id]:last-child');
        if (last) sinceId = parseInt(last.getAttribute('data-msg-id'), 10) || 0;
        bodyEl.scrollTop = bodyEl.scrollHeight;
    }

    function csrf() {
        var p = document.querySelector('meta[name="csrf-param"]')?.getAttribute('content') || '_csrf-frontend';
        var t = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        return { param: p, token: t };
    }

    function appendMessage(m) {
        if (!bodyEl || !m) return;
        var wrap = document.createElement('div');
        var direction = (m.senderRole === 'student' || m.senderRole === 'organization') ? 'out' : 'in';
        wrap.className = 'support-msg support-msg--' + direction;
        wrap.setAttribute('data-msg-id', String(m.id));

        var attachments = '';
        if (m.attachments && m.attachments.length) {
            attachments = '<div class="support-attachments">' + m.attachments.map(function (a) {
                return '<a class="support-attachment" href="' + a.url + '" target="_blank" rel="noopener">' +
                    '<i class="fas fa-paperclip me-1"></i>' +
                    (a.name || 'Attachment') +
                    '</a>';
            }).join('') + '</div>';
        }

        wrap.innerHTML =
            '<div class="support-msg__meta">' +
            '<span class="support-msg__from">' + (m.senderRole || 'support') + '</span>' +
            '<span class="support-msg__time">' + (m.timeLabel || '') + '</span>' +
            '</div>' +
            '<div class="support-msg__bubble">' +
            (String(m.body || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/\n/g, '<br>')) +
            attachments +
            '</div>';

        bodyEl.appendChild(wrap);
        bodyEl.scrollTop = bodyEl.scrollHeight;
    }

    function poll() {
        fetch('/index.php?r=support/api/poll&code=' + encodeURIComponent(code) + '&since_id=' + encodeURIComponent(String(sinceId)), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res || !res.success) return;
                (res.messages || []).forEach(function (m) {
                    appendMessage(m);
                    sinceId = Math.max(sinceId, parseInt(m.id, 10) || 0);
                });
            })
            .catch(function () { /* silent */ });
    }

    if (!reduced) {
        setInterval(function () {
            if (document.hidden) return;
            poll();
        }, 3000);
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var fd = new FormData(form);
            var c = csrf();
            fd.set(c.param, c.token);

            var btn = form.querySelector('[type=\"submit\"]');
            if (btn) btn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (btn) btn.disabled = false;
                    if (!res || !res.success || !res.message) return;
                    appendMessage(res.message);
                    sinceId = Math.max(sinceId, parseInt(res.message.id, 10) || 0);
                    form.querySelector('textarea[name=\"body\"]').value = '';
                    var file = form.querySelector('input[type=\"file\"]');
                    if (file) file.value = '';
                })
                .catch(function () {
                    if (btn) btn.disabled = false;
                });
        });
    }
})();

