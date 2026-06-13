/**
 * Help Center — search, FAQ, AI assistant, live chat
 */
(function () {
    'use strict';

    var cfg = window.ftHelpCenter || {};
    var api = cfg.api || {};

    function $(sel, root) { return (root || document).querySelector(sel); }
    function $all(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

    function post(url, data) {
        var body = new URLSearchParams();
        Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
        var csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) body.append('_csrf-frontend', csrf.getAttribute('content'));
        return fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: body })
            .then(function (r) { return r.json(); });
    }

    function get(url) {
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); });
    }

    /* ── Search & topics ── */
    function initSearch() {
        var input = $('#hcSearch');
        if (!input) return;

        function filter() {
            var q = input.value.trim().toLowerCase();
            var visible = 0;
            $all('.hc-faq-item').forEach(function (item) {
                var text = item.getAttribute('data-hc-search') || '';
                var match = !q || text.indexOf(q) !== -1;
                item.classList.toggle('is-filtered-out', !match);
                if (match) visible++;
            });
            $all('.hc-topic-card').forEach(function (card) {
                var topic = card.getAttribute('data-hc-topic') || '';
                var match = !q || topic.indexOf(q) !== -1 || card.textContent.toLowerCase().indexOf(q) !== -1;
                card.classList.toggle('is-filtered-out', !match);
            });
            var noRes = $('#hcNoResults');
            if (noRes) noRes.hidden = visible > 0 || !q;
        }

        input.addEventListener('input', filter);
        $all('.hc-topic-card').forEach(function (card) {
            card.addEventListener('click', function () {
                var topic = card.getAttribute('data-hc-topic');
                input.value = topic.replace(/-/g, ' ');
                filter();
                var first = $('.hc-faq-item[data-hc-topic="' + topic + '"]:not(.is-filtered-out)');
                if (first) {
                    first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    openFaq(first);
                }
            });
        });
    }

    function initFaq() {
        $all('.hc-faq-q').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openFaq(btn.closest('.hc-faq-item'));
            });
        });
        $all('.hc-faq-ai').forEach(function (btn) {
            btn.addEventListener('click', function () { openAi(); });
        });
        $all('.hc-faq-admin').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var form = $('#hc-request-help');
                if (form) form.scrollIntoView({ behavior: 'smooth' });
            });
        });
        $all('.hc-faq-chat').forEach(function (btn) {
            btn.addEventListener('click', function () { openLiveChat(); });
        });
    }

    function openFaq(item) {
        if (!item) return;
        var open = item.classList.contains('is-open');
        $all('.hc-faq-item').forEach(function (i) {
            i.classList.remove('is-open');
            var q = i.querySelector('.hc-faq-q');
            if (q) q.setAttribute('aria-expanded', 'false');
        });
        if (!open) {
            item.classList.add('is-open');
            var btn = item.querySelector('.hc-faq-q');
            if (btn) btn.setAttribute('aria-expanded', 'true');
        }
    }

    /* ── Request form ── */
    function initRequestForm() {
        var form = $('#hcRequestForm');
        if (!form || !api.submitRequest) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var msg = $('#hcRequestMsg');
            var btn = $('#hcRequestSubmit');
            btn.disabled = true;
            post(api.submitRequest, {
                category: $('#hcCategory').value,
                subject: $('#hcSubject').value,
                body: $('#hcBody').value,
            }).then(function (res) {
                btn.disabled = false;
                if (!msg) return;
                msg.hidden = false;
                if (res.ok) {
                    msg.textContent = res.message;
                    msg.className = 'hc-form-msg is-success';
                    form.reset();
                } else {
                    msg.textContent = res.error || 'Could not send request.';
                    msg.className = 'hc-form-msg is-error';
                }
            }).catch(function () {
                btn.disabled = false;
                if (msg) {
                    msg.hidden = false;
                    msg.textContent = 'Network error. Please try again.';
                    msg.className = 'hc-form-msg is-error';
                }
            });
        });
    }

    /* ── AI Assistant ── */
    var aiPanel, aiBackdrop, aiMessages, aiInput, aiForm;

    function openAi() {
        if (!aiPanel) return;
        aiPanel.classList.add('is-open');
        aiPanel.setAttribute('aria-hidden', 'false');
        if (aiBackdrop) {
            aiBackdrop.hidden = false;
            requestAnimationFrame(function () { aiBackdrop.classList.add('is-visible'); });
        }
        if (aiInput) aiInput.focus();
    }

    function closeAi() {
        if (!aiPanel) return;
        aiPanel.classList.remove('is-open');
        aiPanel.setAttribute('aria-hidden', 'true');
        if (aiBackdrop) {
            aiBackdrop.classList.remove('is-visible');
            setTimeout(function () { aiBackdrop.hidden = true; }, 300);
        }
    }

    function appendAiMsg(text, type) {
        var div = document.createElement('div');
        div.className = 'hc-ai-msg hc-ai-msg--' + (type || 'bot');
        div.innerHTML = '<p>' + formatMarkdown(text) + '</p>';
        aiMessages.appendChild(div);
        aiMessages.scrollTop = aiMessages.scrollHeight;
        return div;
    }

    function formatMarkdown(text) {
        return String(text)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>')
            .replace(/\n/g, '<br>');
    }

    function askAi(question) {
        if (!question || !api.aiAsk) return;
        appendAiMsg(question, 'user');
        post(api.aiAsk, { question: question }).then(function (res) {
            if (!res.ok || !res.result) return;
            var r = res.result;
            var el = appendAiMsg(r.answer, 'bot');
            if (r.suggest_contact) {
                var actions = document.createElement('div');
                actions.className = 'hc-ai-contact-actions';
                actions.innerHTML =
                    '<button type="button" class="hc-btn hc-btn--ghost hc-ai-to-request">Send Message to Admin</button>' +
                    '<button type="button" class="hc-btn hc-btn--primary hc-ai-to-chat">Open Live Chat</button>';
                el.appendChild(actions);
                actions.querySelector('.hc-ai-to-request').addEventListener('click', function () {
                    closeAi();
                    var form = $('#hc-request-help');
                    if (form) form.scrollIntoView({ behavior: 'smooth' });
                });
                actions.querySelector('.hc-ai-to-chat').addEventListener('click', function () {
                    closeAi();
                    openLiveChat();
                });
            }
        });
    }

    function initAi() {
        aiPanel = $('#hcAiPanel');
        aiBackdrop = $('#hcAiBackdrop');
        aiMessages = $('#hcAiMessages');
        aiInput = $('#hcAiInput');
        aiForm = $('#hcAiForm');

        var openBtn = $('#hcOpenAi');
        var closeBtn = $('#hcCloseAi');
        if (openBtn) openBtn.addEventListener('click', openAi);
        if (closeBtn) closeBtn.addEventListener('click', closeAi);
        if (aiBackdrop) aiBackdrop.addEventListener('click', closeAi);

        if (aiForm) {
            aiForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var q = aiInput.value.trim();
                if (!q) return;
                aiInput.value = '';
                askAi(q);
            });
        }
        $all('#hcAiQuick button').forEach(function (btn) {
            btn.addEventListener('click', function () {
                askAi(btn.getAttribute('data-q'));
            });
        });
    }

    /* ── Live Chat ── */
    var liveChat, liveFab, liveMessages, liveInput, liveForm;
    var chatOpen = false;
    var chatMinimized = false;
    var lastChatId = 0;
    var pollTimer = null;

    function setOnlineStatus(online) {
        var el = $('#hcLiveStatus');
        var offline = $('#hcLiveOffline');
        if (el) {
            el.classList.toggle('is-online', online);
            el.innerHTML = '<span class="hc-dot"></span> ' + (online ? 'Online' : 'Offline');
        }
        if (offline) offline.hidden = online;
    }

    function renderChatMessage(m) {
        var div = document.createElement('div');
        div.className = 'hc-live-msg ' + (m.is_mine ? 'hc-live-msg--mine' : 'hc-live-msg--theirs');
        div.innerHTML = escapeHtml(m.body) + '<time>' + escapeHtml(m.time_label || '') + '</time>';
        liveMessages.appendChild(div);
        liveMessages.scrollTop = liveMessages.scrollHeight;
        if (m.id > lastChatId) lastChatId = m.id;
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function loadChatHistory() {
        if (!api.chatHistory) return;
        get(api.chatHistory).then(function (res) {
            if (!res.ok) return;
            setOnlineStatus(res.admin_online);
            liveMessages.innerHTML = '';
            lastChatId = 0;
            (res.messages || []).forEach(function (m) { renderChatMessage(m); });
        });
    }

    function pollChat() {
        if (!chatOpen || !api.chatPoll) return;
        get(api.chatPoll + (api.chatPoll.indexOf('?') >= 0 ? '&' : '?') + 'since_id=' + lastChatId)
            .then(function (res) {
                if (!res.ok) return;
                setOnlineStatus(res.admin_online);
                (res.messages || []).forEach(function (m) { renderChatMessage(m); });
            });
    }

    function openLiveChat() {
        if (!liveChat) return;
        chatOpen = true;
        chatMinimized = false;
        liveChat.classList.add('is-open');
        liveChat.classList.remove('is-minimized');
        liveChat.setAttribute('aria-hidden', 'false');
        if (liveFab) liveFab.classList.add('is-hidden');
        loadChatHistory();
        if (!pollTimer) pollTimer = setInterval(pollChat, 4000);
        if (liveInput) liveInput.focus();
    }

    function closeLiveChat() {
        chatOpen = false;
        if (liveChat) {
            liveChat.classList.remove('is-open');
            liveChat.setAttribute('aria-hidden', 'true');
        }
        if (liveFab) liveFab.classList.remove('is-hidden');
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        if (api.chatMarkRead) post(api.chatMarkRead, {});
    }

    function initLiveChat() {
        liveChat = $('#hcLiveChat');
        liveFab = $('#hcLiveFab');
        liveMessages = $('#hcLiveMessages');
        liveInput = $('#hcLiveInput');
        liveForm = $('#hcLiveForm');

        if (liveFab) liveFab.addEventListener('click', openLiveChat);
        var start = $('#hcStartChat');
        if (start) start.addEventListener('click', openLiveChat);
        var closeBtn = $('#hcLiveClose');
        var minBtn = $('#hcLiveMinimize');
        if (closeBtn) closeBtn.addEventListener('click', closeLiveChat);
        if (minBtn) minBtn.addEventListener('click', function () {
            chatMinimized = !chatMinimized;
            liveChat.classList.toggle('is-minimized', chatMinimized);
        });

        if (liveForm) {
            liveForm.addEventListener('submit', function (e) {
                e.preventDefault();
                var body = liveInput.value.trim();
                if (!body || !api.chatSend) return;
                liveInput.value = '';
                post(api.chatSend, { body: body }).then(function (res) {
                    if (res.ok && res.message) renderChatMessage(res.message);
                });
            });
        }

        if (window.location.hash === '#hc-live-chat') {
            setTimeout(openLiveChat, 400);
        }
    }

    function init() {
        if (!$('#hcPage')) return;
        initSearch();
        initFaq();
        initRequestForm();
        initAi();
        initLiveChat();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
