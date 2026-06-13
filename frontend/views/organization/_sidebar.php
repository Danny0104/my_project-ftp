<?php
/** @var \yii\web\View $this */
/** @var string $navActive */
/** @var string $orgName */
/** @var \common\models\Organization|null $org */
/** @var int $unreadNotificationsCount */
/** @var int $unreadMessagesCount */
/** @var int $supportUnreadCount */

use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\Url;

function orgNavClass(string $key, string $navActive): string
{
    return 'org-nav-link' . ($navActive === $key ? ' is-active' : '');
}

$sections = [
    ['Overview', [
        ['dashboard', 'Dashboard', ['/dashboard/index'], 'fas fa-gauge-high'],
        ['analytics', 'Analytics & Reports', ['/organization/analytics/index'], 'fas fa-chart-line'],
    ]],
    ['Recruitment', [
        ['opportunities', 'Internship Opportunities', ['/position/index'], 'fas fa-briefcase'],
        ['applications', 'Applications (ATS)', ['/application/index'], 'fas fa-layer-group'],
        ['students', 'Students', ['/organization/students/index'], 'fas fa-user-graduate'],
        ['interviews', 'Interviews', ['/organization/interviews/index'], 'fas fa-video'],
        ['messages', 'Messages', ['/message/index'], 'fas fa-comments'],
        ['notifications', 'Notifications', ['/notification/index'], 'fas fa-bell'],
        ['help', 'Help Center', ['/site/contact'], 'fas fa-circle-question'],
    ]],
    ['Programs', [
        ['programs', 'Internship Programs', ['/organization/programs/index'], 'fas fa-diagram-project'],
        ['university', 'University Coordination', ['/organization/coordination/index'], 'fas fa-building-columns'],
        ['reviews', 'Reviews & Feedback', ['/organization/reviews/index'], 'fas fa-star-half-stroke'],
    ]],
    ['Organization', [
        ['company', 'Company Profile', ['/profile/view-organization'], 'fas fa-building'],
        ['team', 'Team Management', ['/organization/team/index'], 'fas fa-users-gear'],
        ['settings', 'Settings & Security', ['/profile/organization'], 'fas fa-shield-halved'],
    ]],
];
?>

<aside class="org-sidebar" id="orgSidebar" aria-label="Organization navigation">
    <div class="org-brand">
        <div class="org-logo"><?= ProfileAvatar::widget(['type' => 'organization', 'organization' => $org ?? null, 'size' => 'md', 'lazy' => false]) ?></div>
        <div class="org-brand-text">
            <strong><?= Html::encode($orgName) ?></strong>
            <span>Organization Panel</span>
        </div>
        <button type="button" class="ft-drawer-close" id="orgSidebarClose" aria-label="Close menu">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <?php foreach ($sections as [$label, $items]): ?>
        <div class="org-nav-section">
            <span class="org-nav-label"><?= Html::encode($label) ?></span>
            <?php foreach ($items as [$key, $title, $route, $icon]): ?>
                <a href="<?= Html::encode(Url::to($route)) ?>" class="<?= Html::encode(orgNavClass($key, $navActive)) ?>">
                    <i class="<?= Html::encode($icon) ?>"></i>
                    <span class="org-nav-text"><?= Html::encode($title) ?></span>
                    <?php if ($key === 'messages' && !empty($unreadMessagesCount) && (int) $unreadMessagesCount > 0): ?>
                        <span class="org-nav-badge"><?= (int) $unreadMessagesCount > 99 ? '99+' : (int) $unreadMessagesCount ?></span>
                    <?php endif; ?>
                    <?php if ($key === 'notifications' && (int) $unreadNotificationsCount > 0): ?>
                        <span class="org-nav-badge"><?= (int) $unreadNotificationsCount > 99 ? '99+' : (int) $unreadNotificationsCount ?></span>
                    <?php endif; ?>
                    <?php if ($key === 'help' && (int) ($supportUnreadCount ?? 0) > 0): ?>
                        <span class="org-nav-badge"><?= (int) $supportUnreadCount > 99 ? '99+' : (int) $supportUnreadCount ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <div class="org-sidebar-footer">
        <div style="font-weight:900;margin-bottom:4px">System status</div>
        <div style="font-size:12px;color:rgba(255,255,255,.72)">
            Operational Â· Real-time updates enabled
        </div>
    </div>
</aside>

