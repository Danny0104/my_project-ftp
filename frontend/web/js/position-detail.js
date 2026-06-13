/**
 * Position / opportunity detail — scrollspy, reveals, apply wizard, bookmark, share.
 */
(function (global) {
  'use strict';

  var root = document.querySelector('.pd-page');
  if (!root) return;

  var positionId = root.getAttribute('data-position-id') || '';

  /* ---- Scroll reveal ---- */
  var revealEls = root.querySelectorAll('.pd-reveal');
  if (revealEls.length && 'IntersectionObserver' in window) {
    var io = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) {
            e.target.classList.add('is-visible');
            io.unobserve(e.target);
          }
        });
      },
      { rootMargin: '0px 0px -8% 0px', threshold: 0.08 }
    );
    revealEls.forEach(function (el) {
      io.observe(el);
    });
  } else {
    revealEls.forEach(function (el) {
      el.classList.add('is-visible');
    });
  }

  /* ---- Scrollspy nav ---- */
  var nav = root.querySelector('.pd-section-nav');
  var sections = root.querySelectorAll('.pd-section[id]');
  var navLinks = nav ? nav.querySelectorAll('a[href^="#"]') : [];

  function setActiveSpy(id) {
    if (!navLinks.length) return;
    navLinks.forEach(function (a) {
      var href = a.getAttribute('href');
      if (href === '#' + id) {
        a.classList.add('is-active');
      } else {
        a.classList.remove('is-active');
      }
    });
  }

  function onScrollSpy() {
    var scrollY = window.scrollY || window.pageYOffset;
    var offset = 140;
    var current = '';
    sections.forEach(function (sec) {
      if (sec.offsetTop <= scrollY + offset) {
        current = sec.id;
      }
    });
    if (current) setActiveSpy(current);
  }

  window.addEventListener('scroll', onScrollSpy, { passive: true });
  onScrollSpy();

  navLinks.forEach(function (a) {
    a.addEventListener('click', function (e) {
      var id = a.getAttribute('href');
      if (!id || id === '#') return;
      var target = document.querySelector(id);
      if (target) {
        e.preventDefault();
        var top = target.getBoundingClientRect().top + window.scrollY - 100;
        window.scrollTo({ top: top, behavior: 'smooth' });
      }
    });
  });

  /* ---- Sticky mobile CTA visibility ---- */
  var hero = root.querySelector('.pd-hero');
  var mobileCta = root.querySelector('.pd-mobile-cta');
  if (hero && mobileCta && 'IntersectionObserver' in window) {
    var heroIo = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (e) {
          mobileCta.classList.toggle('is-hidden', e.isIntersecting);
        });
      },
      { threshold: 0.02, rootMargin: '-60px 0px 0px 0px' }
    );
    heroIo.observe(hero);
  }

  /* ---- Bookmark (local) ---- */
  var storageKey = 'ftp_saved_positions';
  var bookmarkBtn = root.querySelector('[data-pd-bookmark]');
  function getSaved() {
    try {
      var raw = localStorage.getItem(storageKey);
      var arr = raw ? JSON.parse(raw) : [];
      return Array.isArray(arr) ? arr : [];
    } catch (err) {
      return [];
    }
  }
  function setSaved(arr) {
    try {
      localStorage.setItem(storageKey, JSON.stringify(arr));
    } catch (err) {}
  }
  function updateBookmarkUi() {
    if (!bookmarkBtn) return;
    var saved = getSaved();
    var on = saved.indexOf(positionId) !== -1;
    bookmarkBtn.classList.toggle('is-saved', on);
    bookmarkBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
    var label = on ? 'Saved' : 'Save';
    var span = bookmarkBtn.querySelector('.pd-bookmark-label');
    if (span) span.textContent = label;
  }
  if (bookmarkBtn && positionId) {
    updateBookmarkUi();
    bookmarkBtn.addEventListener('click', function () {
      var bookmarkUrl = root.getAttribute('data-bookmark-url');
      if (bookmarkUrl && global.yii) {
        bookmarkBtn.disabled = true;
        var body = 'position_id=' + encodeURIComponent(positionId)
          + '&' + encodeURIComponent(global.yii.getCsrfParam()) + '=' + encodeURIComponent(global.yii.getCsrfToken());
        fetch(bookmarkUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
          body: body,
        })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            if (res && res.success) {
              var saved = getSaved();
              var i = saved.indexOf(positionId);
              if (res.saved && i === -1) saved.push(positionId);
              if (!res.saved && i !== -1) saved.splice(i, 1);
              setSaved(saved);
              updateBookmarkUi();
            }
          })
          .finally(function () { bookmarkBtn.disabled = false; });
        return;
      }

      var saved = getSaved();
      var i = saved.indexOf(positionId);
      if (i === -1) {
        saved.push(positionId);
        bookmarkBtn.classList.add('pd-bookmark-pop');
        setTimeout(function () {
          bookmarkBtn.classList.remove('pd-bookmark-pop');
        }, 600);
      } else {
        saved.splice(i, 1);
      }
      setSaved(saved);
      updateBookmarkUi();
    });
  }

  /* ---- Portal apply modals (backup if modal-stack.js missed) ---- */
  if (global.ftModalStack && global.ftModalStack.portal) {
    document.querySelectorAll('#pdApplyModal, #pdShareModal, #pdRestrictedModal').forEach(function (el) {
      global.ftModalStack.portal(el);
    });
  }

  /* ---- Apply wizard ---- */
  var wizard = document.querySelector('[data-pd-apply-wizard]');
  if (wizard) {
    var hasQuestions = wizard.getAttribute('data-has-questions') === '1';
    var stepKeys = hasQuestions
      ? ['review', 'profile', 'questions', 'confirm']
      : ['review', 'profile', 'confirm'];
    var stepIndex = 0;
    var profileReady = wizard.getAttribute('data-profile-ready') === '1';

    var stepLabels = wizard.querySelectorAll('[data-pd-step-key]');
    var panels = wizard.querySelectorAll('[data-pd-step-panel]');
    var btnCancel = wizard.querySelector('[data-pd-wizard-cancel]');
    var btnBack = wizard.querySelector('[data-pd-wizard-back]');
    var btnNext = wizard.querySelector('[data-pd-wizard-next]');
    var btnSubmit = wizard.querySelector('[data-pd-wizard-submit]');
    var footer = wizard.querySelector('[data-pd-wizard-footer]');
    var errorBox = wizard.querySelector('[data-pd-wizard-error]');
    var declaration = wizard.querySelector('[data-pd-declaration]');
    var applyUrl = wizard.getAttribute('data-apply-url') || '';
    var csrfParam = wizard.getAttribute('data-csrf-param') || '_csrf';
    var csrfToken = wizard.getAttribute('data-csrf-token') || '';
    var submitting = false;

    function getPanel(key) {
      return wizard.querySelector('[data-pd-step-panel="' + key + '"]');
    }

    function clearError() {
      if (!errorBox) return;
      errorBox.textContent = '';
      errorBox.classList.add('d-none');
    }

    function showError(message) {
      if (!errorBox) return;
      errorBox.textContent = message;
      errorBox.classList.remove('d-none');
    }

    function syncStepLabels() {
      var currentKey = stepKeys[stepIndex];
      stepLabels.forEach(function (label) {
        var key = label.getAttribute('data-pd-step-key');
        if (key === 'questions' && !hasQuestions) {
          return;
        }
        var keyIndex = stepKeys.indexOf(key);
        if (keyIndex === -1 && key === 'questions') {
          label.hidden = true;
          return;
        }
        label.classList.toggle('is-current', key === currentKey);
        label.classList.toggle('is-done', keyIndex !== -1 && keyIndex < stepIndex);
      });
    }

    function syncFooter() {
      var currentKey = stepKeys[stepIndex];
      var onSuccess = currentKey === 'success';
      if (footer) footer.hidden = onSuccess;
      if (btnCancel) btnCancel.hidden = stepIndex > 0 || onSuccess;
      if (btnBack) btnBack.hidden = stepIndex <= 0 || onSuccess;
      if (btnNext) btnNext.hidden = currentKey === 'confirm' || onSuccess;
      if (btnSubmit) btnSubmit.hidden = currentKey !== 'confirm' || onSuccess;
    }

    function showStep(index) {
      stepIndex = Math.max(0, Math.min(index, stepKeys.length - 1));
      clearError();
      var currentKey = stepKeys[stepIndex];
      panels.forEach(function (panel) {
        var key = panel.getAttribute('data-pd-step-panel');
        panel.hidden = key !== currentKey;
      });
      syncStepLabels();
      syncFooter();
    }

    function showSuccess() {
      clearError();
      panels.forEach(function (panel) {
        panel.hidden = panel.getAttribute('data-pd-step-panel') !== 'success';
      });
      stepLabels.forEach(function (label) {
        label.classList.remove('is-current');
        label.classList.add('is-done');
      });
      if (footer) footer.hidden = true;
      if (btnCancel) btnCancel.hidden = true;
      if (btnBack) btnBack.hidden = true;
      if (btnNext) btnNext.hidden = true;
      if (btnSubmit) btnSubmit.hidden = true;
    }

    function validateQuestions() {
      var form = wizard.querySelector('[data-pd-questions-form]');
      if (!form) return true;
      var missing = [];
      form.querySelectorAll('[data-pd-question-id]').forEach(function (wrap) {
        var required = wrap.getAttribute('data-pd-question-required') === '1';
        if (!required) return;
        var input = wrap.querySelector('input, textarea, select');
        if (!input) return;
        if (input.type === 'file') {
          if (!input.files || !input.files.length) {
            missing.push(wrap.querySelector('label') ? wrap.querySelector('label').textContent.replace(/\s*\*$/, '') : 'A required question');
          }
        } else if (!String(input.value || '').trim()) {
          missing.push(wrap.querySelector('label') ? wrap.querySelector('label').textContent.replace(/\s*\*$/, '') : 'A required question');
        }
      });
      if (missing.length) {
        showError('Please answer all required questions: ' + missing.join(', ') + '.');
        return false;
      }
      return true;
    }

    function collectFormData() {
      var data = new FormData();
      data.append(csrfParam, csrfToken);
      data.append('declaration', declaration && declaration.checked ? '1' : '0');

      var questionsForm = wizard.querySelector('[data-pd-questions-form]');
      if (questionsForm) {
        questionsForm.querySelectorAll('input, textarea, select').forEach(function (input) {
          if (!input.name) return;
          if (input.type === 'file') {
            if (input.files && input.files[0]) {
              data.append(input.name, input.files[0]);
            }
          } else {
            data.append(input.name, input.value);
          }
        });
      }

      return data;
    }

    function submitApplication() {
      if (submitting) return;
      clearError();

      if (!profileReady) {
        showError('Please complete your profile before submitting.');
        return;
      }

      if (!declaration || !declaration.checked) {
        showError('Please confirm the declaration before submitting.');
        return;
      }

      if (hasQuestions && !validateQuestions()) {
        return;
      }

      submitting = true;
      if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting…';
      }

      fetch(applyUrl, {
        method: 'POST',
        body: collectFormData(),
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then(function (response) {
          return response.json().then(function (payload) {
            return { ok: response.ok, payload: payload };
          });
        })
        .then(function (result) {
          if (result.payload && result.payload.success) {
            showSuccess();
            var heroApply = document.getElementById('pdHeroApply');
            if (heroApply) {
              heroApply.outerHTML = '<a href="' + (result.payload.applicationsUrl || '/application/index') + '" class="pd-btn-primary"><i class="fas fa-file-lines me-2"></i>View application</a>';
            }
            return;
          }
          var message = (result.payload && result.payload.message)
            || 'Could not submit application. Please try again.';
          if (result.payload && result.payload.errors && result.payload.errors.length) {
            message = result.payload.errors.join(' ');
          }
          showError(message);
        })
        .catch(function () {
          showError('Network error. Please check your connection and try again.');
        })
        .finally(function () {
          submitting = false;
          if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Submit Application';
          }
        });
    }

    if (btnNext) {
      btnNext.addEventListener('click', function () {
        clearError();
        var currentKey = stepKeys[stepIndex];
        if (currentKey === 'profile' && !profileReady) {
          showError('Please complete your profile before continuing.');
          return;
        }
        if (currentKey === 'questions' && !validateQuestions()) {
          return;
        }
        showStep(stepIndex + 1);
      });
    }

    if (btnBack) {
      btnBack.addEventListener('click', function () {
        showStep(stepIndex - 1);
      });
    }

    if (btnSubmit) {
      btnSubmit.addEventListener('click', submitApplication);
    }

    wizard.addEventListener('show.bs.modal', function () {
      stepIndex = 0;
      submitting = false;
      if (declaration) declaration.checked = false;
      clearError();
      panels.forEach(function (panel) {
        panel.hidden = panel.getAttribute('data-pd-step-panel') !== 'review';
      });
      syncStepLabels();
      syncFooter();
    });

    if (window.location.hash === '#apply') {
      var modalInstance = global.bootstrap && global.bootstrap.Modal
        ? global.bootstrap.Modal.getOrCreateInstance(wizard)
        : null;
      if (modalInstance) {
        modalInstance.show();
      }
    }
  }

  /* ---- Share copy ---- */
  var copyBtn = document.getElementById('pdShareCopy');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var input = document.getElementById('pdShareUrl');
      if (!input) return;
      input.select();
      input.setSelectionRange(0, 99999);
      var url = input.value;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function () {
          copyBtn.textContent = 'Copied!';
          setTimeout(function () {
            copyBtn.textContent = 'Copy link';
          }, 2000);
        });
      } else {
        document.execCommand('copy');
        copyBtn.textContent = 'Copied!';
        setTimeout(function () {
          copyBtn.textContent = 'Copy link';
        }, 2000);
      }
    });
  }

  /* ---- Radial progress (match ring) ---- */
  var ring = root.querySelector('.pd-match-ring__progress');
  if (ring) {
    var pct = parseInt(ring.getAttribute('data-score'), 10);
    if (!isNaN(pct)) {
      var c = 2 * Math.PI * 40;
      var offset = c - (pct / 100) * c;
      ring.style.strokeDashoffset = String(offset);
    }
  }
})(typeof window !== 'undefined' ? window : this);
