/**
 * Field Training Platform — Student module interactions
 */
(function () {
    'use strict';

    /* ── Animated counters ── */
    function animateCounter(el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        var duration = 1200;
        var start = performance.now();

        function tick(now) {
            var p = Math.min((now - start) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = String(Math.floor(target * eased));
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }

    function initCounters() {
        document.querySelectorAll('[data-count]').forEach(function (el) {
            if (el.__ftpCounted) return;
            el.__ftpCounted = true;
            animateCounter(el);
        });
    }

    /* ── Filter chips (client-side) ── */
    function initFilterChips(containerSelector, itemSelector, attr) {
        var container = document.querySelector(containerSelector);
        if (!container) return;

        container.querySelectorAll('[data-filter]').forEach(function (chip) {
            chip.addEventListener('click', function () {
                container.querySelectorAll('[data-filter]').forEach(function (c) {
                    c.classList.remove('is-active');
                });
                chip.classList.add('is-active');

                var filter = chip.getAttribute('data-filter');
                document.querySelectorAll(itemSelector).forEach(function (item) {
                    if (filter === 'all') {
                        item.hidden = false;
                        return;
                    }
                    var val = (item.getAttribute(attr) || '').toLowerCase();
                    item.hidden = !val.includes(filter);
                });
            });
        });
    }

    /* ── Application status tabs ── */
    function initAppFilters() {
        var tabs = document.querySelectorAll('[data-app-filter]');
        var cards = document.querySelectorAll('[data-app-status]');
        if (!tabs.length) return;

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (t) { t.classList.remove('is-active'); });
                tab.classList.add('is-active');
                var status = tab.getAttribute('data-app-filter');
                cards.forEach(function (card) {
                    if (status === 'all') {
                        card.hidden = false;
                    } else {
                        card.hidden = card.getAttribute('data-app-status') !== status;
                    }
                });
                // subtle reflow-free “filter applied” feel
                window.requestAnimationFrame(function () {
                    document.querySelectorAll('.sp-app-card:not([hidden])').forEach(function (c, idx) {
                        c.classList.remove('sp-enter');
                        // restart animation
                        void c.offsetWidth;
                        c.style.animationDelay = (idx * 30) + 'ms';
                        c.classList.add('sp-enter');
                    });
                });
            });
        });
    }

    /* ── Expandable application cards ── */
    function initExpandableCards() {
        document.querySelectorAll('[data-expand-card]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var card = btn.closest('.sp-app-card');
                if (card) card.classList.toggle('is-expanded');
            });
        });
    }

    /* ── Messages: conversation selection ── */
    function initMessagesHub() {
        var hub = document.querySelector('.sp-messages-hub:not(.org-messages-hub):not([data-messaging-hub])');
        if (!hub) return;

        var items = hub.querySelectorAll('[data-conversation-id]');
        var empty = hub.querySelector('.sp-chat-empty');
        var thread = hub.querySelector('.sp-chat-thread');
        var detail = hub.querySelector('.sp-chat-detail');

        function selectItem(item) {
            items.forEach(function (i) { i.classList.remove('is-active'); });
            item.classList.add('is-active');
            items.forEach(function (i) { i.setAttribute('aria-selected', 'false'); });
            item.setAttribute('aria-selected', 'true');
            if (empty) empty.hidden = true;
            if (thread) thread.hidden = false;
            if (detail) detail.hidden = false;

            var title = item.querySelector('.sp-conv-title')?.textContent || '';
            var msg = item.querySelector('.sp-conv-preview')?.textContent || '';
            var type = item.getAttribute('data-sender-type') || 'system';
            var time = item.querySelector('.sp-conv-time')?.textContent || '';
            var actionUrl = item.getAttribute('data-action-url') || '';
            var actionText = item.getAttribute('data-action-text') || 'View Details';
            var notifId = item.getAttribute('data-conversation-id');

            var threadTitle = hub.querySelector('.sp-thread-title');
            var threadBody = hub.querySelector('.sp-thread-body');
            var detailTitle = hub.querySelector('.sp-detail-title');
            var detailType = hub.querySelector('.sp-detail-type');
            var detailTime = hub.querySelector('.sp-detail-time');
            var detailAction = hub.querySelector('.sp-detail-action');

            if (threadTitle) threadTitle.textContent = title;
            if (threadBody) {
                threadBody.innerHTML = '<div class="sp-bubble sp-bubble--in"><p>' + escapeHtml(msg) + '</p><span class="sp-bubble-time">' + escapeHtml(time) + '</span></div>';
            }
            if (detailTitle) detailTitle.textContent = title;
            if (detailType) detailType.textContent = type.charAt(0).toUpperCase() + type.slice(1);
            if (detailTime) detailTime.textContent = time;
            if (detailAction) {
                if (actionUrl) {
                    detailAction.href = actionUrl;
                    detailAction.textContent = actionText;
                    detailAction.hidden = false;
                } else {
                    detailAction.hidden = true;
                }
            }

            hub.setAttribute('data-active-id', notifId);
        }

        items.forEach(function (item) {
            item.addEventListener('click', function () { selectItem(item); });
            item.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    selectItem(item);
                }
            });
        });

        var first = hub.querySelector('[data-conversation-id].is-active') || items[0];
        if (first) selectItem(first);

        /* Conv filter tabs */
        hub.querySelectorAll('[data-conv-filter]').forEach(function (tab) {
            tab.addEventListener('click', function () {
                hub.querySelectorAll('[data-conv-filter]').forEach(function (t) {
                    t.classList.remove('is-active');
                });
                tab.classList.add('is-active');
                var f = tab.getAttribute('data-conv-filter');
                items.forEach(function (item) {
                    if (f === 'all') {
                        item.hidden = false;
                    } else {
                        item.hidden = item.getAttribute('data-sender-type') !== f;
                    }
                });
            });
        });

        /* Search conversations */
        var search = hub.querySelector('.sp-conv-search');
        if (search) {
            search.addEventListener('input', function () {
                var q = search.value.toLowerCase();
                items.forEach(function (item) {
                    var text = item.textContent.toLowerCase();
                    item.hidden = q !== '' && !text.includes(q);
                });
            });
        }
    }

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    /* ── Sticky filter shadow ── */
    function initStickyShadow() {
        var sticky = document.querySelector('.sp-sticky-bar');
        if (!sticky) return;
        var observer = new IntersectionObserver(function (entries) {
            sticky.classList.toggle('is-stuck', !entries[0].isIntersecting);
        }, { threshold: 1, rootMargin: '-1px 0px 0px 0px' });
        var sentinel = document.querySelector('.sp-sticky-sentinel');
        if (sentinel) observer.observe(sentinel);
    }

    /* ── Skeleton demo (remove on load) ── */
    function hideSkeletons() {
        document.querySelectorAll('.sp-skeleton-wrap').forEach(function (el) {
            window.setTimeout(function () {
                el.classList.add('is-loaded');
            }, 320);
        });
    }

    /* ── Notifications feed filters & search ── */
    function initNotifFeed() {
        var feed = document.querySelector('.sp-notif-feed');
        if (!feed) return;

        var cards = feed.querySelectorAll('.sp-notif-card');
        var chips = document.querySelectorAll('[data-notif-filter]');
        var search = document.querySelector('.sp-notif-search');

        function applyFilters() {
            var activeChip = document.querySelector('[data-notif-filter].is-active');
            var cat = activeChip ? activeChip.getAttribute('data-notif-filter') : 'all';
            var q = search ? search.value.toLowerCase().trim() : '';

            cards.forEach(function (card) {
                var matchCat = cat === 'all' || card.getAttribute('data-notif-category') === cat;
                var matchSearch = !q || (card.getAttribute('data-search-text') || '').includes(q);
                card.hidden = !(matchCat && matchSearch);
            });

            document.querySelectorAll('.sp-notif-group').forEach(function (group) {
                var visible = group.querySelectorAll('.sp-notif-card:not([hidden])').length;
                group.hidden = visible === 0;
            });
        }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                chips.forEach(function (c) { c.classList.remove('is-active'); });
                chip.classList.add('is-active');
                applyFilters();
            });
        });

        if (search) {
            search.addEventListener('input', applyFilters);
        }
    }

    /* ── Settings nav scroll spy ── */
    function initSettingsNav() {
        var nav = document.querySelector('.sp-settings-nav');
        if (!nav) return;

        nav.querySelectorAll('.sp-settings-nav-link').forEach(function (link) {
            link.addEventListener('click', function (e) {
                var href = link.getAttribute('href');
                if (!href || href.charAt(0) !== '#') return;
                e.preventDefault();
                var target = document.querySelector(href);
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                nav.querySelectorAll('.sp-settings-nav-link').forEach(function (l) {
                    l.classList.remove('is-active');
                });
                link.classList.add('is-active');
            });
        });
    }

    /* ── Help center FAQ & search ── */
    function initHelpCenter() {
        var help = document.querySelector('.sp-help');
        if (!help) return;

        help.querySelectorAll('.sp-faq-question').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('.sp-faq-item');
                var isOpen = item.classList.contains('is-open');
                help.querySelectorAll('.sp-faq-item').forEach(function (i) {
                    i.classList.remove('is-open');
                    i.querySelector('.sp-faq-question').setAttribute('aria-expanded', 'false');
                });
                if (!isOpen) {
                    item.classList.add('is-open');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });

        var search = document.getElementById('spHelpSearch');
        var faqItems = help.querySelectorAll('.sp-faq-item');
        var noResults = document.getElementById('spHelpNoResults');
        var catChips = help.querySelectorAll('[data-help-cat]');

        function filterHelp() {
            var activeChip = help.querySelector('.sp-chip.is-active[data-help-cat]');
            var cat = activeChip ? activeChip.getAttribute('data-help-cat') : 'all';
            var q = search ? search.value.toLowerCase().trim() : '';
            var visible = 0;

            faqItems.forEach(function (item) {
                var itemCat = item.getAttribute('data-help-cat');
                var text = item.getAttribute('data-search-text') || '';
                var match = (cat === 'all' || itemCat === cat) && (!q || text.includes(q));
                item.hidden = !match;
                if (match) visible++;
            });

            help.querySelectorAll('.sp-help-card').forEach(function (card) {
                if (!card.hasAttribute('data-help-cat')) return;
                var match = cat === 'all' || card.getAttribute('data-help-cat') === cat;
                card.hidden = !match;
            });

            if (noResults) noResults.hidden = visible > 0;
        }

        if (search) search.addEventListener('input', filterHelp);
        catChips.forEach(function (chip) {
            if (chip.tagName !== 'BUTTON') return;
            chip.addEventListener('click', function () {
                help.querySelectorAll('.sp-chip[data-help-cat]').forEach(function (c) {
                    c.classList.remove('is-active');
                });
                chip.classList.add('is-active');
                filterHelp();
            });
        });
    }

    /* ── Accessibility toggles (client-side only) ── */
    function initAccessibilityToggles() {
        var reduce = document.getElementById('spReduceMotion');
        var contrast = document.getElementById('spHighContrast');

        if (reduce) {
            reduce.checked = localStorage.getItem('spReduceMotion') === '1';
            reduce.addEventListener('change', function () {
                document.body.classList.toggle('sp-reduce-motion', reduce.checked);
                localStorage.setItem('spReduceMotion', reduce.checked ? '1' : '0');
            });
            if (reduce.checked) document.body.classList.add('sp-reduce-motion');
        }

        if (contrast) {
            contrast.checked = localStorage.getItem('spHighContrast') === '1';
            contrast.addEventListener('change', function () {
                document.body.classList.toggle('sp-high-contrast', contrast.checked);
                localStorage.setItem('spHighContrast', contrast.checked ? '1' : '0');
            });
            if (contrast.checked) document.body.classList.add('sp-high-contrast');
        }
    }

    function initOppViewToggle() {
        document.querySelectorAll('[data-sp-opp-view]').forEach(function (wrap) {
            if (wrap.dataset.spOppBound) return;
            wrap.dataset.spOppBound = '1';
            var targetId = wrap.getAttribute('data-sp-opp-view');
            var target = document.getElementById(targetId);
            if (!target) return;

            wrap.querySelectorAll('button').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    wrap.querySelectorAll('button').forEach(function (b) { b.classList.remove('is-active'); });
                    btn.classList.add('is-active');
                    var view = btn.getAttribute('data-view');
                    target.classList.toggle('sp-view--grid', view === 'grid');
                    target.classList.toggle('sp-view--list', view === 'list');
                });
            });
        });
    }

    function initCvDropzone() {
        var zone = document.getElementById('spCvDropzone');
        var input = document.getElementById('spCvFileInput');
        var nameEl = document.getElementById('spCvFileName');
        if (!zone || !input) return;

        zone.addEventListener('click', function (e) {
            if (e.target === input) return;
            input.click();
        });

        ['dragenter', 'dragover'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) {
                e.preventDefault();
                zone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) {
                e.preventDefault();
                zone.classList.remove('is-dragover');
            });
        });

        zone.addEventListener('drop', function (e) {
            var files = e.dataTransfer && e.dataTransfer.files;
            if (files && files.length) {
                input.files = files;
                showFileName(files[0]);
            }
        });

        input.addEventListener('change', function () {
            if (input.files && input.files[0]) {
                showFileName(input.files[0]);
            }
        });

        function showFileName(file) {
            if (!nameEl) return;
            nameEl.textContent = 'Selected: ' + file.name;
            nameEl.hidden = false;
            if (window.ftpShowToast) {
                window.ftpShowToast('CV ready to upload — save your profile to confirm.');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initCounters();
        initFilterChips('.sp-filter-chips', '.sp-opp-card', 'data-field');
        initFilterChips('.sp-quick-chips', '.sp-opp-card', 'data-tags');
        initAppFilters();
        initExpandableCards();
        initMessagesHub();
        initStickyShadow();
        initNotifFeed();
        initSettingsNav();
        initHelpCenter();
        initAccessibilityToggles();
        initOppViewToggle();
        initCvDropzone();
        hideSkeletons();
    });
})();
