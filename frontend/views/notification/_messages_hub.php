<?php
/** @var \common\models\Notification[] $notifications */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int $unreadCount */
/** @var int $orgCount */
/** @var int $adminCount */
/** @var string $markReadUrl */
/** @var string $deleteUrl */
/** @var string $markAllReadUrl */
/** @var string $csrfParam */
/** @var string $csrfToken */

use common\widgets\ProfileAvatar;
use yii\helpers\Html;
?>

<div class="sp-page-header sp-page-header-row mb-3">
    <div>
        <h1>Messages</h1>
        <p>Chat with recruiters in real time. Platform announcements remain read-only.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?= Html::a('<i class="fas fa-bell me-1"></i> Activity Feed', ['notification/index', 'view' => 'notifications'], ['class' => 'sp-btn-ghost']) ?>
        <button type="button" class="sp-btn-primary" id="markAllRead">
            <i class="fas fa-check-double me-1"></i> Mark all read
        </button>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <span class="sp-mini-stat"><strong><?= count($notifications) ?></strong> Total</span>
    <span class="sp-mini-stat sp-mini-stat--warn"><strong data-count="<?= (int) $unreadCount ?>" data-msg-unread-badge><?= (int) $unreadCount ?></strong> Unread</span>
    <span class="sp-mini-stat sp-mini-stat--success"><strong><?= (int) $orgCount ?></strong> Organizations</span>
    <span class="sp-mini-stat sp-mini-stat--info"><strong><?= (int) $adminCount ?></strong> Admin</span>
</div>

<?php if (!empty($notifications)): ?>

<?php
$firstId = !empty($notifications) ? (string) $notifications[0]->id : '';
?>

<?php if ((int) $orgCount === 0 && !empty($notifications)): ?>
<div class="msg-no-recruiter-banner sp-glass mb-3">
    <i class="fas fa-info-circle"></i>
    <div>
        <strong>No recruiter chats yet</strong>
        <p class="mb-0">You have platform announcements only. Live two-way chat opens when an organization contacts you — look for the <span class="sp-conv-chat-badge">Live chat</span> badge, or apply to internships and wait for recruiter messages.</p>
    </div>
    <?= Html::a('Browse opportunities', ['position/index'], ['class' => 'sp-btn-primary btn-sm']) ?>
</div>
<?php endif; ?>

<script type="application/json" id="messaging-hub-config"><?= $this->render('_messaging_config', ['role' => 'student', 'markReadUrl' => $markReadUrl, 'csrfParam' => $csrfParam, 'csrfToken' => $csrfToken, 'asJsonScript' => true]) ?></script>

<div class="sp-messages-hub"
     data-messaging-hub
     data-active-id="<?= Html::encode($firstId) ?>"
     data-messaging-config="<?= $this->render('_messaging_config', ['role' => 'student', 'markReadUrl' => $markReadUrl, 'csrfParam' => $csrfParam, 'csrfToken' => $csrfToken]) ?>">
    <aside class="sp-conv-sidebar" data-msg-sidebar>
        <div class="sp-conv-sidebar-head">
            <h2>Conversations</h2>
            <input type="search" class="sp-conv-search" data-msg-search placeholder="Search messages…" aria-label="Search conversations">
        </div>
        <div class="sp-conv-filters">
            <button type="button" class="sp-conv-filter is-active" data-conv-filter="all">All</button>
            <button type="button" class="sp-conv-filter" data-conv-filter="organizations">Organizations</button>
            <button type="button" class="sp-conv-filter" data-conv-filter="support">Support</button>
            <button type="button" class="sp-conv-filter" data-conv-filter="interviews">Interviews</button>
            <button type="button" class="sp-conv-filter" data-conv-filter="archived">Archived</button>
        </div>
        <div class="sp-conv-list">
            <?php foreach ($notifications as $i => $notification): ?>
                <?php
                $senderType = $notification->sender_type ?: 'system';
                $isUnread = (int) $notification->is_read === 0;
                $context = [
                    'source' => 'notification',
                    'id' => (int) $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'senderType' => $senderType,
                    'isRead' => !$isUnread,
                    'time' => Yii::$app->formatter->asRelativeTime($notification->created_at),
                    'actionUrl' => $notification->action_url ?? '',
                    'actionText' => $notification->action_text ?: 'View Details',
                    'chatEnabled' => $senderType === 'organization',
                ];
                ?>
                <div class="sp-conv-item notification-item <?= $isUnread ? 'unread' : 'read' ?> <?= Html::encode($senderType) ?>-notification <?= $i === 0 ? 'is-active' : '' ?>"
                     data-conversation-id="<?= (int) $notification->id ?>"
                     data-conv-source="notification"
                     data-conv-filter-tags="inbox <?= $senderType === 'organization' ? 'organizations' : ($senderType === 'admin' || $senderType === 'system' ? 'support' : '') ?>"
                     data-sender-type="<?= Html::encode($senderType) ?>"
                     data-action-url="<?= Html::encode($notification->action_url ?? '') ?>"
                     data-action-text="<?= Html::encode($notification->action_text ?: 'View Details') ?>"
                     data-context-json="<?= Html::encode(json_encode($context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
                     data-search-text="<?= Html::encode(strtolower($notification->title . ' ' . $notification->message)) ?>"
                     role="button" tabindex="0" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                    <div class="sp-conv-avatar sp-conv-avatar--<?= Html::encode($senderType) ?>">
                        <?php if ($senderType === 'organization'): ?>
                            <?= ProfileAvatar::widget(['type' => 'organization', 'organization' => $notification->organization, 'size' => 'sm']) ?>
                        <?php else: ?>
                            <i class="fas fa-<?= $senderType === 'admin' ? 'user-shield' : 'bell' ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="sp-conv-body">
                        <div class="d-flex justify-content-between gap-2">
                            <h3 class="sp-conv-title"><?= Html::encode($notification->title) ?></h3>
                            <span class="sp-conv-time"><?= Yii::$app->formatter->asRelativeTime($notification->created_at) ?></span>
                        </div>
                        <p class="sp-conv-preview"><?= Html::encode($notification->message) ?></p>
                        <?php if ($senderType === 'organization'): ?>
                            <span class="sp-conv-chat-badge">Live chat</span>
                        <?php elseif ($senderType === 'admin' || $senderType === 'system'): ?>
                            <span class="sp-conv-announce-badge">Announcement</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($isUnread): ?><span class="sp-conv-unread sp-pulse" aria-label="Unread"></span><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <section class="sp-chat-center">
        <header class="sp-chat-header">
            <div>
                <h2 class="sp-thread-title" data-msg-thread-title>Select a conversation</h2>
                <p class="msg-presence" data-msg-presence><span class="msg-presence-dot" aria-hidden="true"></span> Select a conversation</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="sp-btn-ghost d-inline-flex d-md-none" data-msg-open-sidebar title="Conversations" aria-label="Conversations"><i class="fas fa-inbox"></i></button>
                <button type="button" class="sp-btn-ghost" id="spMarkReadActive" style="display:none"><i class="fas fa-check"></i></button>
                <button type="button" class="sp-btn-ghost text-danger" id="spDeleteActive" style="display:none"><i class="fas fa-trash"></i></button>
            </div>
        </header>
        <div class="sp-chat-empty" data-msg-empty>
            <div><i class="fas fa-comments fa-3x text-muted mb-3"></i><h3>Your inbox</h3><p>Select a conversation to read the full message.</p></div>
        </div>
        <div class="sp-chat-thread" data-msg-thread hidden>
            <div class="msg-thread-scroll">
                <div class="sp-thread-body" data-msg-thread-body></div>
                <div class="msg-typing-indicator" data-msg-typing hidden aria-live="polite"></div>
            </div>
        </div>
        <div class="sp-chat-composer msg-composer" data-msg-composer>
            <button type="button" class="sp-btn-ghost" data-msg-attach title="Attach file"><i class="fas fa-paperclip"></i></button>
            <textarea rows="1" class="msg-composer-input" data-msg-composer-input placeholder="Type a message…" disabled></textarea>
            <button type="button" class="sp-btn-ghost" data-msg-emoji title="Insert emoji"><i class="fas fa-face-smile"></i></button>
            <button type="button" class="sp-btn-primary msg-composer-send" data-msg-composer-send disabled title="Send"><i class="fas fa-paper-plane"></i></button>
            <p class="msg-composer-note sp-chat-note" data-msg-composer-note>Select a recruiter conversation to reply.</p>
        </div>
    </section>

    <aside class="sp-chat-detail" data-msg-detail hidden>
        <h3 class="sp-detail-title">Details</h3>
        <div class="sp-assist-card">
            <h4>Recruiter context</h4>
            <p>Quick access to internship links and next steps for the active conversation.</p>
        </div>
        <div class="sp-detail-meta">
            <div>Type: <strong class="sp-detail-type">—</strong></div>
            <div class="mt-1">Received: <strong class="sp-detail-time">—</strong></div>
        </div>
        <div class="d-grid gap-2">
            <a href="#" class="sp-detail-action sp-btn-primary text-center" hidden>View Details</a>
            <?= Html::a('Browse Opportunities', ['position/index'], ['class' => 'sp-btn-ghost text-center']) ?>
            <?= Html::a('Update Profile', ['profile/student'], ['class' => 'sp-btn-ghost text-center']) ?>
        </div>
    </aside>
</div>

<?php if ($dataProvider->pagination->pageCount > 1): ?>
    <div class="sp-pagination mt-3"><?= \yii\widgets\LinkPager::widget(['pagination' => $dataProvider->pagination]) ?></div>
<?php endif; ?>

<div class="msg-toast-stack" id="msgToastStack" aria-live="polite"></div>

<?php else: ?>
<div class="sp-empty sp-glass">
    <i class="fas fa-bell-slash d-block"></i>
    <h3>No messages yet</h3>
    <p>When organizations or admins contact you, conversations will appear here.</p>
    <?= Html::a('Explore Opportunities', ['position/index'], ['class' => 'sp-btn-primary']) ?>
</div>
<?php endif; ?>
