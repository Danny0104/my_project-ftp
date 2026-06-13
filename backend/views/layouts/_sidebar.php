<?php
/** @var yii\web\View $this */

use common\services\SupportService;
use yii\helpers\Html;
use yii\helpers\Url;

$supportUnreadCount = !Yii::$app->user->isGuest ? (new SupportService())->countUnreadForAdmin() : 0;

$ctrl = Yii::$app->controller->id;
$action = Yii::$app->controller->action->id;
$navActive = $this->params['apNavActive'] ?? '';

$admin = Yii::$app->user->identity;
$adminName = $admin->username ?? 'Administrator';
$adminInitials = strtoupper(substr($adminName, 0, 2));

function apNavClass(string $key, string $ctrl, string $action, string $navActive, ?string $ctrlMatch = null): string
{
    $active = ($navActive === $key) || ($ctrlMatch && $ctrl === $ctrlMatch);
    if ($key === 'dashboard' && $ctrl === 'site' && $action === 'dash') {
        $active = true;
    }
    return 'ap-nav-link' . ($active ? ' is-active' : '');
}

$sections = [
    ['Overview', [
        ['dashboard', 'Dashboard', 'site/dash', 'fas fa-chart-pie', null],
        ['analytics', 'Reports & Analytics', 'site/analytics', 'fas fa-chart-line', 'site'],
    ]],
    ['Management', [
        ['students', 'Students', 'student/index', 'fas fa-user-graduate', 'student'],
        ['organizations', 'Organizations', 'organization/index', 'fas fa-building', 'organization'],
        ['opportunities', 'Opportunities', 'position/index', 'fas fa-briefcase', 'position'],
        ['applications', 'Applications', 'application/index', 'fas fa-file-lines', 'application'],
        ['users', 'Users', 'user/index', 'fas fa-users', 'user'],
        ['admins', 'Administrators', 'admin/index', 'fas fa-user-shield', 'admin'],
    ]],
    ['Academic', [
        ['faculties', 'Faculties & Fields', 'site/faculties', 'fas fa-sitemap', null],
        ['regulations', 'Regulations', 'site/regulations', 'fas fa-scale-balanced', null],
        ['approvals', 'Approval Center', 'site/approvals', 'fas fa-circle-check', null],
    ]],
    ['Communication', [
        ['notifications', 'Notifications', 'notification/index', 'fas fa-bell', 'notification'],
        ['announcements', 'Announcements', 'site/send-announcement', 'fas fa-bullhorn', null],
        ['support', 'Support Inbox', 'support/index', 'fas fa-headset', 'support', $supportUnreadCount],
    ]],
    ['System', [
        ['audit', 'Audit Logs', 'site/audit-logs', 'fas fa-clipboard-list', null],
        ['settings', 'System Settings', 'site/settings', 'fas fa-gear', null],
    ]],
];
?>
<aside class="ap-sidebar" id="apSidebar" aria-label="Admin navigation">
    <div class="ap-sidebar-brand">
        <div class="ap-sidebar-logo" aria-hidden="true"><i class="fas fa-graduation-cap"></i></div>
        <div class="ap-sidebar-brand-text">
            <strong>Field Training</strong>
            <span>Admin Console</span>
        </div>
    </div>

    <nav class="ap-sidebar-nav">
        <?php foreach ($sections as [$label, $items]): ?>
            <div class="ap-nav-section">
                <span class="ap-nav-section-label"><?= Html::encode($label) ?></span>
                <?php foreach ($items as $item):
                    [$key, $title, $route, $icon, $ctrlMatch] = $item;
                    $badge = $item[5] ?? 0;
                    ?>
                    <a href="<?= Url::to([$route]) ?>"
                       class="<?= apNavClass($key, $ctrl, $action, $navActive, $ctrlMatch) ?>"
                       data-ap-nav-tooltip="<?= Html::encode($title) ?>">
                        <i class="<?= Html::encode($icon) ?>" aria-hidden="true"></i>
                        <span class="ap-nav-text"><?= Html::encode($title) ?></span>
                        <?php if ($key === 'support' && (int) $badge > 0): ?>
                            <span class="ap-nav-badge"><?= (int) $badge > 99 ? '99+' : (int) $badge ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="ap-sidebar-footer">
        <div class="ap-sidebar-user">
            <span class="ap-sidebar-user-avatar"><?= Html::encode($adminInitials) ?></span>
            <div class="ap-sidebar-user-meta">
                <strong><?= Html::encode($adminName) ?></strong>
                <span>Platform administrator</span>
            </div>
        </div>
        <div class="ap-sidebar-health">
            <span class="ap-health-dot" aria-hidden="true"></span>
            <span>System operational</span>
        </div>
        <button type="button" class="ap-sidebar-collapse" id="apSidebarCollapse" aria-label="Collapse sidebar">
            <i class="fas fa-angles-left" aria-hidden="true"></i>
        </button>
    </div>
</aside>
