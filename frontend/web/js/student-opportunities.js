/**
 * Student opportunities marketplace
 */
(function () {
    'use strict';

    var SEARCH_HISTORY_KEY = 'sp_om_search_history';
    var SAVED_KEY = 'ftp_saved_positions';

    function getSavedIds() {
        try {
            return JSON.parse(localStorage.getItem(SAVED_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    function getCards() {
        return Array.prototype.slice.call(document.querySelectorAll('.sp-om-card'));
    }

    function getState() {
        return window.__spOmFilterState || {
            quick: 'all',
            workMode: 'all',
            matchMin: 0,
            category: 'all',
            deadlineMax: 999,
            search: '',
            savedOnly: false,
        };
    }

    function setState(partial) {
        var s = getState();
        Object.keys(partial).forEach(function (k) {
            s[k] = partial[k];
        });
        window.__spOmFilterState = s;
        return s;
    }

    function cardVisible(card, state) {
        var tags = (card.getAttribute('data-tags') || '').toLowerCase();
        var field = (card.getAttribute('data-field') || '').toLowerCase();
        var search = (card.getAttribute('data-search') || '').toLowerCase();
        var workMode = card.getAttribute('data-work-mode') || '';
        var match = parseInt(card.getAttribute('data-match'), 10) || 0;
        var category = (card.getAttribute('data-category') || '').toLowerCase();
        var days = parseInt(card.getAttribute('data-deadline-days'), 10) || 999;
        var id = parseInt(card.getAttribute('data-position-id'), 10);

        if (state.savedOnly && getSavedIds().indexOf(id) < 0) return false;
        if (state.search && search.indexOf(state.search) < 0) return false;

        if (state.quick !== 'all') {
            if (state.quick === 'recommended' && match < 75) return false;
            if (state.quick === 'closing' && days > 7) return false;
            if (['remote', 'hybrid', 'on-site'].indexOf(state.quick) >= 0 && workMode !== state.quick) return false;
            if (state.quick !== 'recommended' && state.quick !== 'closing' &&
                ['remote', 'hybrid', 'on-site'].indexOf(state.quick) < 0 &&
                tags.indexOf(state.quick) < 0 && field.indexOf(state.quick) < 0) return false;
        }

        if (state.workMode !== 'all' && workMode !== state.workMode) return false;
        if (match < state.matchMin) return false;
        if (state.category !== 'all' && category !== state.category) return false;
        if (days > state.deadlineMax) return false;

        return true;
    }

    function applyFilters() {
        var state = getState();
        var cards = getCards();
        var visible = 0;

        cards.forEach(function (card, idx) {
            var show = cardVisible(card, state);
            card.hidden = !show;
            if (show) {
                visible++;
                card.style.animationDelay = (idx % 12) * 30 + 'ms';
                card.classList.add('sp-om-enter');
            }
        });

        var countEl = document.getElementById('spOmResultsCount');
        if (countEl) countEl.textContent = visible + ' role' + (visible === 1 ? '' : 's');

        var emptyFilter = document.getElementById('spOmEmptyFilter');
        var cardsWrap = document.getElementById('spOmCards');
        if (emptyFilter && cardsWrap) {
            emptyFilter.hidden = visible > 0 || cards.length === 0;
        }

        updateSavedCount();
    }

    function updateSavedCount() {
        var el = document.getElementById('spOmSavedCount');
        if (el) el.textContent = String(getSavedIds().length);
    }

    function initQuickChips() {
        var wrap = document.getElementById('spOmQuickChips');
        if (!wrap) return;

        wrap.querySelectorAll('.sp-om-chip[data-filter]').forEach(function (chip) {
            chip.addEventListener('click', function () {
                wrap.querySelectorAll('.sp-om-chip[data-filter]').forEach(function (c) {
                    c.classList.remove('is-active');
                });
                chip.classList.add('is-active');
                setState({ quick: chip.getAttribute('data-filter'), savedOnly: false });
                applyFilters();
            });
        });

        var savedChip = wrap.querySelector('[data-om-saved-only]');
        if (savedChip) {
            savedChip.addEventListener('click', function () {
                wrap.querySelectorAll('.sp-om-chip').forEach(function (c) {
                    c.classList.remove('is-active');
                });
                savedChip.classList.add('is-active');
                setState({ savedOnly: true, quick: 'all' });
                applyFilters();
            });
        }
    }

    function initSidebarFilters() {
        document.querySelectorAll('.sp-om-filter-chips').forEach(function (group) {
            var attr = group.getAttribute('data-filter-attr');
            group.querySelectorAll('.sp-om-chip').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    group.querySelectorAll('.sp-om-chip').forEach(function (c) {
                        c.classList.remove('is-active');
                    });
                    chip.classList.add('is-active');
                    var val = chip.getAttribute('data-filter');
                    if (attr === 'data-work-mode') setState({ workMode: val });
                    if (attr === 'data-match-min') setState({ matchMin: parseInt(val, 10) || 0 });
                    if (attr === 'data-category') setState({ category: val });
                    if (attr === 'data-deadline-max') setState({ deadlineMax: parseInt(val, 10) || 999 });
                    applyFilters();
                });
            });
        });
    }

    function clearAllFilters() {
        setState({
            quick: 'all',
            workMode: 'all',
            matchMin: 0,
            category: 'all',
            deadlineMax: 999,
            search: '',
            savedOnly: false,
        });
        var input = document.getElementById('spOmSearchInput');
        if (input) input.value = '';
        document.querySelectorAll('.sp-om-chip').forEach(function (c) {
            var isAll = c.getAttribute('data-filter') === 'all' || c.getAttribute('data-filter') === '0' ||
                c.getAttribute('data-filter') === '999';
            c.classList.toggle('is-active', isAll);
        });
        var quick = document.getElementById('spOmQuickChips');
        if (quick) {
            quick.querySelectorAll('.sp-om-chip[data-filter]').forEach(function (c) {
                c.classList.toggle('is-active', c.getAttribute('data-filter') === 'all');
            });
        }
        applyFilters();
    }

    function initSearch() {
        var input = document.getElementById('spOmSearchInput');
        var box = document.getElementById('spOmSearchBox');
        var suggest = document.getElementById('spOmSuggest');
        if (!input) return;

        function getHistory() {
            try {
                return JSON.parse(localStorage.getItem(SEARCH_HISTORY_KEY) || '[]');
            } catch (e) {
                return [];
            }
        }

        function pushHistory(q) {
            if (!q) return;
            var h = getHistory().filter(function (x) { return x !== q; });
            h.unshift(q);
            localStorage.setItem(SEARCH_HISTORY_KEY, JSON.stringify(h.slice(0, 8)));
        }

        function renderSuggest(items) {
            if (!suggest) return;
            if (!items.length) {
                suggest.hidden = true;
                return;
            }
            suggest.innerHTML = items.map(function (item) {
                return '<button type="button" data-suggest="' + item.replace(/"/g, '&quot;') + '">' + item + '</button>';
            }).join('');
            suggest.hidden = false;
            suggest.querySelectorAll('button').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    input.value = btn.getAttribute('data-suggest');
                    suggest.hidden = true;
                    setState({ search: input.value.toLowerCase().trim() });
                    applyFilters();
                });
            });
        }

        var debounce;
        input.addEventListener('input', function () {
            clearTimeout(debounce);
            debounce = setTimeout(function () {
                var q = input.value.toLowerCase().trim();
                setState({ search: q });
                applyFilters();
                if (!q) {
                    renderSuggest(getHistory().slice(0, 5));
                    return;
                }
                var titles = getCards().map(function (c) {
                    return c.getAttribute('data-title');
                }).filter(Boolean);
                var uniq = [];
                titles.forEach(function (t) {
                    if (t.toLowerCase().indexOf(q) >= 0 && uniq.indexOf(t) < 0) uniq.push(t);
                });
                renderSuggest(uniq.slice(0, 6));
            }, 180);
        });

        input.addEventListener('focus', function () {
            if (box) box.classList.add('is-focused');
            if (!input.value.trim()) renderSuggest(getHistory().slice(0, 5));
        });

        input.addEventListener('blur', function () {
            setTimeout(function () {
                if (box) box.classList.remove('is-focused');
                if (suggest) suggest.hidden = true;
            }, 200);
        });

        var form = document.getElementById('spOmSearchForm');
        if (form) {
            form.addEventListener('submit', function () {
                pushHistory(input.value.trim());
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === '/' && document.activeElement !== input) {
                var tag = document.activeElement && document.activeElement.tagName;
                if (tag === 'INPUT' || tag === 'TEXTAREA') return;
                e.preventDefault();
                input.focus();
            }
        });
    }

    function initSort() {
        var select = document.getElementById('spOmSort');
        var container = document.getElementById('spOmCards');
        if (!select || !container) return;

        select.addEventListener('change', function () {
            var cards = getCards();
            cards.sort(function (a, b) {
                if (select.value === 'match') {
                    return (parseInt(b.getAttribute('data-match'), 10) || 0) -
                        (parseInt(a.getAttribute('data-match'), 10) || 0);
                }
                if (select.value === 'deadline') {
                    return (parseInt(a.getAttribute('data-deadline-days'), 10) || 999) -
                        (parseInt(b.getAttribute('data-deadline-days'), 10) || 999);
                }
                return (parseInt(b.getAttribute('data-position-id'), 10) || 0) -
                    (parseInt(a.getAttribute('data-position-id'), 10) || 0);
            });
            cards.forEach(function (c) { container.appendChild(c); });
        });
    }

    function initViewToggle() {
        var wrap = document.querySelector('[data-sp-opp-view="spOmFeed"]');
        var feed = document.getElementById('spOmFeed');
        if (!wrap || !feed) return;

        wrap.querySelectorAll('button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                wrap.querySelectorAll('button').forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
                var view = btn.getAttribute('data-view');
                feed.classList.remove('sp-om-view--grid', 'sp-om-view--list');
                feed.classList.add('sp-om-view--' + view);
            });
        });
    }

    function initMobileFilters() {
        var toggle = document.getElementById('spOmFilterToggle');
        var panel = document.getElementById('spOmFiltersPanel');
        var backdrop = document.getElementById('spOmFilterBackdrop');
        if (!toggle || !panel) return;

        function close() {
            panel.classList.remove('is-open');
            if (backdrop) backdrop.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }

        function open() {
            panel.classList.add('is-open');
            if (backdrop) backdrop.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        }

        toggle.addEventListener('click', function () {
            panel.classList.contains('is-open') ? close() : open();
        });
        if (backdrop) backdrop.addEventListener('click', close);
    }

    function initCarousels() {
        document.querySelectorAll('[data-carousel]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-carousel');
                var el = document.getElementById(id);
                if (!el) return;
                var dir = parseInt(btn.getAttribute('data-dir'), 10) || 1;
                el.scrollBy({ left: dir * 220, behavior: 'smooth' });
            });
        });
    }

    function initQuickView() {
        document.querySelectorAll('[data-om-quick-view]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var card = btn.closest('.sp-om-card');
                if (!card) return;
                var id = card.getAttribute('data-position-id');
                document.getElementById('spOmQuickTitle').textContent = card.getAttribute('data-title') || '';
                document.getElementById('spOmQuickOrg').textContent = card.getAttribute('data-org') || '';
                var quickLogo = document.getElementById('spOmQuickLogo');
                var cardLogo = card.querySelector('.sp-om-logo');
                if (quickLogo && cardLogo) {
                    quickLogo.innerHTML = cardLogo.innerHTML;
                }
                document.getElementById('spOmQuickDesc').textContent = card.getAttribute('data-desc') || '';
                document.getElementById('spOmQuickInsight').innerHTML =
                    '<i class="fas fa-wand-magic-sparkles"></i> ' + (card.getAttribute('data-insight') || '');
                var link = document.getElementById('spOmQuickApply');
                if (link) link.href = card.getAttribute('data-view-url') || '#';
                var modal = document.getElementById('spOmQuickModal');
                if (modal && typeof bootstrap !== 'undefined') {
                    new bootstrap.Modal(modal).show();
                }
            });
        });
    }

    function initShare() {
        document.querySelectorAll('[data-om-share]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-om-share');
                if (navigator.share) {
                    navigator.share({ title: 'Internship opportunity', url: url }).catch(function () {});
                } else if (navigator.clipboard) {
                    navigator.clipboard.writeText(url);
                    if (window.ftpShowToast) window.ftpShowToast('Link copied');
                }
            });
        });
    }

    function initSkillTags() {
        document.querySelectorAll('.sp-om-skill[data-skill-filter]').forEach(function (tag) {
            tag.addEventListener('click', function (e) {
                e.stopPropagation();
                var skill = tag.getAttribute('data-skill-filter');
                var input = document.getElementById('spOmSearchInput');
                if (input) {
                    input.value = skill;
                    setState({ search: skill });
                    applyFilters();
                }
            });
        });
    }

    function initEligibilityModal() {
        document.querySelectorAll('[data-eligibility-check]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var msg = btn.getAttribute('data-eligibility-msg') || 'Not eligible';
                var el = document.getElementById('spEligibilityModalMessage');
                if (el) el.textContent = msg;
                var modal = document.getElementById('spEligibilityModal');
                if (modal && typeof bootstrap !== 'undefined') {
                    new bootstrap.Modal(modal).show();
                }
            });
        });
    }

    function hideSkeleton() {
        var sk = document.getElementById('spOmSkeleton');
        if (sk) {
            setTimeout(function () {
                sk.classList.add('is-hidden');
            }, 400);
        }
    }

    function initClearButtons() {
        ['spOmClearFilters', 'spOmFilterReset', 'spOmEmptyReset'].forEach(function (id) {
            var btn = document.getElementById(id);
            if (btn) btn.addEventListener('click', clearAllFilters);
        });
    }

    function syncSavedBookmarks() {
        var root = document.getElementById('spOpportunitiesMarketplace');
        if (!root) return;
        var syncUrl = root.getAttribute('data-bookmark-sync-url');
        if (!syncUrl || typeof fetch === 'undefined') return;

        fetch(syncUrl, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || !data.success || !Array.isArray(data.ids)) return;
                localStorage.setItem(SAVED_KEY, JSON.stringify(data.ids));
                updateSavedCount();
                applyFilters();
            })
            .catch(function () { /* keep local cache on failure */ });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!document.getElementById('spOpportunitiesMarketplace')) return;

        syncSavedBookmarks();
        window.__spOmFilterState = getState();
        initQuickChips();
        initSidebarFilters();
        initSearch();
        initSort();
        initViewToggle();
        initMobileFilters();
        initCarousels();
        initQuickView();
        initShare();
        initSkillTags();
        initEligibilityModal();
        initClearButtons();
        hideSkeleton();
        applyFilters();

        document.addEventListener('click', function () {
            updateSavedCount();
        });
    });
})();
