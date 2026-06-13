/* Admin CRUD modals and AJAX forms */
(function () {
  'use strict';

  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  function toast(title, message, variant) {
    if (window.apToast) {
      window.apToast({ title: title, message: message, variant: variant || 'success' });
      return;
    }
    alert(message);
  }

  function postForm(url, form) {
    var fd = new FormData(form);
    if (window.yii) {
      fd.append(yii.getCsrfParam(), yii.getCsrfToken());
    }
    return fetch(url, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
    }).then(function (r) {
      if (!r.ok) throw new Error('Request failed');
      return r.json();
    });
  }

  function initModals() {
    qsa('[data-ap-open-modal]').forEach(function (btn) {
      if (btn.dataset.apBoundModal === '1') return;
      btn.dataset.apBoundModal = '1';
      btn.addEventListener('click', function () {
        var key = btn.getAttribute('data-ap-open-modal');
        var modal = qs('[data-ap-modal="' + key + '"]');
        if (!modal) return;

        var form = qs('form', modal);
        if (form) {
          form.reset();
          qsa('[data-ap-prefill-target]', modal).forEach(function (input) {
            var keyName = input.getAttribute('data-ap-prefill-target');
            if (btn.hasAttribute('data-prefill-' + keyName)) {
              input.value = btn.getAttribute('data-prefill-' + keyName);
            }
          });
        }

        modal.classList.add('is-open');
      });
    });

    qsa('[data-ap-close-modal]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var modal = btn.closest('.ap-modal-backdrop');
        if (modal) modal.classList.remove('is-open');
      });
    });

    qsa('.ap-modal-backdrop').forEach(function (backdrop) {
      backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) backdrop.classList.remove('is-open');
      });
    });
  }

  function initAjaxForms() {
    qsa('[data-ap-ajax-form]').forEach(function (form) {
      if (form.dataset.apBoundAjax === '1') return;
      form.dataset.apBoundAjax = '1';
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var url = form.getAttribute('data-ap-ajax-form');
        var btn = qs('[type="submit"]', form);
        if (btn) {
          btn.disabled = true;
          btn.dataset.originalText = btn.textContent;
          btn.textContent = 'Saving…';
        }
        postForm(url, form)
          .then(function (res) {
            if (res.success) {
              toast('Saved', res.message || 'Changes saved.', 'success');
              setTimeout(function () { window.location.reload(); }, 350);
              return;
            }
            toast('Error', res.message || 'Save failed.', 'danger');
          })
          .catch(function () {
            toast('Error', 'Save failed. Please try again.', 'danger');
          })
          .finally(function () {
            if (btn) {
              btn.disabled = false;
              btn.textContent = btn.dataset.originalText || 'Save';
            }
          });
      });
    });
  }

  function initDeleteButtons() {
    qsa('[data-ap-delete]').forEach(function (btn) {
      if (btn.dataset.apBoundDelete === '1') return;
      btn.dataset.apBoundDelete = '1';
      btn.addEventListener('click', function () {
        if (!window.confirm(btn.getAttribute('data-confirm') || 'Delete this item?')) return;
        var url = btn.getAttribute('data-ap-delete');
        var fd = new FormData();
        fd.append('id', btn.getAttribute('data-id') || '');
        if (window.yii) fd.append(yii.getCsrfParam(), yii.getCsrfToken());
        btn.disabled = true;
        fetch(url, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res.success) {
              toast('Deleted', res.message || 'Item removed.', 'success');
              setTimeout(function () { window.location.reload(); }, 350);
              return;
            }
            btn.disabled = false;
            toast('Error', res.message || 'Delete failed.', 'danger');
          })
          .catch(function () {
            btn.disabled = false;
            toast('Error', 'Delete failed.', 'danger');
          });
      });
    });
  }

  function initThemeSettings() {
    var form = qs('[data-ap-theme-form]');
    if (!form || form.dataset.apBoundTheme === '1') return;
    form.dataset.apBoundTheme = '1';
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      postForm(form.getAttribute('action'), form)
        .then(function (res) {
          if (!res.success) {
            toast('Theme', res.message || 'Could not save theme.', 'danger');
            return;
          }
          if (window.ftThemeBridge && res.theme) {
            window.ftThemeBridge.apply(res.theme, 'ftp_admin_theme');
            document.body.classList.toggle('ap-dark', res.theme === 'dark');
          }
          toast('Theme', res.message || 'Theme saved.', 'success');
        })
        .catch(function () {
          toast('Theme', 'Could not save theme.', 'danger');
        });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initModals();
    initAjaxForms();
    initDeleteButtons();
    initThemeSettings();
  });
})();
