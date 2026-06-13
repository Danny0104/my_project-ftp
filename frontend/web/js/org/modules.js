/* Organization module pages — charts, modals, AJAX forms */
(function () {
  'use strict';

  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
  var chartInstances = {};

  function animateCounters() {
    qsa('[data-org-counter]').forEach(function (el) {
      var target = parseInt(el.getAttribute('data-org-counter'), 10) || 0;
      var start = performance.now();
      var duration = 900;
      function tick(now) {
        var p = Math.min((now - start) / duration, 1);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = String(Math.floor(target * eased));
        if (p < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    });
  }

  function initCharts() {
    if (typeof Chart === 'undefined') return;
    if (document.getElementById('orgAnalyticsRoot')) return;

    function makeChart(id, type, colors) {
      var canvas = document.getElementById(id);
      if (!canvas) return;
      var labels = [];
      var values = [];
      try {
        labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
        values = JSON.parse(canvas.getAttribute('data-values') || '[]');
      } catch (e) { return; }

      if (chartInstances[id]) {
        chartInstances[id].destroy();
      }

      var dataset = {
        label: '',
        data: values,
        backgroundColor: colors,
        borderColor: type === 'line' ? '#2f76ff' : colors,
        borderWidth: type === 'line' ? 2 : 0,
        fill: type === 'line',
        tension: 0.35,
      };

      if (type === 'bar') {
        dataset.borderRadius = 10;
        dataset.maxBarThickness = 32;
        dataset.hoverBorderWidth = 1;
      }

      chartInstances[id] = new Chart(canvas, {
        type: type,
        data: {
          labels: labels,
          datasets: [dataset],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          resizeDelay: 120,
          layout: { padding: { top: 6, right: 8, bottom: 6, left: 8 } },
          animation: { duration: 500, easing: 'easeOutCubic' },
          plugins: {
            legend: {
              display: type === 'doughnut',
              labels: { color: 'rgba(255,255,255,.78)', boxWidth: 12, boxHeight: 12, useBorderRadius: true },
            },
            tooltip: {
              displayColors: false,
              backgroundColor: 'rgba(9,12,24,.92)',
              titleColor: '#fff',
              bodyColor: 'rgba(255,255,255,.9)',
            },
          },
          radius: type === 'doughnut' ? '74%' : undefined,
          cutout: type === 'doughnut' ? '62%' : undefined,
          scales: type === 'doughnut' ? {} : {
            x: {
              ticks: { color: 'rgba(255,255,255,.66)', maxRotation: 0, autoSkip: true },
              grid: { color: 'rgba(255,255,255,.05)', drawBorder: false },
            },
            y: {
              beginAtZero: true,
              ticks: { color: 'rgba(255,255,255,.66)', precision: 0 },
              grid: { color: 'rgba(255,255,255,.06)', drawBorder: false },
            },
          },
        },
      });
    }

    var palette = ['#2f76ff', '#6d5cff', '#22c55e', '#f59e0b', '#fb7185', '#38bdf8'];
    makeChart('orgChartTrends', 'line', '#2f76ff');
    makeChart('orgChartPipeline', 'doughnut', palette);
    makeChart('orgChartFields', 'bar', palette);
    makeChart('orgChartReviews', 'bar', palette);
  }

  function initModals() {
    qsa('[data-org-open-modal]').forEach(function (btn) {
      if (btn.dataset.orgBoundModal === '1') return;
      btn.dataset.orgBoundModal = '1';
      btn.addEventListener('click', function () {
        var key = btn.getAttribute('data-org-open-modal');
        var modal = qs('[data-org-modal="' + key + '"]');
        if (modal) modal.classList.add('is-open');
      });
    });
    qsa('.org-modal-backdrop').forEach(function (backdrop) {
      if (backdrop.dataset.orgBoundModalClose === '1') return;
      backdrop.dataset.orgBoundModalClose = '1';
      backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) backdrop.classList.remove('is-open');
      });
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      qsa('.org-modal-backdrop.is-open').forEach(function (backdrop) {
        backdrop.classList.remove('is-open');
      });
    });
  }

  function postJson(url, data) {
    var fd = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData)) {
      Object.keys(data).forEach(function (key) { fd.append(key, data[key]); });
    }
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

  function initAppActions() {
    qsa('[data-org-app-action]').forEach(function (btn) {
      if (btn.dataset.orgBoundAppAction === '1') return;
      btn.dataset.orgBoundAppAction = '1';
      btn.addEventListener('click', function () {
        var appId = btn.getAttribute('data-app-id');
        var status = btn.getAttribute('data-org-app-action');
        var url = btn.getAttribute('data-status-url');
        if (!appId || !status || !url) return;

        var original = btn.textContent;
        btn.disabled = true;
        btn.classList.add('is-loading');
        btn.textContent = 'Saving…';

        postJson(url, { application_id: appId, status: status })
          .then(function (res) {
            if (res.success) {
              if (window.orgToast) orgToast({ title: 'Updated', message: res.message || 'Application updated', variant: 'success' });
              setTimeout(function () { location.reload(); }, 400);
              return;
            }
            btn.disabled = false;
            btn.classList.remove('is-loading');
            btn.textContent = original;
            if (window.orgToast) orgToast({ title: 'Error', message: res.message || 'Update failed', variant: 'danger' });
          })
          .catch(function () {
            btn.disabled = false;
            btn.classList.remove('is-loading');
            btn.textContent = original;
            if (window.orgToast) orgToast({ title: 'Error', message: 'Update failed. Please try again.', variant: 'danger' });
          });
      });
    });
  }

  function setButtonLoading(btn, loading, originalText) {
    if (!btn) return;
    btn.disabled = !!loading;
    btn.classList.toggle('is-loading', !!loading);
    if (loading) {
      btn.dataset.orgOriginalText = originalText || btn.textContent;
      btn.textContent = 'Saving…';
    } else if (btn.dataset.orgOriginalText) {
      btn.textContent = btn.dataset.orgOriginalText;
      delete btn.dataset.orgOriginalText;
    }
  }

  function initAjaxForms() {
    qsa('[data-org-ajax-form]').forEach(function (form) {
      if (form.dataset.orgBoundAjax === '1') return;
      form.dataset.orgBoundAjax = '1';
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (form.dataset.orgSubmitting === '1') return;
        form.dataset.orgSubmitting = '1';

        var url = form.getAttribute('data-org-ajax-form');
        var fd = new FormData(form);
        var btn = form.querySelector('[type="submit"]');
        var originalText = btn ? btn.textContent : '';
        setButtonLoading(btn, true, originalText);

        fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res.success) {
              var variant = res.already_exists ? 'info' : 'success';
              if (window.orgToast) {
                orgToast({
                  title: res.already_exists ? 'Already scheduled' : 'Saved',
                  message: res.message || (res.already_exists ? 'Interview already scheduled.' : 'Done'),
                  variant: variant,
                });
              }
              setTimeout(function () { location.reload(); }, 400);
              return;
            }
            form.dataset.orgSubmitting = '0';
            setButtonLoading(btn, false);
            if (window.orgToast) {
              orgToast({ title: 'Error', message: res.message || 'Request failed', variant: 'danger' });
            }
          })
          .catch(function () {
            form.dataset.orgSubmitting = '0';
            setButtonLoading(btn, false);
            if (window.orgToast) orgToast({ title: 'Error', message: 'Network error', variant: 'danger' });
          });
      });
    });
  }

  function initScheduleInterview() {
    qsa('[data-org-schedule-interview]').forEach(function (btn) {
      if (btn.dataset.orgBoundSchedule === '1') return;
      btn.dataset.orgBoundSchedule = '1';

      btn.addEventListener('click', function () {
        if (btn.disabled || btn.dataset.orgScheduling === '1') return;

        var appId = btn.getAttribute('data-org-schedule-interview');
        var url = btn.getAttribute('data-schedule-url');
        if (!appId || !url) return;

        var originalText = btn.textContent;
        btn.dataset.orgScheduling = '1';
        setButtonLoading(btn, true, originalText);

        var scheduledAt = new Date(Date.now() + 3 * 86400000);
        var pad = function (n) { return String(n).padStart(2, '0'); };
        var when = scheduledAt.getFullYear() + '-'
          + pad(scheduledAt.getMonth() + 1) + '-'
          + pad(scheduledAt.getDate()) + ' '
          + pad(scheduledAt.getHours()) + ':'
          + pad(scheduledAt.getMinutes()) + ':'
          + pad(scheduledAt.getSeconds());

        postJson(url, {
          application_id: appId,
          scheduled_at: when,
          interview_stage: 'interview',
        })
          .then(function (res) {
            if (res.success) {
              var variant = res.already_exists ? 'info' : 'success';
              if (window.orgToast) {
                orgToast({
                  title: res.already_exists ? 'Already scheduled' : 'Scheduled',
                  message: res.message || (res.already_exists ? 'Interview already scheduled.' : 'Interview created'),
                  variant: variant,
                });
              }
              if (!res.already_exists) {
                setTimeout(function () { location.reload(); }, 400);
              }
              return;
            }
            if (window.orgToast) {
              orgToast({ title: 'Error', message: res.message || 'Could not schedule interview', variant: 'danger' });
            }
          })
          .catch(function () {
            if (window.orgToast) {
              orgToast({ title: 'Error', message: 'Could not schedule interview', variant: 'danger' });
            }
          })
          .finally(function () {
            btn.dataset.orgScheduling = '0';
            setButtonLoading(btn, false);
          });
      });
    });
  }

  function initTabs() {
    var root = qs('[data-org-tabs]');
    if (!root) return;
    var links = qsa('[data-tab-target]', root);
    links.forEach(function (link) {
      if (link.dataset.orgBoundTab === '1') return;
      link.dataset.orgBoundTab = '1';
      link.addEventListener('click', function (e) {
        e.preventDefault();
        var target = link.getAttribute('data-tab-target');
        links.forEach(function (a) { a.classList.remove('is-active'); });
        link.classList.add('is-active');
        qsa('.org-tab-pane').forEach(function (pane) {
          pane.classList.toggle('is-active', pane.getAttribute('data-tab-pane') === target);
        });
      });
    });
  }

  function initSettingsSaveState() {
    var form = qs('[data-org-settings-form]');
    var bar = qs('#orgStickySaveBar');
    if (!form || !bar) return;

    var dirty = false;
    function setDirty() {
      dirty = true;
      bar.classList.add('is-active');
    }
    function clearDirty() {
      dirty = false;
      bar.classList.remove('is-active');
    }

    qsa('input, textarea, select', form).forEach(function (el) {
      el.addEventListener('input', setDirty);
      el.addEventListener('change', setDirty);
    });

    var resetBtn = qs('#orgResetSettings');
    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        form.reset();
        clearDirty();
      });
    }

    form.addEventListener('submit', function () {
      clearDirty();
    });

    window.addEventListener('beforeunload', function (e) {
      if (!dirty) return;
      e.preventDefault();
      e.returnValue = '';
    });
  }

  function initPasswordStrength() {
    var input = qs('#orgPasswordInput');
    var out = qs('#orgPasswordStrength');
    if (!input || !out || input.dataset.orgBoundStrength === '1') return;
    input.dataset.orgBoundStrength = '1';

    input.addEventListener('input', function () {
      var v = input.value || '';
      if (!v) {
        out.textContent = 'Strength: —';
        return;
      }
      var score = 0;
      if (v.length >= 8) score++;
      if (/[A-Z]/.test(v)) score++;
      if (/[a-z]/.test(v)) score++;
      if (/\d/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      var label = score <= 2 ? 'Weak' : (score <= 4 ? 'Medium' : 'Strong');
      out.textContent = 'Strength: ' + label;
    });
  }

  function bootstrapModulesUi() {
    animateCounters();
    initCharts();
    initModals();
    initAppActions();
    initScheduleInterview();
    initAjaxForms();
    initTabs();
    initSettingsSaveState();
    initPasswordStrength();
  }

  document.addEventListener('DOMContentLoaded', bootstrapModulesUi);
  document.addEventListener('pjax:end', bootstrapModulesUi);
})();
