(function () {
  function toast(msg, variant) {
    if (window.orgToast) window.orgToast({ title: 'ATS', message: msg, variant: variant || 'success' });
  }

  function updateStageUrl() {
    if (typeof window.ftOrgApiResolve === 'function') {
      return window.ftOrgApiResolve('updateStage', '/application/update-stage');
    }
    return '/index.php?r=application/update-stage';
  }

  function updateStage(id, status) {
    return fetch(updateStageUrl(), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
      },
      credentials: 'same-origin',
      body:
        'id=' + encodeURIComponent(id) +
        '&status=' + encodeURIComponent(status) +
        '&' + encodeURIComponent(yii.getCsrfParam()) + '=' + encodeURIComponent(yii.getCsrfToken()),
    }).then(function (r) {
      if (!r.ok) {
        return r.text().then(function (text) {
          throw new Error(text || 'Request failed');
        });
      }
      return r.json();
    });
  }

  var activeFilter = 'all';

  function applyFilters() {
    var q = (document.getElementById('orgAtsSearch')?.value || '').toLowerCase().trim();
    document.querySelectorAll('.org-app-card').forEach(function (card) {
      var txt = card.getAttribute('data-search') || '';
      var matchSearch = !q || txt.indexOf(q) !== -1;
      var top = card.getAttribute('data-top-match') === '1';
      var risk = card.getAttribute('data-at-risk') === '1';
      var matchChip = true;
      if (activeFilter === 'top') matchChip = top;
      if (activeFilter === 'risk') matchChip = risk;
      card.style.display = matchSearch && matchChip ? '' : 'none';
    });
  }

  document.querySelectorAll('[data-org-ats-filter]').forEach(function (chip) {
    chip.addEventListener('click', function () {
      document.querySelectorAll('[data-org-ats-filter]').forEach(function (c) {
        c.classList.remove('is-active');
      });
      chip.classList.add('is-active');
      activeFilter = chip.getAttribute('data-org-ats-filter') || 'all';
      applyFilters();
    });
  });

  document.getElementById('orgAtsSearch')?.addEventListener('input', applyFilters);

  var draggedCard = null;

  document.querySelectorAll('.org-app-card[draggable="true"]').forEach(function (card) {
    card.addEventListener('dragstart', function (e) {
      draggedCard = card;
      card.classList.add('is-dragging');
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', card.getAttribute('data-id') || '');
      }
    });
    card.addEventListener('dragend', function () {
      card.classList.remove('is-dragging');
      draggedCard = null;
      document.querySelectorAll('.org-col').forEach(function (col) {
        col.classList.remove('is-drop-target');
      });
    });
  });

  document.querySelectorAll('.org-col[data-col]').forEach(function (col) {
    col.addEventListener('dragover', function (e) {
      e.preventDefault();
      col.classList.add('is-drop-target');
      if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
    });
    col.addEventListener('dragleave', function () {
      col.classList.remove('is-drop-target');
    });
    col.addEventListener('drop', function (e) {
      e.preventDefault();
      col.classList.remove('is-drop-target');
      if (!draggedCard) return;
      var id = draggedCard.getAttribute('data-id');
      var status = col.getAttribute('data-col');
      if (!id || !status || draggedCard.getAttribute('data-status') === status) return;

      if (status === 'rejected' && !window.confirm('Reject this candidate?')) return;

      updateStage(id, status).then(function (res) {
        if (res && res.success) {
          toast(res.message || 'Candidate moved', 'success');
          setTimeout(function () { window.location.reload(); }, 400);
        } else {
          toast((res && res.message) || 'Stage update failed', 'danger');
        }
      }).catch(function () {
        toast('Stage update failed', 'danger');
      });
    });
  });

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-stage-update]');
    if (!btn || btn.disabled) return;
    var id = btn.getAttribute('data-id');
    var status = btn.getAttribute('data-stage-update');
    if (!id || !status) return;

    var original = btn.textContent;
    btn.disabled = true;
    btn.classList.add('is-loading');
    btn.textContent = 'Updating…';

    updateStage(id, status).then(function (res) {
      if (res && res.success) {
        toast(res.message || 'Candidate moved to next stage', 'success');
        setTimeout(function () { window.location.reload(); }, 450);
      } else {
        btn.disabled = false;
        btn.classList.remove('is-loading');
        btn.textContent = original;
        toast((res && res.message) || 'Stage update failed', 'danger');
      }
    }).catch(function () {
      btn.disabled = false;
      btn.classList.remove('is-loading');
      btn.textContent = original;
      toast('Stage update failed. Please refresh and try again.', 'danger');
    });
  });
})();
