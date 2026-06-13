<?php
/** @var yii\web\View $this */
/** @var \common\models\User $user */
/** @var array<int, array<string, mixed>> $messages */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Live Chat: ' . $user->username;
$this->params['breadcrumbs'][] = ['label' => 'Support Inbox', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerJs('window.ftAdminSupportChat = ' . Json::htmlEncode([
    'userId' => (int) $user->id,
    'sendUrl' => Url::to(['support/chat-send']),
    'pollUrl' => Url::to(['support/chat-poll', 'user_id' => $user->id]),
    'messages' => $messages,
]) . ';', \yii\web\View::POS_HEAD);

$this->registerJs(<<<'JS'
(function () {
    var cfg = window.ftAdminSupportChat || {};
    var box = document.getElementById('adminChatMessages');
    var form = document.getElementById('adminChatForm');
    var input = document.getElementById('adminChatInput');
    var lastId = 0;

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function append(m) {
        var div = document.createElement('div');
        div.className = 'support-msg ' + (m.sender_role === 'admin' ? 'support-msg--admin' : 'support-msg--user');
        div.innerHTML = '<div class="support-msg-meta"><strong>' + esc(m.sender_role) + '</strong><time>' + esc(m.time_label || '') + '</time></div><div class="support-msg-body">' + esc(m.body) + '</div>';
        box.appendChild(div);
        box.scrollTop = box.scrollHeight;
        if (m.id > lastId) lastId = m.id;
    }

    (cfg.messages || []).forEach(append);

    function poll() {
        fetch(cfg.pollUrl + '&since_id=' + lastId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.ok) return;
                (res.messages || []).forEach(append);
            });
    }

    setInterval(poll, 4000);

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var body = input.value.trim();
            if (!body) return;
            var data = new URLSearchParams();
            data.append('user_id', cfg.userId);
            data.append('body', body);
            var csrf = document.querySelector('meta[name="csrf-token"]');
            if (csrf) data.append('_csrf-backend', csrf.getAttribute('content'));
            fetch(cfg.sendUrl, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: data })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.ok && res.message) {
                        input.value = '';
                        append(res.message);
                    }
                });
        });
    }
})();
JS
);
?>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-comments me-2"></i><?= Html::encode($user->username) ?> (<?= Html::encode($user->role) ?>)</span>
        <?= Html::a('Back to inbox', ['index'], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
    </div>
    <div class="card-body support-thread" id="adminChatMessages" style="min-height:360px;max-height:480px;overflow-y:auto"></div>
    <div class="card-footer">
        <form id="adminChatForm" class="d-flex gap-2">
            <input type="text" id="adminChatInput" class="form-control" placeholder="Type your message…" maxlength="2000" required>
            <button type="submit" class="btn btn-primary">Send</button>
        </form>
    </div>
</div>

<style>
.support-thread { display: flex; flex-direction: column; gap: 12px; }
.support-msg { padding: 12px 14px; border-radius: 12px; max-width: 80%; }
.support-msg--user { background: #f1f5f9; align-self: flex-start; }
.support-msg--admin { background: #dbeafe; align-self: flex-end; }
.support-msg-meta { font-size: 0.75rem; color: #64748b; margin-bottom: 4px; display: flex; gap: 8px; }
</style>
