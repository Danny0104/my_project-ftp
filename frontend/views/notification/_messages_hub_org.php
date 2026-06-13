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

use common\models\Application;
use common\models\Organization;
use common\models\Position;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$organization = Organization::findOrCreateForUserId((int) Yii::$app->user->id);
$applications = [];
$activeApplicants = 0;
$interviewCount = 0;

if ($organization) {
    $applications = Application::find()
        ->alias('a')
        ->innerJoin(['p' => Position::tableName()], 'p.id = a.position_id')
        ->where(['p.organization_id' => $organization->id])
        ->with(['student.user', 'position'])
        ->orderBy(['a.created_at' => SORT_DESC])
        ->limit(30)
        ->all();

    foreach ($applications as $app) {
        if (!in_array($app->status, [Application::STATUS_REJECTED, Application::STATUS_WITHDRAWN], true)) {
            $activeApplicants++;
        }
        if (in_array($app->status, [Application::STATUS_ORG_APPROVED, Application::STATUS_UNIVERSITY_APPROVED], true)) {
            $interviewCount++;
        }
    }
}

$hasContent = !empty($notifications) || !empty($applications);
$firstActive = !empty($applications) ? 'app-' . $applications[0]->id : (!empty($notifications) ? (string) $notifications[0]->id : '');
?>

<div class="org-msg-page org-msg-page--workspace">
    <header class="org-msg-hero org-msg-hero--slim">
        <div class="org-msg-hero-bg" aria-hidden="true"></div>
        <div class="org-msg-hero-inner">
            <div>
                <p class="org-msg-eyebrow"><i class="fas fa-comments"></i> Recruitment communication center</p>
                <h1>Candidate messaging</h1>
                <p>Live two-way chat with applicants — messages deliver instantly when the chat server is running.</p>
                <div class="org-msg-hero-chips">
                    <span class="org-msg-chip"><strong data-count="<?= count($applications) ?>">0</strong> applicants</span>
                    <span class="org-msg-chip org-msg-chip--accent"><strong data-count="<?= (int) $unreadCount ?>" data-msg-unread-badge><?= (int) $unreadCount ?></strong> unread</span>
                    <span class="org-msg-chip"><strong data-count="<?= (int) $interviewCount ?>">0</strong> interviews</span>
                </div>
            </div>
            <div class="org-msg-hero-actions">
                <?= Html::a('<i class="fas fa-columns"></i> ATS board', ['application/index'], ['class' => 'org-btn org-btn-ghost']) ?>
                <?= Html::a('<i class="fas fa-bell"></i> Activity', ['notification/index', 'view' => 'notifications'], ['class' => 'org-btn org-btn-ghost']) ?>
                <button type="button" class="org-btn org-btn-primary" id="markAllRead">
                    <i class="fas fa-check-double"></i> Mark all read
                </button>
            </div>
        </div>
    </header>

    <?php if ($hasContent): ?>

    <script type="application/json" id="messaging-hub-config"><?= $this->render('_messaging_config', ['role' => 'organization', 'markReadUrl' => $markReadUrl, 'csrfParam' => $csrfParam, 'csrfToken' => $csrfToken, 'asJsonScript' => true]) ?></script>

    <div class="org-msg-shell">
    <div class="org-msg-layout org-messages-hub sp-messages-hub"
         data-messaging-hub
         data-active-id="<?= Html::encode($firstActive) ?>"
         data-messaging-config="<?= $this->render('_messaging_config', ['role' => 'organization', 'markReadUrl' => $markReadUrl, 'csrfParam' => $csrfParam, 'csrfToken' => $csrfToken]) ?>">
        <aside class="org-msg-inbox sp-conv-sidebar org-msg-sidebar" data-msg-sidebar aria-label="Inbox">
            <div class="sp-conv-sidebar-head org-msg-sidebar-head">
                <h2>Inbox</h2>
                <div class="org-msg-search-wrap">
                    <input type="search" class="sp-conv-search org-msg-search" data-msg-search placeholder="<?= Html::encode('Search students, roles…') ?>" aria-label="Search conversations">
                </div>
            </div>
            <div class="sp-conv-filters org-msg-filters" role="tablist" aria-label="Inbox filters">
                <button type="button" class="sp-conv-filter is-active" data-org-filter="all">All</button>
                <button type="button" class="sp-conv-filter" data-org-filter="applicants">Applicants</button>
                <button type="button" class="sp-conv-filter" data-org-filter="interviews">Interviews</button>
                <button type="button" class="sp-conv-filter" data-org-filter="support">Support</button>
                <button type="button" class="sp-conv-filter" data-org-filter="archived">Archived</button>
            </div>
            <div class="sp-conv-list org-msg-conv-list">
                <?php if (!empty($applications)): ?>
                    <div class="org-msg-conv-section-label">Active applicants</div>
                    <?php foreach ($applications as $i => $app): ?>
                        <?= $this->render('_org_conv_item', ['app' => $app, 'isActive' => $i === 0]) ?>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($notifications)): ?>
                    <div class="org-msg-conv-section-label">Platform inbox</div>
                    <?php foreach ($notifications as $i => $notification): ?>
                        <?php
                        $senderType = $notification->sender_type ?: 'system';
                        $isUnread = (int) $notification->is_read === 0;
                        $isInterview = preg_match('/interview|schedule|meeting/i', $notification->title . ' ' . $notification->message);
                        $filterTags = 'inbox ' . ($senderType === 'admin' || $senderType === 'system' ? 'support' : 'applicants');
                        if ($isInterview) {
                            $filterTags .= ' interviews';
                        }
                        $context = [
                            'source' => 'notification',
                            'id' => (int) $notification->id,
                            'title' => $notification->title,
                            'message' => $notification->message,
                            'senderType' => $senderType,
                            'isRead' => !$isUnread,
                            'time' => Yii::$app->formatter->asRelativeTime($notification->created_at),
                            'actionUrl' => $notification->action_url ?? '',
                            'actionText' => $notification->action_text ?: 'View',
                        ];
                        ?>
                        <div class="sp-conv-item notification-item org-msg-conv <?= $isUnread ? 'unread' : 'read' ?> <?= Html::encode($senderType) ?>-notification <?= (empty($applications) && $i === 0) ? 'is-active' : '' ?>"
                             data-conversation-id="<?= (int) $notification->id ?>"
                             data-conv-source="notification"
                             data-conv-filter-tags="<?= Html::encode($filterTags) ?>"
                             data-sender-type="<?= Html::encode($senderType) ?>"
                             data-action-url="<?= Html::encode($notification->action_url ?? '') ?>"
                             data-action-text="<?= Html::encode($notification->action_text ?: 'View Details') ?>"
                             data-context-json="<?= Html::encode(json_encode($context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
                             role="button" tabindex="0"
                             aria-selected="<?= ($firstActive === (string) $notification->id) ? 'true' : 'false' ?>">
                            <div class="sp-conv-avatar sp-conv-avatar--<?= Html::encode($senderType) ?>">
                                <i class="fas fa-<?= $senderType === 'admin' ? 'user-shield' : ($senderType === 'organization' ? 'building' : 'bell') ?>"></i>
                            </div>
                            <div class="sp-conv-body min-w-0">
                                <div class="org-msg-conv-head">
                                    <h3 class="sp-conv-title org-msg-truncate"><?= Html::encode($notification->title) ?></h3>
                                    <span class="sp-conv-time"><?= Yii::$app->formatter->asRelativeTime($notification->created_at) ?></span>
                                </div>
                                <p class="sp-conv-preview org-msg-truncate"><?= Html::encode($notification->message) ?></p>
                            </div>
                            <?php if ($isUnread): ?><span class="sp-conv-unread sp-pulse"></span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <div class="org-msg-conversation-panel">
        <section class="org-msg-chat sp-chat-center" aria-label="Conversation">
            <header class="org-msg-chat-header sp-chat-header org-msg-chat-head">
                <div class="org-msg-chat-head-text min-w-0">
                    <h2 class="sp-thread-title org-msg-truncate" data-msg-thread-title>Select a candidate</h2>
                    <p class="msg-presence org-msg-truncate" data-msg-presence><span class="msg-presence-dot" aria-hidden="true"></span> Select a conversation</p>
                </div>
                <div class="org-msg-chat-head-actions">
                    <button type="button" class="org-btn org-btn-ghost org-msg-mobile-inbox" data-msg-open-sidebar title="Inbox"><i class="fas fa-inbox"></i></button>
                    <button type="button" class="org-btn org-btn-ghost" id="spMarkReadActive" style="display:none"><i class="fas fa-check"></i></button>
                    <button type="button" class="org-btn org-btn-ghost text-danger" id="spDeleteActive" style="display:none"><i class="fas fa-trash"></i></button>
                </div>
            </header>

            <div class="org-msg-messages-scroll" data-msg-messages-scroll>
                <div class="org-msg-chat-body">
                    <div class="sp-chat-empty org-msg-chat-empty" data-msg-empty hidden>
                        <div><i class="fas fa-comments"></i><h3>Recruitment inbox</h3><p>Select an applicant or message to open the thread.</p></div>
                    </div>

                    <div class="org-msg-thread sp-chat-thread" data-msg-thread>
                        <div class="org-msg-thread-body sp-thread-body" data-msg-thread-body></div>
                        <div class="msg-typing-indicator" data-msg-typing hidden aria-live="polite"></div>
                    </div>
                </div>
            </div>

            <footer class="org-msg-composer-wrap">
                <div class="org-msg-composer-inner sp-chat-composer org-msg-composer msg-composer" data-msg-composer>
                    <div class="org-msg-composer-row">
                        <button type="button" class="org-btn org-btn-ghost org-msg-composer-icon" data-msg-attach title="Attach file"><i class="fas fa-paperclip"></i></button>
                        <textarea rows="1" class="org-msg-composer-input msg-composer-input" data-msg-composer-input placeholder="<?= Html::encode('Message candidate…') ?>" disabled></textarea>
                        <button type="button" class="org-btn org-btn-ghost org-msg-composer-icon" data-msg-emoji disabled title="Emoji (coming soon)"><i class="fas fa-face-smile"></i></button>
                        <button type="button" class="org-btn org-btn-ghost org-msg-composer-icon" data-msg-save-draft title="Save draft"><i class="fas fa-save"></i></button>
                        <button type="button" class="org-btn org-btn-primary msg-composer-send org-msg-composer-icon" data-msg-composer-send disabled title="Send"><i class="fas fa-paper-plane"></i></button>
                    </div>
                    <p class="msg-composer-note org-msg-composer-note" data-msg-composer-note>Select an applicant to start chatting.</p>
                    <div class="msg-composer-extras org-msg-quick-replies">
                        <button type="button" class="msg-quick-reply org-msg-qr" data-msg-quick-reply="Thanks for applying! We'll review your profile shortly.">Thanks for applying</button>
                        <button type="button" class="msg-quick-reply org-msg-qr" data-msg-quick-reply="Please upload your CV and cover letter.">Request documents</button>
                        <button type="button" class="msg-quick-reply org-msg-qr" data-msg-quick-reply="Your interview is scheduled — check your email for details.">Interview update</button>
                    </div>
                </div>
            </footer>
        </section>

        <aside class="org-msg-context-panel sp-chat-detail org-msg-context" id="orgMsgContext" data-msg-detail data-msg-context-panel aria-label="Candidate context">
            <h3 class="sp-detail-title">Candidate context</h3>
            <div class="org-msg-context-card" id="orgMsgContextStudent">
                <h4>Student</h4>
                <p class="org-msg-context-name">—</p>
                <p class="org-msg-context-sub">—</p>
            </div>
            <div class="org-msg-context-card">
                <h4>Application</h4>
                <dl class="org-msg-dl">
                    <div><dt>Role</dt><dd id="orgCtxRole">—</dd></div>
                    <div><dt>Status</dt><dd id="orgCtxStatus">—</dd></div>
                    <div><dt>GPA</dt><dd id="orgCtxGpa">—</dd></div>
                    <div><dt>Skills</dt><dd id="orgCtxSkills">—</dd></div>
                </dl>
            </div>
            <div class="org-msg-context-card org-msg-interview-card" id="orgMsgInterview" hidden>
                <h4><i class="fas fa-video"></i> Interview mode</h4>
                <p class="small mb-2">Schedule and share meeting details with the candidate.</p>
                <span class="org-msg-countdown">Next step: confirm slot</span>
            </div>
            <div class="org-msg-context-actions">
                <a href="#" class="org-btn org-btn-primary org-msg-ctx-action" id="orgCtxPrimary" hidden>View application</a>
                <a href="<?= Url::to(['application/index']) ?>" class="org-btn org-btn-ghost">Open ATS</a>
                <button type="button" class="org-btn org-btn-ghost" id="orgNeedHelp"><i class="fas fa-life-ring"></i> Need help?</button>
            </div>
            <div class="org-msg-support-panel" id="orgSupportPanel" hidden>
                <h4>Support shortcuts</h4>
                <span class="org-msg-support-badge org-msg-support-badge--open">Open</span>
                <p class="small">Escalate platform issues without leaving messaging.</p>
                <?= Html::a('Contact admin', ['site/contact'], ['class' => 'org-btn org-btn-ghost btn-sm']) ?>
            </div>
        </aside>
        </div>
    </div>
    </div>

    <?php if ($dataProvider->pagination->pageCount > 1): ?>
        <div class="sp-pagination mt-3"><?= \yii\widgets\LinkPager::widget(['pagination' => $dataProvider->pagination]) ?></div>
    <?php endif; ?>

    <?php else: ?>
    <div class="org-msg-empty org-glass">
        <div class="org-msg-empty-orbit" aria-hidden="true">
            <i class="fas fa-user-graduate"></i>
            <i class="fas fa-comments"></i>
            <i class="fas fa-briefcase"></i>
        </div>
        <h2>Connect with your applicants</h2>
        <p>When students apply to your internships, their threads will appear here alongside platform messages.</p>
        <?= Html::a('<i class="fas fa-briefcase"></i> Manage opportunities', ['position/index'], ['class' => 'org-btn org-btn-primary']) ?>
        <?= Html::a('<i class="fas fa-columns"></i> View applications', ['application/index'], ['class' => 'org-btn org-btn-ghost']) ?>
    </div>
    <?php endif; ?>
</div>

<div class="org-msg-toast-stack msg-toast-stack" id="orgMsgToastStack" aria-live="polite"></div>
