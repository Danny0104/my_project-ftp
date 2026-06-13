/* Admin module pages — search, view toggles */
(function () {
  'use strict';

  function initModuleSearch() {
    document.querySelectorAll('.ap-module-search').forEach(function (input) {
      if (input.dataset.apSearchBound) return;
      input.dataset.apSearchBound = '1';

      input.addEventListener('input', function () {
        var q = (input.value || '').toLowerCase().trim();
        var gridId = input.getAttribute('data-target');
        var root = gridId ? document.getElementById(gridId) : document;
        if (!root) return;

        root.querySelectorAll('[data-search]').forEach(function (card) {
          var hay = (card.getAttribute('data-search') || '').toLowerCase();
          card.style.display = !q || hay.indexOf(q) !== -1 ? '' : 'none';
        });
      });
    });
  }

  function initViewToggle() {
    document.querySelectorAll('[data-ap-view-toggle]').forEach(function (wrap) {
      if (wrap.dataset.apViewBound) return;
      wrap.dataset.apViewBound = '1';
      var targetId = wrap.getAttribute('data-ap-view-toggle');
      var target = document.getElementById(targetId);
      if (!target) return;

      wrap.querySelectorAll('button').forEach(function (btn) {
        btn.addEventListener('click', function () {
          wrap.querySelectorAll('button').forEach(function (b) { b.classList.remove('is-active'); });
          btn.classList.add('is-active');
          var view = btn.getAttribute('data-view');
          target.classList.toggle('ap-view--list', view === 'list');
          target.classList.toggle('ap-view--grid', view === 'grid');
        });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initModuleSearch();
    initViewToggle();
  });
})();
