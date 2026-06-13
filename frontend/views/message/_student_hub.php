<?php
/** @var array $conversations */
/** @var int $unreadMessages */
/** @var int $activeConversationId */
/** @var string $markReadUrl */
/** @var string $csrfParam */
/** @var string $csrfToken */

use yii\helpers\Html;

$firstId = $activeConversationId > 0
    ? (string) $activeConversationId
    : (!empty($conversations) ? (string) $conversations[0]['id'] : '');
?>

<div class="msg-page-hero sp-page-header-row">
    <div>
        <h1>Messages</h1>
        <p>Professional workspace for live conversations with organizations about your applications.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?= Html::a('<i class="fas fa-bell me-1"></i> Notifications', ['notification/index'], ['class' => 'sp-btn-ghost']) ?>
    </div>
</div>

<div class="msg-stats-row">
    <span class="msg-stat-pill"><strong><?= count($conversations) ?></strong> conversations</span>
    <span class="msg-stat-pill msg-stat-pill--warn">
        <strong data-count="<?= (int) $unreadMessages ?>" data-msg-unread-badge><?= (int) $unreadMessages ?></strong> unread
    </span>
</div>

<?php if (!empty($conversations)): ?>

<script type="application/json" id="messaging-hub-config"><?= $this->render('@frontend/views/notification/_messaging_config', ['role' => 'student', 'markReadUrl' => $markReadUrl, 'csrfParam' => $csrfParam, 'csrfToken' => $csrfToken, 'asJsonScript' => true]) ?></script>

<div class="msg-workspace sp-messages-hub"
     data-messaging-hub
     data-active-id="<?= Html::encode($firstId) ?>"
     data-messaging-config="<?= $this->render('@frontend/views/notification/_messaging_config', ['role' => 'student', 'markReadUrl' => $markReadUrl, 'csrfParam' => $csrfParam, 'csrfToken' => $csrfToken]) ?>">

    <!-- Left: conversation sidebar -->
    <aside class="msg-panel msg-panel--sidebar sp-conv-sidebar" data-msg-sidebar>
        <div class="msg-sidebar-head">
            <h2>Inbox</h2>
            <div class="msg-search-wrap">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" class="sp-conv-search" data-msg-search placeholder="Search conversations…" aria-label="Search conversations">
            </div>
        </div>
        <div class="msg-filter-bar sp-conv-filters">
            <button type="button" class="sp-conv-filter is-active" data-conv-filter="all">All</button>
            <button type="button" class="sp-conv-filter" data-conv-filter="organizations">Organizations</button>
            <button type="button" class="sp-conv-filter" data-conv-filter="unread">Unread</button>
            <button type="button" class="sp-conv-filter" data-conv-filter="archived">Archived</button>
        </div>
        <div class="msg-conv-list sp-conv-list">
            <?php foreach ($conversations as $conv): ?>
                <?= $this->render('_conversation_item', ['conv' => $conv, 'isActive' => (string) $conv['id'] === $firstId]) ?>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Center: chat room -->
    <section class="msg-panel msg-panel--chat sp-chat-center">
        <header class="msg-chat-toolbar sp-chat-header">
            <div class="msg-chat-toolbar__identity">
                <div class="msg-chat-toolbar__avatar" data-msg-header-avatar aria-hidden="true"></div>
                <div class="msg-chat-toolbar__text min-w-0">
                    <h2 class="sp-thread-title" data-msg-thread-title>Select a conversation</h2>
                    <p class="msg-presence mb-0" data-msg-presence role="status" aria-live="polite">
                        <span class="msg-presence-dot" aria-hidden="true"></span>
                        <span data-msg-presence-label>Select a conversation</span>
                    </p>
                </div>
            </div>
            <div class="msg-chat-toolbar__actions">
                <button type="button" class="msg-toolbar-btn d-md-none" data-msg-open-sidebar title="Conversations" aria-label="Conversations"><i class="fas fa-inbox"></i></button>
                <button type="button" class="msg-toolbar-btn" data-msg-thread-search-toggle title="Search in conversation"><i class="fas fa-magnifying-glass"></i></button>
                <button type="button" class="msg-toolbar-btn" data-msg-pin title="Pin conversation"><i class="fas fa-thumbtack"></i></button>
                <button type="button" class="msg-toolbar-btn" data-msg-star title="Star conversation"><i class="fas fa-star"></i></button>
                <button type="button" class="msg-toolbar-btn" data-msg-mute title="Mute conversation"><i class="fas fa-bell-slash"></i></button>
                <button type="button" class="msg-toolbar-btn" data-msg-mark-unread title="Mark unread"><i class="fas fa-envelope"></i></button>
                <button type="button" class="msg-toolbar-btn" data-msg-archive title="Archive"><i class="fas fa-box-archive"></i></button>
                <button type="button" class="msg-toolbar-btn d-xl-none" data-msg-open-context title="Details"><i class="fas fa-circle-info"></i></button>
            </div>
        </header>

        <div class="msg-thread-search">
            <input type="search" data-msg-thread-search placeholder="Search messages in this conversation…" aria-label="Search messages">
            <button type="button" class="msg-toolbar-btn" data-msg-thread-search-toggle aria-label="Close search"><i class="fas fa-xmark"></i></button>
        </div>

        <div class="sp-chat-empty" data-msg-empty>
            <div class="msg-welcome">
                <i class="fas fa-comments"></i>
                <h3>Your recruitment inbox</h3>
                <p>Select a conversation to view messages, attachments, and application context.</p>
            </div>
        </div>

        <div class="sp-chat-thread" data-msg-thread hidden>
            <div class="msg-thread-scroll" data-msg-messages-scroll>
                <div class="sp-thread-body" data-msg-thread-body></div>
                <div class="msg-typing-indicator" data-msg-typing hidden aria-live="polite"></div>
            </div>
        </div>

        <div class="msg-composer-premium sp-chat-composer msg-composer" data-msg-composer>
            <div class="msg-reply-bar" data-msg-reply-bar>
                <i class="fas fa-reply" aria-hidden="true"></i>
                <span class="msg-reply-bar__text" data-msg-reply-text></span>
                <button type="button" class="msg-toolbar-btn" data-msg-reply-clear aria-label="Cancel reply"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="msg-attach-preview" data-msg-attach-preview></div>
            <div class="msg-composer-row">
                <button type="button" class="msg-toolbar-btn" data-msg-attach title="Attach file"><i class="fas fa-paperclip"></i></button>
                <textarea rows="1" class="msg-composer-input" data-msg-composer-input placeholder="Write a message…" disabled></textarea>
                <button type="button" class="msg-toolbar-btn" data-msg-emoji title="Emoji"><i class="fas fa-face-smile"></i></button>
                <button type="button" class="msg-composer-send" data-msg-composer-send disabled title="Send"><i class="fas fa-paper-plane"></i></button>
            </div>
            <p class="msg-composer-note" data-msg-composer-note>Select a conversation to reply.</p>
        </div>
    </section>

    <!-- Right: context panel -->
    <aside class="msg-panel msg-panel--context sp-chat-detail" data-msg-detail>
        <div class="msg-context-head">
            <div class="msg-context-avatar" data-msg-context-avatar></div>
            <h3 class="msg-context-name" data-msg-context-name>—</h3>
            <p class="msg-context-sub" data-msg-context-sub>Conversation details</p>
        </div>
        <div class="msg-context-section">
            <h4>About</h4>
            <div class="msg-context-kv">
                <div><span>Role / internship</span><strong data-msg-ctx-role>—</strong></div>
                <div><span>Application status</span><strong data-msg-ctx-status>—</strong></div>
                <div><span>Last activity</span><strong data-msg-ctx-time>—</strong></div>
            </div>
        </div>
        <div class="msg-context-section">
            <h4>Organization</h4>
            <div class="msg-context-kv">
                <div><span>Location</span><strong data-msg-ctx-location>—</strong></div>
                <div><span>Industry</span><strong data-msg-ctx-industry>—</strong></div>
            </div>
        </div>
        <div class="msg-context-actions">
            <?= Html::a('<i class="fas fa-briefcase me-1"></i> Browse opportunities', ['position/index'], ['class' => 'sp-btn-ghost text-center']) ?>
            <?= Html::a('<i class="fas fa-file-lines me-1"></i> My applications', ['application/index'], ['class' => 'sp-btn-ghost text-center']) ?>
            <a href="#" class="sp-btn-ghost text-center sp-detail-action" data-msg-ctx-action hidden>View details</a>
        </div>
    </aside>
</div>

<div class="msg-toast-stack" id="msgToastStack" aria-live="polite"></div>

<?php else: ?>
<div class="sp-empty sp-glass">
    <i class="fas fa-comments d-block"></i>
    <h3>No conversations yet</h3>
    <p>When an organization contacts you or you apply to internships, live chats appear here.</p>
    <?= Html::a('Explore opportunities', ['position/index'], ['class' => 'sp-btn-primary']) ?>
</div>
<?php endif; ?>
