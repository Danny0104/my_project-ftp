(function () {
  'use strict';

  var root = document.querySelector('.ap-support-thread[data-support-thread]');
  if (!root) return;

  var code = root.getAttribute('data-ticket-code');
  var bodyEl = document.getElementById('supportThreadBody');
  var form = document.getElementById('supportComposer');
  if (!code || !bodyEl) return;

  var last = bodyEl.querySelector('[data-msg-id]:last-child');
  var sinceId = last ? (parseInt(last.getAttribute('data-msg-id'), 10) || 0) : 0;
  bodyEl.scrollTop = bodyEl.scrollHeight;

  function append(m) {
    var wrap = document.createElement('div');
    wrap.className = 'support-msg support-msg--' + (m.senderRole === 'admin' ? 'out' : 'in');
    wrap.setAttribute('data-msg-id', String(m.id));

    var at = '';
    if (m.attachments && m.attachments.length) {
      at = '<div class="support-attachments">' + m.attachments.map(function (a) {
        return '<a class="support-attachment" target="_blank" rel="noopener" href="' + a.url + '">' +
          '<i class="fas fa-paperclip me-1"></i>' + (a.name || 'Attachment') + '</a>';
      }).join('') + '</div>';
    }

    wrap.innerHTML =
      '<div class="support-msg__meta"><span>' + (m.senderRole || 'support') + '</span><span>' + (m.timeLabel || '') + '</span></div>' +
      '<div class="support-msg__bubble">' + String(m.body || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/\n/g, '<br>') + at + '</div>';
    bodyEl.appendChild(wrap);
    bodyEl.scrollTop = bodyEl.scrollHeight;
  }

  function poll() {
    fetch('/index.php?r=support/poll&code=' + encodeURIComponent(code) + '&since_id=' + encodeURIComponent(String(sinceId)), {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    }).then(function (r) { return r.json(); }).then(function (res) {
      if (!res || !res.success) return;
      (res.messages || []).forEach(function (m) {
        append(m);
        sinceId = Math.max(sinceId, parseInt(m.id, 10) || 0);
      });
    }).catch(function () { /* noop */ });
  }

  setInterval(function () {
    if (document.hidden) return;
    poll();
  }, 3000);

  if (form) {
    form.addEventListener('submit', function () {
      setTimeout(function () { poll(); }, 350);
    });
  }
})();

