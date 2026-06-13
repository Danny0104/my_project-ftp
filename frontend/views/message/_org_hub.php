<?php
/** @var array $conversations */
/** @var int $unreadMessages */
/** @var int $activeConversationId */
/** @var \common\models\Application[] $pendingApplications */
/** @var string $markReadUrl */
/** @var string $csrfParam */
/** @var string $csrfToken */

use yii\helpers\Html;

$firstId = $activeConversationId > 0
    ? (string) $activeConversationId
    : (!empty($conversations)
        ? (string) $conversations[0]['id']
        : (!empty($pendingApplications) ? 'app-' . $pendingApplications[0]->id : ''));
$hasContent = !empty($conversations) || !empty($pendingApplications);
?>

<div class="org-msg-page org-msg-page--workspace">
    <header class="msg-page-hero org-msg-hero org-msg-hero--slim">
        <div class="org-msg-hero-inner">
            <div>
                <p class="org-msg-eyebrow"><i class="fas fa-comments"></i> Recruitment messaging</p>
                <h1>Candidate conversations</h1>
                <p>Enterprise workspace for applicant communication — platform alerts stay in Notifications.</p>
                <div class="msg-stats-row org-msg-hero-chips" style="margin-bottom:0">
                    <span class="msg-stat-pill"><strong data-count="<?= count($conversations) ?>"><?= count($conversations) ?></strong> active</span>
                    <span class="msg-stat-pill msg-stat-pill--warn"><strong data-count="<?= (int) $unreadMessages ?>" data-msg-unread-badge><?= (int) $unreadMessages ?></strong> unread</span>
                </div>
            </div>
            <div class="org-msg-hero-actions">
                <?= Html::a('<i class="fas fa-columns"></i> ATS', ['application/index'], ['class' => 'org-btn org-btn-ghost']) ?>
                <?= Html::a('<i class="fas fa-bell"></i> Notifications', ['notification/index'], ['class' => 'org-btn org-btn-ghost']) ?>
            </div>
        </div>
    </header>

    <?php if ($hasContent): ?>

    <script type="application/json" id="messaging-hub-config"><?= $this->render('@frontend/views/notification/_messaging_config', ['role' => 'organization', 'markReadUrl' => $markReadUrl, 'csrfParam' => $csrfParam, 'csrfToken' => $csrfToken, 'asJsonScript' => true]) ?></script>

    <div class="org-msg-shell">
    <div class="msg-workspace org-msg-layout org-messages-hub sp-messages-hub"
         data-messaging-hub
         data-active-id="<?= Html::encode($firstId) ?>"
         data-messaging-config="<?= $this->render('@frontend/views/notification/_messaging_config', ['role' => 'organization', 'markReadUrl' => $markReadUrl, 'csrfParam' => $csrfParam, 'csrfToken' => $csrfToken]) ?>">

        <aside class="msg-panel msg-panel--sidebar org-msg-inbox sp-conv-sidebar org-msg-sidebar" data-msg-sidebar>
            <div class="msg-sidebar-head org-msg-sidebar-head">
                <h2>Inbox</h2>
                <div class="msg-search-wrap org-msg-search-wrap">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="search" class="sp-conv-search org-msg-search" data-msg-search placeholder="Search students, roles…" aria-label="Search conversations">
                </div>
            </div>
            <div class="msg-filter-bar sp-conv-filters org-msg-filters">
                <button type="button" class="sp-conv-filter is-active" data-org-filter="all">All</button>
                <button type="button" class="sp-conv-filter" data-org-filter="students">Students</button>
                <button type="button" class="sp-conv-filter" data-org-filter="unread">Unread</button>
                <button type="button" class="sp-conv-filter" data-org-filter="interviews">Interviews</button>
                <button type="button" class="sp-conv-filter" data-org-filter="archived">Archived</button>
            </div>
            <div class="msg-conv-list sp-conv-list org-msg-conv-list">
                <?php if (!empty($conversations)): ?>
                    <div class="org-msg-conv-section-label">Active conversations</div>
                    <?php foreach ($conversations as $conv): ?>
                        <?= $this->render('_conversation_item_org', [
                            'conv' => $conv,
                            'isActive' => (string) $conv['id'] === $firstId,
                        ]) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($pendingApplications)): ?>
                    <div class="org-msg-conv-section-label">Applicants — start chat</div>
                    <?php foreach ($pendingApplications as $app): ?>
                        <?= $this->render('@frontend/views/notification/_org_conv_item', [
                            'app' => $app,
                            'isActive' => $firstId === 'app-' . $app->id,
                        ]) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <div class="org-msg-conversation-panel">
        <section class="msg-panel msg-panel--chat org-msg-chat sp-chat-center">
            <header class="msg-chat-toolbar org-msg-chat-header sp-chat-header">
                <div class="msg-chat-toolbar__identity">
                    <div class="msg-chat-toolbar__avatar" data-msg-header-avatar></div>
                    <div class="msg-chat-toolbar__text min-w-0">
                        <h2 class="sp-thread-title org-msg-truncate" data-msg-thread-title>Select a candidate</h2>
                        <p class="msg-presence org-msg-truncate mb-0" data-msg-presence>
                            <span class="msg-presence-dot" aria-hidden="true"></span>
                            <span data-msg-presence-label>Select a conversation</span>
                        </p>
                    </div>
                </div>
                <div class="msg-chat-toolbar__actions org-msg-chat-head-actions">
                    <button type="button" class="msg-toolbar-btn org-msg-mobile-inbox" data-msg-open-sidebar title="Inbox"><i class="fas fa-inbox"></i></button>
                    <button type="button" class="msg-toolbar-btn" data-msg-thread-search-toggle title="Search"><i class="fas fa-magnifying-glass"></i></button>
                    <button type="button" class="msg-toolbar-btn" data-msg-pin title="Pin"><i class="fas fa-thumbtack"></i></button>
                    <button type="button" class="msg-toolbar-btn" data-msg-star title="Star"><i class="fas fa-star"></i></button>
                    <button type="button" class="msg-toolbar-btn" data-msg-mute title="Mute"><i class="fas fa-bell-slash"></i></button>
                    <button type="button" class="msg-toolbar-btn" data-msg-mark-unread title="Mark unread"><i class="fas fa-envelope"></i></button>
                    <button type="button" class="msg-toolbar-btn" data-msg-archive title="Archive"><i class="fas fa-box-archive"></i></button>
                    <button type="button" class="msg-toolbar-btn d-xl-none" data-msg-open-context title="Candidate info"><i class="fas fa-user"></i></button>
                </div>
            </header>

            <div class="msg-thread-search">
                <input type="search" data-msg-thread-search placeholder="Search in conversation…" aria-label="Search messages">
                <button type="button" class="msg-toolbar-btn" data-msg-thread-search-toggle><i class="fas fa-xmark"></i></button>
            </div>

            <div class="org-msg-messages-scroll" data-msg-messages-scroll>
                <div class="org-msg-chat-body">
                    <div class="sp-chat-empty org-msg-chat-empty" data-msg-empty>
                        <div class="msg-welcome">
                            <i class="fas fa-comments"></i>
                            <h3>Recruitment inbox</h3>
                            <p>Select an applicant to review messages, attachments, and candidate context.</p>
                        </div>
                    </div>
                    <div class="sp-chat-thread org-msg-thread" data-msg-thread hidden>
                        <div class="msg-thread-scroll">
                            <div class="sp-thread-body" data-msg-thread-body></div>
                            <div class="msg-typing-indicator" data-msg-typing hidden aria-live="polite"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="msg-composer-premium org-msg-composer sp-chat-composer msg-composer" data-msg-composer>
                <div class="msg-reply-bar" data-msg-reply-bar>
                    <i class="fas fa-reply"></i>
                    <span class="msg-reply-bar__text" data-msg-reply-text></span>
                    <button type="button" class="msg-toolbar-btn" data-msg-reply-clear><i class="fas fa-xmark"></i></button>
                </div>
                <div class="msg-attach-preview" data-msg-attach-preview></div>
                <div class="msg-composer-row">
                    <button type="button" class="msg-toolbar-btn" data-msg-attach title="Attach"><i class="fas fa-paperclip"></i></button>
                    <textarea rows="1" class="msg-composer-input org-msg-composer-input" data-msg-composer-input placeholder="Write a message…" disabled></textarea>
                    <button type="button" class="msg-toolbar-btn" data-msg-emoji title="Emoji"><i class="fas fa-face-smile"></i></button>
                    <button type="button" class="msg-composer-send" data-msg-composer-send disabled><i class="fas fa-paper-plane"></i></button>
                </div>
                <p class="msg-composer-note" data-msg-composer-note>Select a conversation to send messages.</p>
            </div>
        </section>

        <aside class="msg-panel msg-panel--context org-msg-detail sp-chat-detail" data-msg-detail>
            <div class="msg-context-head">
                <div class="msg-context-avatar" data-msg-context-avatar></div>
                <h3 class="msg-context-name org-msg-context-name" data-msg-context-name>—</h3>
                <p class="msg-context-sub org-msg-context-sub" data-msg-context-sub>Candidate profile</p>
            </div>
            <div class="msg-context-section">
                <h4>Application</h4>
                <div class="msg-context-kv">
                    <div><span>Internship</span><strong id="orgCtxRole" data-msg-ctx-role>—</strong></div>
                    <div><span>Status</span><strong id="orgCtxStatus" data-msg-ctx-status>—</strong></div>
                    <div><span>GPA</span><strong id="orgCtxGpa" data-msg-ctx-gpa>—</strong></div>
                </div>
            </div>
            <div class="msg-context-section">
                <h4>Candidate</h4>
                <div class="msg-context-kv">
                    <div><span>Department / field</span><strong data-msg-ctx-field>—</strong></div>
                    <div><span>Skills</span><strong id="orgCtxSkills" data-msg-ctx-skills>—</strong></div>
                    <div><span>Interview</span><strong data-msg-ctx-interview>—</strong></div>
                </div>
            </div>
            <div class="msg-context-actions">
                <a href="#" class="org-btn org-btn-primary text-center" id="orgCtxPrimary" data-msg-ctx-action hidden>View application</a>
                <?= Html::a('<i class="fas fa-columns me-1"></i> Open ATS', ['application/index'], ['class' => 'org-btn org-btn-ghost text-center']) ?>
                <a href="#" class="org-btn org-btn-ghost text-center" id="orgMsgInterview" hidden>Schedule interview</a>
            </div>
        </aside>
        </div>
    </div>
    </div>

    <div class="msg-toast-stack" id="orgMsgToastStack" aria-live="polite"></div>

    <?php else: ?>
    <div class="org-msg-empty org-msg-empty--panel">
        <i class="fas fa-comments"></i>
        <h3>No conversations yet</h3>
        <p>When applicants message you or you reach out from ATS, chats appear here.</p>
        <?= Html::a('Open ATS', ['application/index'], ['class' => 'org-btn org-btn-primary']) ?>
    </div>
    <?php endif; ?>
</div>
