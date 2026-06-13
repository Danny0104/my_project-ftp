<?php
/** @var \common\models\Notification[] $notifications */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int $unreadCount */
/** @var string $markReadUrl */
/** @var string $deleteUrl */
/** @var string $markAllReadUrl */
/** @var string $csrfParam */
/** @var string $csrfToken */

use common\models\Application;
use common\models\Notification;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\StringHelper;

require_once __DIR__ . '/../dashboard/_student_helpers.php';

function spNotifCategory($n): string
{
    if (!empty($n->category)) {
        return $n->category;
    }
    $text = strtolower($n->title . ' ' . $n->message);
    if (strpos($text, 'interview') !== false) return 'interviews';
    if (strpos($text, 'application') !== false || strpos($text, 'approved') !== false || strpos($text, 'rejected') !== false || strpos($text, 'pending') !== false) return 'applications';
    if (strpos($text, 'message') !== false || strpos($text, 'sent you') !== false) return 'messages';
    if (strpos($text, 'announcement') !== false || strpos($text, 'update') !== false) return 'announcements';
    if ($n->sender_type === 'organization') return 'organization';
    return 'system';
}

function spNotifGroupLabel(int $ts): string
{
    $today = strtotime('today');
    $yesterday = strtotime('yesterday');
    if ($ts >= $today) return 'Today';
    if ($ts >= $yesterday) return 'Yesterday';
    return 'Earlier';
}

$grouped = ['Today' => [], 'Yesterday' => [], 'Earlier' => []];
foreach ($notifications as $n) {
    $grouped[spNotifGroupLabel((int) $n->created_at)][] = $n;
}
?>

<div class="sp-page-header sp-page-header-row mb-3">
    <div>
        <h1>Notifications</h1>
        <p>System alerts and platform updates — read-only. Live chat is in Messages.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?= Html::a('<i class="fas fa-envelope me-1"></i> Open Messages', ['message/index'], ['class' => 'sp-btn-ghost']) ?>
        <button type="button" class="sp-btn-ghost" id="archiveReadNotifications">
            <i class="fas fa-box-archive me-1"></i> Archive read
        </button>
        <button type="button" class="sp-btn-primary" id="markAllRead">
            <i class="fas fa-check-double me-1"></i> Mark all read
        </button>
    </div>
</div>

<div class="sp-notif-summary sp-glass mb-3">
    <div class="sp-notif-summary-item">
        <span class="sp-notif-summary-value" data-count="<?= count($notifications) ?>">0</span>
        <span class="sp-notif-summary-label">Total</span>
    </div>
    <div class="sp-notif-summary-item">
        <span class="sp-notif-summary-value sp-notif-summary-value--warn" data-count="<?= (int) $unreadCount ?>">0</span>
        <span class="sp-notif-summary-label">Unread</span>
    </div>
    <div class="sp-notif-summary-item">
        <span class="sp-notif-summary-value"><?= count(array_filter($notifications, fn($n) => spNotifCategory($n) === 'applications')) ?></span>
        <span class="sp-notif-summary-label">Applications</span>
    </div>
    <div class="sp-notif-summary-item">
        <span class="sp-notif-summary-value"><?= count(array_filter($notifications, fn($n) => spNotifCategory($n) === 'interviews')) ?></span>
        <span class="sp-notif-summary-label">Interviews</span>
    </div>
</div>

<div class="sp-sticky-sentinel"></div>
<div class="sp-sticky-bar sp-glass sp-notif-toolbar">
    <div class="sp-notif-categories">
        <button type="button" class="sp-chip is-active" data-notif-filter="all">All</button>
        <button type="button" class="sp-chip" data-notif-filter="applications">Applications</button>
        <button type="button" class="sp-chip" data-notif-filter="interviews">Interviews</button>
        <button type="button" class="sp-chip" data-notif-filter="messages">Messages</button>
        <button type="button" class="sp-chip" data-notif-filter="announcements">Announcements</button>
        <button type="button" class="sp-chip" data-notif-filter="organization">Organizations</button>
        <button type="button" class="sp-chip" data-notif-filter="system">System</button>
    </div>
    <input type="search" class="sp-notif-search" placeholder="Search notifications…" aria-label="Search notifications">
</div>

<?php if (!empty($notifications)): ?>

<div class="sp-notif-feed">
    <?php foreach ($grouped as $label => $items): ?>
        <?php if (empty($items)) continue; ?>
        <div class="sp-notif-group">
            <h2 class="sp-notif-group-title"><?= Html::encode($label) ?></h2>
            <?php foreach ($items as $notification): ?>
                <?php
                $cat = spNotifCategory($notification);
                $isUnread = (int) $notification->is_read === 0;
                $senderType = $notification->sender_type ?: 'system';
                $priority = !empty($notification->priority) ? $notification->priority : 'normal';
                $typeLabel = !empty($notification->notification_type)
                    ? ucfirst(str_replace('_', ' ', $notification->notification_type))
                    : ucfirst(str_replace('_', ' ', $cat));

                $notifAvatar = null;
                if ($senderType === Notification::SENDER_TYPE_ORGANIZATION && empty($isOrganization)) {
                    $notifAvatar = ProfileAvatar::widget([
                        'type' => 'organization',
                        'organization' => $notification->organization,
                        'size' => 'sm',
                    ]);
                } elseif (!empty($isOrganization) && $cat === 'applications' && $notification->related_id) {
                    $relatedApp = Application::find()->with('student')->where(['id' => (int) $notification->related_id])->one();
                    if ($relatedApp && $relatedApp->student) {
                        $notifAvatar = ProfileAvatar::widget([
                            'type' => 'student',
                            'student' => $relatedApp->student,
                            'size' => 'sm',
                        ]);
                    }
                }
                ?>
                <article class="sp-notif-card notification-item <?= $isUnread ? 'unread' : 'read' ?> sp-notif-card--<?= Html::encode($cat) ?> sp-notif-priority--<?= Html::encode($priority) ?>"
                         data-notif-category="<?= Html::encode($cat) ?>"
                         data-notification-id="<?= (int) $notification->id ?>"
                         data-search-text="<?= Html::encode(strtolower($notification->title . ' ' . $notification->message)) ?>">
                    <div class="sp-notif-card-icon sp-notif-card-icon--<?= Html::encode($cat) ?>">
                        <?php if ($notifAvatar): ?>
                            <?= $notifAvatar ?>
                        <?php else: ?>
                            <i class="fas fa-<?= $cat === 'interviews' ? 'video' : ($cat === 'applications' ? 'file-lines' : ($cat === 'messages' ? 'comment-dots' : 'bell')) ?>"></i>
                        <?php endif; ?>
                    </div>
                    <div class="sp-notif-card-body">
                        <div class="sp-notif-card-top">
                            <h3><?= Html::encode($notification->title) ?></h3>
                            <time><?= ftpRelativeTime((int) $notification->created_at) ?></time>
                        </div>
                        <p><?= Html::encode(StringHelper::truncate($notification->message, 140)) ?></p>
                        <div class="sp-notif-card-meta">
                            <span class="sp-tag"><?= Html::encode($typeLabel) ?></span>
                            <span class="sp-tag"><?= Html::encode(ucfirst($senderType)) ?></span>
                            <?php if ($priority !== 'normal'): ?>
                                <span class="sp-tag sp-tag--priority sp-tag--<?= Html::encode($priority) ?>"><?= Html::encode(ucfirst($priority)) ?></span>
                            <?php endif; ?>
                            <?php if ($isUnread): ?><span class="sp-tag sp-tag--unread sp-pulse">Unread</span><?php endif; ?>
                        </div>
                    </div>
                    <div class="sp-notif-card-actions">
                        <?php if ($notification->action_url): ?>
                            <?= Html::a($notification->action_text ?: 'View', $notification->action_url, ['class' => 'sp-btn-ghost btn-sm']) ?>
                        <?php endif; ?>
                        <?php if ($isUnread): ?>
                            <button type="button" class="sp-btn-ghost btn-sm mark-read-btn" data-id="<?= (int) $notification->id ?>">Mark read</button>
                        <?php else: ?>
                            <button type="button" class="sp-btn-ghost btn-sm mark-unread-btn" data-id="<?= (int) $notification->id ?>">Mark unread</button>
                        <?php endif; ?>
                        <button type="button" class="sp-btn-ghost btn-sm text-danger delete-notification-btn" data-id="<?= (int) $notification->id ?>"><i class="fas fa-trash"></i></button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($dataProvider->pagination->pageCount > 1): ?>
    <div class="sp-pagination mt-3"><?= \yii\widgets\LinkPager::widget(['pagination' => $dataProvider->pagination]) ?></div>
<?php endif; ?>

<?php else: ?>
<div class="sp-empty sp-glass">
    <i class="fas fa-bell-slash d-block"></i>
    <h3>You're all caught up</h3>
    <p>New activity about applications, interviews, and opportunities will appear here.</p>
    <?= Html::a('Browse Opportunities', ['position/index'], ['class' => 'sp-btn-primary']) ?>
</div>
<?php endif; ?>
