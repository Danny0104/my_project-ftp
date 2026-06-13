<?php
/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$admin = Yii::$app->user->identity;
$initials = strtoupper(substr($admin->username ?? 'A', 0, 2));

$unreadCount = 0;
if (!Yii::$app->user->isGuest) {
    $unreadCount = (int) \common\models\Notification::find()
        ->where(['is_read' => 0])
        ->count();
}
?>
<header class="ap-topbar">
    <button type="button" class="ap-topbar-toggle" id="apSidebarToggle" aria-label="Toggle navigation menu">
        <i class="fas fa-bars"></i>
    </button>

    <form class="ap-global-search" action="<?= Url::to(['site/dash']) ?>" method="get" role="search">
        <i class="fas fa-search" aria-hidden="true"></i>
        <input type="search" name="q" placeholder="Search students, organizations, applications…" aria-label="Global search" autocomplete="off">
        <kbd class="ap-kbd">Ctrl K</kbd>
    </form>

    <div class="ap-topbar-actions">
        <a href="<?= Url::to(['site/analytics']) ?>" class="ap-icon-btn ap-topbar-quick" title="Analytics">
            <i class="fas fa-chart-line"></i>
        </a>
        <a href="<?= Url::to(['site/approvals']) ?>" class="ap-icon-btn ap-topbar-quick" title="Approvals">
            <i class="fas fa-circle-check"></i>
        </a>

        <button type="button" class="ap-icon-btn" id="apThemeToggle" title="Toggle theme" aria-label="Toggle theme">
            <i class="fas fa-moon"></i>
        </button>

        <a href="<?= Url::to(['notification/index']) ?>" class="ap-icon-btn" title="Notifications">
            <i class="fas fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="ap-badge-dot"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
            <?php endif; ?>
        </a>

        <div class="ap-profile-menu">
            <button type="button" class="ap-profile-btn" id="apProfileMenuBtn" aria-expanded="false" aria-haspopup="true">
                <span class="ap-avatar"><?= Html::encode($initials) ?></span>
                <span class="ap-profile-name d-none d-md-inline"><?= Html::encode($admin->username ?? 'Admin') ?></span>
                <i class="fas fa-chevron-down ap-profile-chevron" aria-hidden="true"></i>
            </button>
            <div class="ap-profile-dropdown" id="apProfileDropdown" hidden role="menu">
                <?= Html::a('<i class="fas fa-user me-2"></i>Profile', ['admin/index'], ['role' => 'menuitem']) ?>
                <?= Html::a('<i class="fas fa-gear me-2"></i>Settings', ['site/settings'], ['role' => 'menuitem']) ?>
                <hr>
                <?= Html::beginForm(['site/logout'], 'post') .
                    Html::hiddenInput('type', 'manual') .
                    Html::submitButton('<i class="fas fa-right-from-bracket me-2"></i>Sign out', ['class' => 'ap-dropdown-action']) .
                    Html::endForm() ?>
            </div>
        </div>
    </div>
</header>
