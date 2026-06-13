/* Organization panel — app shell behaviors (no backend changes). */
(function () {
  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

  var THEME_KEY = 'ftp_org_theme';
  var SIDEBAR_COLLAPSE_KEY = 'ftp_org_sidebar_collapsed';

  function isMobile() {
    return window.matchMedia && window.matchMedia('(max-width: 1023.98px)').matches;
  }

  function isDesktop() {
    return window.matchMedia && window.matchMedia('(min-width: 1024px)').matches;
  }

  function applyTheme(theme) {
    var t = theme === 'light' ? 'light' : 'dark';
    if (window.ftThemeBridge) {
      window.ftThemeBridge.apply(t, THEME_KEY);
      return;
    }
    document.documentElement.setAttribute('data-theme', t);
    try { localStorage.setItem(THEME_KEY, t); } catch (e) {}
  }

  function initTheme() {
    var saved = null;
    try { saved = localStorage.getItem(THEME_KEY); } catch (e) {}
    if (saved) return applyTheme(saved);
    // default: follow system
    var prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
    applyTheme(prefersLight ? 'light' : 'dark');
  }

  function initSidebar() {
    var sidebar = qs('#orgSidebar');
    var overlay = qs('#orgSidebarOverlay');
    var toggle = qs('#orgSidebarToggle');
    if (!sidebar || !overlay || !toggle) return;

    function lockScroll() {
      if (window.ftLayoutShell) window.ftLayoutShell.lockBodyScroll();
      else document.body.style.overflow = 'hidden';
    }
    function unlockScroll() {
      if (window.ftLayoutShell) window.ftLayoutShell.unlockBodyScroll();
      else document.body.style.overflow = '';
    }
    function open() {
      sidebar.classList.add('is-open');
      overlay.classList.add('is-open');
      lockScroll();
    }
    function close() {
      sidebar.classList.remove('is-open');
      overlay.classList.remove('is-open');
      unlockScroll();
    }

    function syncCollapseFromStorage() {
      if (!isDesktop()) {
        sidebar.classList.remove('is-collapsed');
        return;
      }
      var collapsed = false;
      try { collapsed = localStorage.getItem(SIDEBAR_COLLAPSE_KEY) === '1'; } catch (e) {}
      sidebar.classList.toggle('is-collapsed', collapsed);
    }

    function triggerResize() {
      window.setTimeout(function () {
        window.dispatchEvent(new Event('resize'));
      }, 260);
    }

    // Desktop click = collapse/expand, Mobile click = existing overlay open/close.
    toggle.addEventListener('click', function () {
      if (isMobile()) {
        if (sidebar.classList.contains('is-open')) close();
        else open();
        return;
      }

      var willCollapse = !sidebar.classList.contains('is-collapsed');
      sidebar.classList.toggle('is-collapsed', willCollapse);
      try { localStorage.setItem(SIDEBAR_COLLAPSE_KEY, willCollapse ? '1' : '0'); } catch (e) {}

      // Ensure overlay stays closed on desktop.
      overlay.classList.remove('is-open');
      unlockScroll();
      triggerResize();
    });
    overlay.addEventListener('click', close);
    window.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') close();
    });
    window.addEventListener('resize', function () {
      if (!isMobile()) close();
    });

    // Initial state
    syncCollapseFromStorage();
  }

  function toast(opts) {
    var o = opts || {};
    var title = o.title || 'Update';
    var message = o.message || '';
    var variant = o.variant || 'success'; // success|warning|danger|info
    var timeout = typeof o.timeout === 'number' ? o.timeout : 3200;

    var stack = qs('#orgToastStack');
    if (!stack) return;

    var el = document.createElement('div');
    el.className = 'org-toast is-' + variant;
    el.innerHTML =
      '<div class="t-icon" aria-hidden="true"><i class="fas fa-' + (variant === 'danger' ? 'triangle-exclamation' : variant === 'warning' ? 'circle-exclamation' : 'check') + '"></i></div>' +
      '<div class="t-body"><p class="t-title"></p><p class="t-msg"></p></div>' +
      '<button type="button" class="t-x" aria-label="Close"><i class="fas fa-xmark"></i></button>';
    qs('.t-title', el).textContent = title;
    qs('.t-msg', el).textContent = message;
    qs('.t-x', el).addEventListener('click', function () { el.remove(); });
    stack.appendChild(el);

    if (timeout > 0) {
      setTimeout(function () { el.remove(); }, timeout);
    }
  }

  // Export for inline AJAX success handlers (kept minimal).
  window.orgToast = toast;

  function initThemeToggle() {
    var btn = qs('#orgThemeToggle');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var curr = document.documentElement.getAttribute('data-theme') || 'dark';
      applyTheme(curr === 'dark' ? 'light' : 'dark');
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initTheme();
    initSidebar();
    initThemeToggle();
  });

  // If viewport crosses breakpoint, keep UI consistent.
  window.addEventListener('resize', function () {
    var sidebar = qs('#orgSidebar');
    if (!sidebar) return;
    if (isMobile()) {
      // Mobile uses off-canvas, never the collapsed (80px) state.
      sidebar.classList.remove('is-collapsed');
      return;
    }

    // Returning to desktop: re-apply saved collapsed state.
    var collapsed = false;
    try { collapsed = localStorage.getItem(SIDEBAR_COLLAPSE_KEY) === '1'; } catch (e) {}
    sidebar.classList.toggle('is-collapsed', collapsed);
  });
})();

