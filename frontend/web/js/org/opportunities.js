(function () {
  var rootSelector = '[data-org-opportunities]';

  function toast(msg, variant) {
    if (window.orgToast) window.orgToast({ title: 'Opportunities', message: msg, variant: variant || 'success' });
  }

  function deleteUrl(id) {
    if (typeof window.ftOrgApiResolve === 'function') {
      var base = window.ftOrgApiResolve('deletePosition', '/position/delete');
      return base + (base.indexOf('?') >= 0 ? '&' : '?') + 'id=' + encodeURIComponent(id);
    }
    return '/index.php?r=position/delete&id=' + encodeURIComponent(id);
  }

  function toggleStatusUrl() {
    if (typeof window.ftOrgApiResolve === 'function') {
      return window.ftOrgApiResolve('togglePositionStatus', '/position/toggle-status');
    }
    return '/index.php?r=position/toggle-status';
  }

  function closeAllStatusMenus() {
    document.querySelectorAll('.org-status-menu').forEach(function (menu) {
      menu.setAttribute('hidden', '');
    });
  }

  function updateCardStatus(positionId, status, toggle) {
    document.querySelectorAll('[data-position-status-badge="' + positionId + '"]').forEach(function (badge) {
      badge.textContent = status;
      badge.className = badge.className.replace(/\b(draft|active|paused|closed)\b/g, '').trim();
      badge.classList.add(status.toLowerCase());
      if (!badge.classList.contains('org-tag')) badge.classList.add('org-tag');
    });

    var card = document.querySelector('[data-position-card="' + positionId + '"]');
    if (!card || !toggle) return;

    var toggleBtn = card.querySelector('.org-btn-status-toggle');
    if (!toggleBtn) return;

    toggleBtn.setAttribute('data-current-status', status);
    toggleBtn.setAttribute('data-next-status', toggle.next);
    toggleBtn.setAttribute('data-label', toggle.label);
    toggleBtn.setAttribute('data-icon', toggle.icon);
    toggleBtn.setAttribute('data-confirm', toggle.confirm || '');

    var icon = toggleBtn.querySelector('i.fas:not(.org-status-caret)');
    if (icon) icon.className = 'fas ' + toggle.icon;

    var label = toggleBtn.querySelector('[data-position-status-label]');
    if (label) label.textContent = toggle.label;

    toggleBtn.classList.toggle('org-btn-primary', !!toggle.primary);
    toggleBtn.classList.toggle('org-btn-ghost', !toggle.primary);

    var control = card.querySelector('.org-status-control');
    if (!control) return;

    var isActive = status === 'Active';
    control.classList.toggle('org-status-control--menu', isActive);

    var menu = control.querySelector('.org-status-menu');
    var caret = toggleBtn.querySelector('.org-status-caret');

    if (isActive) {
      toggleBtn.setAttribute('data-status-menu-trigger', '');
      if (!caret) {
        caret = document.createElement('i');
        caret.className = 'fas fa-chevron-down org-status-caret';
        toggleBtn.appendChild(caret);
      }
      if (!menu) {
        menu = document.createElement('div');
        menu.className = 'org-status-menu';
        menu.setAttribute('data-position-status-menu', String(positionId));
        menu.setAttribute('hidden', '');
        menu.innerHTML =
          '<button type="button" data-toggle-position-status="' + positionId + '" data-next-status="Paused" data-label="Pause" data-icon="fa-pause"><i class="fas fa-pause"></i> Pause</button>' +
          '<button type="button" data-close-position="' + positionId + '" data-next-status="Closed"><i class="fas fa-stop"></i> Close internship</button>';
        control.appendChild(menu);
      }
    } else {
      toggleBtn.removeAttribute('data-status-menu-trigger');
      if (caret) caret.remove();
      if (menu) menu.remove();
    }
  }

  function postStatusToggle(id, status, btn) {
    if (btn) btn.disabled = true;

    var body = 'id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(status) +
      '&' + encodeURIComponent(yii.getCsrfParam()) + '=' + encodeURIComponent(yii.getCsrfToken());

    return fetch(toggleStatusUrl(), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
      },
      credentials: 'same-origin',
      body: body,
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data && data.success) {
          updateCardStatus(id, data.status, data.toggle);
          toast(data.message || 'Status updated', 'success');
          closeAllStatusMenus();
        } else {
          toast((data && data.message) || 'Status update failed', 'danger');
        }
        return data;
      })
      .catch(function () {
        toast('Status update failed', 'danger');
      })
      .finally(function () {
        if (btn) btn.disabled = false;
      });
  }

  function bindDelegatedActions() {
    var root = document.querySelector(rootSelector);
    if (!root || root.dataset.orgOppsBound === '1') return;
    root.dataset.orgOppsBound = '1';

    root.addEventListener('click', function (e) {
      var menuTrigger = e.target.closest('[data-status-menu-trigger]');
      if (menuTrigger) {
        e.preventDefault();
        e.stopPropagation();
        var menuId = menuTrigger.getAttribute('data-toggle-position-status');
        var menu = document.querySelector('[data-position-status-menu="' + menuId + '"]');
        if (!menu) return;
        var isOpen = !menu.hasAttribute('hidden');
        closeAllStatusMenus();
        if (!isOpen) menu.removeAttribute('hidden');
        return;
      }

      var closeBtn = e.target.closest('[data-close-position]');
      if (closeBtn) {
        e.preventDefault();
        e.stopPropagation();
        var closeId = closeBtn.getAttribute('data-close-position');
        if (!closeId) return;
        if (!window.confirm('Close this internship? Students will no longer be able to apply.')) return;
        postStatusToggle(closeId, 'Closed', closeBtn);
        return;
      }

      var statusBtn = e.target.closest('[data-toggle-position-status]');
      if (statusBtn && !statusBtn.hasAttribute('data-status-menu-trigger')) {
        e.preventDefault();
        e.stopPropagation();
        var id = statusBtn.getAttribute('data-toggle-position-status');
        var nextStatus = statusBtn.getAttribute('data-next-status');
        var confirmMsg = statusBtn.getAttribute('data-confirm');
        if (!id || !nextStatus) return;
        if (confirmMsg && !window.confirm(confirmMsg)) return;
        postStatusToggle(id, nextStatus, statusBtn);
      }
    });

    document.addEventListener('click', function () {
      closeAllStatusMenus();
    });
  }

  function bindOpenModal() {
    var modalEl = document.getElementById('orgPositionModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

    document.querySelectorAll('[data-open-position-modal]').forEach(function (btn) {
      if (btn.dataset.orgBoundOpenModal === '1') return;
      btn.dataset.orgBoundOpenModal = '1';
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var url = btn.getAttribute('data-url');
        if (!url) return;
        if (window.ftSessionMonitor && typeof window.ftSessionMonitor.refreshActivity === 'function') {
          window.ftSessionMonitor.refreshActivity();
        }
        var content = modalEl.querySelector('.modal-content');
        content.innerHTML = '<div class="p-4 text-center">Loading form…</div>';
        bsModal.show();
        fetch(url, {
          headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
          credentials: 'same-origin',
        })
          .then(function (r) {
            if (!r.ok) throw new Error('Failed to load form');
            return r.text();
          })
          .then(function (html) {
            content.innerHTML = html;
            if (typeof window.orgInitPositionForm === 'function') {
              window.orgInitPositionForm(content);
            }
          })
          .catch(function () {
            content.innerHTML = '<div class="p-4 text-danger">Failed to load form. Please refresh and try again.</div>';
          });
      });
    });
  }

  function bindDelete() {
    var root = document.querySelector(rootSelector);
    if (!root) return;

    root.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-delete-position]');
      if (!btn || btn.dataset.orgDeleting === '1') return;

      var id = btn.getAttribute('data-delete-position');
      if (!id) return;
      e.preventDefault();
      if (!window.confirm('Delete this internship opportunity?')) return;

      btn.dataset.orgDeleting = '1';
      btn.disabled = true;

      fetch(deleteUrl(id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
        credentials: 'same-origin',
        body: encodeURIComponent(yii.getCsrfParam()) + '=' + encodeURIComponent(yii.getCsrfToken()),
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.success) {
            toast(data.message || 'Deleted successfully', 'success');
            var card = document.querySelector('[data-position-card="' + id + '"]');
            var article = card ? (card.closest('.org-opp-card') || card.closest('[data-position-row]')) : null;
            if (article) article.remove();
          } else {
            toast((data && data.message) || 'Delete failed', 'danger');
            btn.disabled = false;
            btn.dataset.orgDeleting = '0';
          }
        })
        .catch(function () {
          toast('Delete failed', 'danger');
          btn.disabled = false;
          btn.dataset.orgDeleting = '0';
        });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!document.body.classList.contains('org-body')) return;
    if (!document.querySelector(rootSelector)) return;
    bindOpenModal();
    bindDelete();
    bindDelegatedActions();
  });
})();
