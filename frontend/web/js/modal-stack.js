/**
 * Portal Bootstrap modals and custom overlays to <body> so they sit above backdrops
 * inside dashboard flex shells. Handles missing-target errors gracefully.
 */
(function (global) {
    'use strict';

    var PORTAL_ATTR = 'data-ft-modal-portaled';
    var PORTAL_SELECTORS = [
        '.modal',
        '.org-modal-backdrop',
        '.spa-drawer',
        '.spa-drawer-backdrop',
        '.sp-om-filter-backdrop',
    ];

    function portalNode(node) {
        if (!node || node.getAttribute(PORTAL_ATTR) === '1') {
            return node;
        }
        if (node.classList.contains('modal')) {
            node.classList.add('ft-modal-stack');
        }
        if (node.parentNode !== document.body) {
            document.body.appendChild(node);
        }
        node.setAttribute(PORTAL_ATTR, '1');
        return node;
    }

    function portalExisting() {
        PORTAL_SELECTORS.forEach(function (selector) {
            document.querySelectorAll(selector).forEach(portalNode);
        });
    }

    function closeCustomOverlays() {
        document.querySelectorAll('.spa-drawer.is-open').forEach(function (drawer) {
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
        });
        document.querySelectorAll('.spa-drawer-backdrop').forEach(function (backdrop) {
            backdrop.hidden = true;
        });
        document.querySelectorAll('.org-modal-backdrop.is-open').forEach(function (backdrop) {
            backdrop.classList.remove('is-open');
        });
        document.querySelectorAll('.sp-om-filter-backdrop.is-open, .sp-om-filter-backdrop.show').forEach(function (backdrop) {
            backdrop.classList.remove('is-open', 'show');
        });
    }

    function cleanupDuplicateBackdrops() {
        var backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length <= 1) {
            return;
        }
        for (var i = 1; i < backdrops.length; i++) {
            backdrops[i].remove();
        }
    }

    function setShellModalOpen(open) {
        document.body.classList.toggle('ft-shell-modal-open', open);
    }

    function ensureErrorUi() {
        var existing = document.getElementById('ftModalLoadError');
        if (existing) {
            return existing;
        }

        existing = document.createElement('div');
        existing.id = 'ftModalLoadError';
        existing.className = 'ft-modal-load-error';
        existing.setAttribute('role', 'alertdialog');
        existing.setAttribute('aria-modal', 'true');
        existing.setAttribute('aria-labelledby', 'ftModalLoadErrorTitle');
        existing.hidden = true;
        existing.innerHTML =
            '<div class="ft-modal-load-error__panel">' +
            '<h2 id="ftModalLoadErrorTitle">Unable to open</h2>' +
            '<p id="ftModalLoadErrorText"></p>' +
            '<button type="button" class="btn btn-primary" data-ft-modal-error-close>OK</button>' +
            '</div>';
        document.body.appendChild(existing);

        existing.querySelector('[data-ft-modal-error-close]').addEventListener('click', hideLoadError);
        existing.addEventListener('click', function (event) {
            if (event.target === existing) {
                hideLoadError();
            }
        });

        return existing;
    }

    function showLoadError(message) {
        var panel = ensureErrorUi();
        var text = document.getElementById('ftModalLoadErrorText');
        if (text) {
            text.textContent = message ||
                'The form could not be loaded. Please refresh the page and try again.';
        }
        panel.hidden = false;
        setShellModalOpen(true);
        closeCustomOverlays();
    }

    function hideLoadError() {
        var panel = document.getElementById('ftModalLoadError');
        if (panel) {
            panel.hidden = true;
        }
        if (!document.querySelector('.modal.show')) {
            setShellModalOpen(false);
        }
    }

    function onModalTriggerCapture(event) {
        var trigger = event.target.closest('[data-bs-toggle="modal"]');
        if (!trigger) {
            return;
        }

        var selector = trigger.getAttribute('data-bs-target');
        if (!selector) {
            return;
        }

        var modal = document.querySelector(selector);
        if (!modal) {
            event.preventDefault();
            event.stopImmediatePropagation();
            showLoadError('The application form is not available right now. Please refresh the page or open the opportunity again.');
            return;
        }

        portalNode(modal);
        closeCustomOverlays();
    }

    document.addEventListener('click', onModalTriggerCapture, true);

    document.addEventListener('show.bs.modal', function (event) {
        portalNode(event.target);
        hideLoadError();
        closeCustomOverlays();
        setShellModalOpen(true);
    });

    document.addEventListener('hidden.bs.modal', function () {
        cleanupDuplicateBackdrops();
        if (!document.querySelector('.modal.show')) {
            setShellModalOpen(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }
        var error = document.getElementById('ftModalLoadError');
        if (error && !error.hidden) {
            hideLoadError();
        }
    });

    if (global.MutationObserver) {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) {
                        return;
                    }
                    PORTAL_SELECTORS.forEach(function (selector) {
                        if (node.matches && node.matches(selector)) {
                            portalNode(node);
                        }
                    });
                    if (node.querySelectorAll) {
                        PORTAL_SELECTORS.forEach(function (selector) {
                            node.querySelectorAll(selector).forEach(portalNode);
                        });
                    }
                });
            });
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }

    function init() {
        portalExisting();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    global.ftModalStack = {
        portal: portalNode,
        showError: showLoadError,
        hideError: hideLoadError,
    };
})(typeof window !== 'undefined' ? window : this);
