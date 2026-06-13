/**
 * Student Applications — premium career workspace
 */
(function () {
    'use strict';

    function qsa(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function bindRipple(btn) {
        btn.addEventListener('pointermove', function (e) {
            var r = btn.getBoundingClientRect();
            btn.style.setProperty('--x', ((e.clientX - r.left) / r.width) * 100 + '%');
            btn.style.setProperty('--y', ((e.clientY - r.top) / r.height) * 100 + '%');
        });
    }

    function bindMagnetic(btn) {
        btn.addEventListener('mousemove', function (e) {
            var r = btn.getBoundingClientRect();
            var x = (e.clientX - r.left - r.width / 2) * 0.12;
            var y = (e.clientY - r.top - r.height / 2) * 0.12;
            btn.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
        });
        btn.addEventListener('mouseleave', function () {
            btn.style.transform = '';
        });
    }

    function animateCounter(el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        var duration = 900;
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
        qsa('.spa [data-count]').forEach(function (el) {
            if (el.__spaCounted) return;
            el.__spaCounted = true;
            animateCounter(el);
        });
    }

    function initReveals() {
        var root = document.getElementById('spaApplications');
        if (!root) return;

        if (!('IntersectionObserver' in window)) {
            qsa('.spa-reveal', root).forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -40px 0px', threshold: 0.08 });

        qsa('.spa-reveal', root).forEach(function (el) { io.observe(el); });
    }

    function initPageLoad() {
        var root = document.getElementById('spaApplications');
        var skeleton = document.getElementById('spaSkeleton');
        var cards = document.getElementById('spaCards');
        if (!root) return;

        requestAnimationFrame(function () {
            root.classList.add('is-ready');
            setTimeout(function () {
                root.setAttribute('data-spa-ready', '1');
                if (cards) cards.hidden = false;
                if (skeleton) skeleton.setAttribute('aria-hidden', 'true');
                staggerCards();
            }, 480);
        });
    }

    function staggerCards() {
        qsa('[data-spa-card]').forEach(function (card, i) {
            card.style.animationDelay = (i * 55) + 'ms';
            card.classList.add('spa-card--in');
        });
    }

    function initPipeline() {
        var track = document.getElementById('spaPipeline');
        var fill = document.getElementById('spaPipelineFill');
        var nodes = qsa('.spa-pipeline-node[data-spa-journey-filter]');
        if (!nodes.length) return;

        var maxCount = 0;
        var dominant = 0;
        nodes.forEach(function (node, i) {
            var count = parseInt((node.querySelector('.spa-pipeline-count') || {}).textContent, 10) || 0;
            if (count > maxCount) {
                maxCount = count;
                dominant = i;
            }
        });

        var progressPct = nodes.length > 1 ? (dominant / (nodes.length - 1)) * 100 : 0;
        if (fill) fill.style.width = progressPct + '%';

        nodes.forEach(function (node, i) {
            if (i <= dominant) node.classList.add('is-done');
            if (i === dominant && maxCount > 0) node.classList.add('is-lit');
        });

        nodes.forEach(function (node) {
            node.addEventListener('click', function () {
                var key = node.getAttribute('data-spa-journey-filter');
                nodes.forEach(function (n) {
                    n.classList.toggle('is-active', n === node);
                });
                var tabs = qsa('.spa-tab[data-spa-filter]');
                tabs.forEach(function (t) { t.classList.remove('is-active'); });
                if (key === 'applied') {
                    setTabActive('pending');
                } else if (key === 'review') {
                    setTabActive('review');
                } else if (key === 'shortlisted' || key === 'interview') {
                    setTabActive('interview');
                } else if (key === 'accepted') {
                    setTabActive('approved');
                } else {
                    applyJourneyFilter(key);
                }
                if (window.__spaApplyFilters) window.__spaApplyFilters();
            });
        });

        function setTabActive(filter) {
            qsa('.spa-tab[data-spa-filter]').forEach(function (t) {
                t.classList.toggle('is-active', t.getAttribute('data-spa-filter') === filter);
            });
        }

        function applyJourneyFilter(key) {
            window.__spaJourneyOverride = key;
        }

        if (track && 'IntersectionObserver' in window && fill) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        fill.style.width = progressPct + '%';
                    }
                });
            }, { threshold: 0.3 });
            io.observe(track);
        }
    }

    function initFiltering() {
        var tabs = qsa('.spa-tab[data-spa-filter]');
        var search = document.getElementById('spaSearch');
        var sort = document.getElementById('spaSort');
        var org = document.getElementById('spaOrgFilter');
        var empty = document.getElementById('spaEmptyFilter');
        var clearBtn = document.getElementById('spaClearFilters');

        function activeFilterKey() {
            var active = document.querySelector('.spa-tab.is-active[data-spa-filter]');
            return active ? active.getAttribute('data-spa-filter') : 'all';
        }

        function matchStatus(card, key) {
            if (key === 'all') return true;
            var s = card.getAttribute('data-app-status') || '';
            if (key === 'interview') {
                return s === 'org_approved' || s === 'university_approved' || card.getAttribute('data-journey') === 'shortlisted' || card.getAttribute('data-journey') === 'interview';
            }
            return s === key;
        }

        function apply() {
            var key = activeFilterKey();
            var journey = window.__spaJourneyOverride;
            var q = (search && search.value || '').toLowerCase().trim();
            var orgVal = (org && org.value || '').toLowerCase().trim();
            var cards = qsa('[data-spa-card]');
            var visible = 0;

            cards.forEach(function (card) {
                var ok = matchStatus(card, key);
                if (ok && journey) {
                    ok = (card.getAttribute('data-journey') || '') === journey;
                }
                if (ok && q) ok = (card.getAttribute('data-search-text') || '').includes(q);
                if (ok && orgVal) ok = (card.getAttribute('data-org') || '').includes(orgVal);
                card.hidden = !ok;
                if (ok) visible++;
            });

            if (empty) empty.hidden = visible > 0 || !cards.length;
        }

        window.__spaApplyFilters = apply;

        tabs.forEach(function (t) {
            t.addEventListener('click', function () {
                window.__spaJourneyOverride = null;
                qsa('.spa-pipeline-node').forEach(function (n) { n.classList.remove('is-active'); });
                tabs.forEach(function (x) { x.classList.remove('is-active'); });
                t.classList.add('is-active');
                apply();
            });
        });

        if (search) search.addEventListener('input', apply);
        if (org) org.addEventListener('change', apply);

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (search) search.value = '';
                if (org) org.value = '';
                window.__spaJourneyOverride = null;
                tabs.forEach(function (x) {
                    x.classList.toggle('is-active', x.getAttribute('data-spa-filter') === 'all');
                });
                apply();
            });
        }

        if (sort) {
            sort.addEventListener('change', function () {
                var container = document.getElementById('spaCards');
                if (!container) return;
                var cards = qsa('[data-spa-card]', container);
                var mode = sort.value;
                cards.sort(function (a, b) {
                    if (mode === 'oldest') {
                        return (parseInt(a.getAttribute('data-applied-ts'), 10) || 0) - (parseInt(b.getAttribute('data-applied-ts'), 10) || 0);
                    }
                    if (mode === 'org') {
                        return (a.getAttribute('data-org') || '').localeCompare(b.getAttribute('data-org') || '');
                    }
                    return (parseInt(b.getAttribute('data-applied-ts'), 10) || 0) - (parseInt(a.getAttribute('data-applied-ts'), 10) || 0);
                });
                cards.forEach(function (c) { container.appendChild(c); });
            });
        }

        apply();
    }

    function initDrawer() {
        var drawer = document.getElementById('spaDrawer');
        var backdrop = document.getElementById('spaDrawerBackdrop');
        var closeBtn = document.getElementById('spaDrawerClose');
        if (!drawer || !backdrop) return;

        function close() {
            drawer.classList.remove('is-open');
            drawer.setAttribute('aria-hidden', 'true');
            backdrop.hidden = true;
        }

        function openWith(card) {
            var json = card.getAttribute('data-app-json') || '{}';
            var data;
            try { data = JSON.parse(json); } catch (e) { data = {}; }

            var set = function (id, text) {
                var el = document.getElementById(id);
                if (el) el.textContent = text;
            };

            set('spaDrawerTitle', data.title || 'Application');
            set('spaDrawerOrg', data.org || '');
            set('spaDrawerMatch', data.match ? (data.match + '%') : '—');
            set('spaDrawerApplied', data.applied || '—');
            set('spaDrawerLocation', data.location || '—');
            set('spaDrawerUpdated', data.updated || '—');
            set('spaDrawerFeedback', data.feedback || '');
            set('spaDrawerCover', data.cover || '');
            set('spaDrawerResume', data.resume || '');

            var status = document.getElementById('spaDrawerStatus');
            if (status) {
                status.className = 'spa-status spa-status--' + (data.statusKey || 'pending');
                status.innerHTML = '<span class="spa-status-dot"></span>' + (data.status || '');
            }

            var view = document.getElementById('spaDrawerView');
            if (view) view.href = data.viewUrl || '#';

            var withdraw = document.getElementById('spaDrawerWithdraw');
            if (withdraw) {
                if (data.withdrawUrl) {
                    withdraw.hidden = false;
                    withdraw.href = data.withdrawUrl;
                    withdraw.setAttribute('data-method', 'post');
                    withdraw.setAttribute('data-confirm', 'Withdraw this application?');
                } else {
                    withdraw.hidden = true;
                }
            }

            var timeline = document.getElementById('spaDrawerTimeline');
            if (timeline) {
                var steps = ['Applied', 'Under Review', 'Shortlisted', 'Interview', 'Accepted'];
                var keys = ['applied', 'review', 'shortlisted', 'interview', 'accepted'];
                var journey = card.getAttribute('data-journey') || 'applied';
                var idx = keys.indexOf(journey);
                if (idx < 0) idx = 0;
                timeline.innerHTML = steps.map(function (s, i) {
                    var cls = '';
                    if (i < idx) cls = 'is-done';
                    else if (i === idx) cls = 'is-active';
                    return '<li class="' + cls + '">' + s + '</li>';
                }).join('');
            }

            drawer.classList.add('is-open');
            drawer.setAttribute('aria-hidden', 'false');
            backdrop.hidden = false;
        }

        qsa('[data-spa-open]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var card = btn.closest('[data-spa-card]');
                if (card) openWith(card);
            });
        });

        backdrop.addEventListener('click', close);
        if (closeBtn) closeBtn.addEventListener('click', close);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') close();
        });
    }

    function initExport() {
        var btn = document.getElementById('spaExportBtn');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var rows = window.spAtExportData || [];
            if (!rows.length) {
                if (typeof window.ftpShowToast === 'function') {
                    window.ftpShowToast('No applications to export.', 'info');
                }
                return;
            }
            var csv = 'Position,Organization,Status,Applied\n';
            rows.forEach(function (r) {
                csv += r.map(function (c) {
                    return '"' + String(c).replace(/"/g, '""') + '"';
                }).join(',') + '\n';
            });
            var blob = new Blob([csv], { type: 'text/csv' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'my-applications.csv';
            a.click();
            URL.revokeObjectURL(a.href);
        });
    }

    function initButtons() {
        qsa('.spa-btn').forEach(function (btn) {
            bindRipple(btn);
            if (btn.classList.contains('spa-btn--primary')) bindMagnetic(btn);
        });
    }

    function init() {
        initPageLoad();
        initCounters();
        initReveals();
        initPipeline();
        initFiltering();
        initDrawer();
        initExport();
        initButtons();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
