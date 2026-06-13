<?php
/** @var \yii\web\View $this */
/** @var string $orgName */
/** @var \common\models\Organization|null $org */
/** @var int $unreadNotificationsCount */

use common\widgets\ProfileAvatar;
use yii\bootstrap5\Html;
use yii\helpers\Url;
?>

<header class="org-topbar" role="banner">
    <button type="button" class="org-topbar-toggle" id="orgSidebarToggle" aria-label="Toggle navigation">
        <i class="fas fa-bars"></i>
    </button>

    <form class="org-search" action="<?= Url::to(['/position/index']) ?>" method="get" role="search" aria-label="Global search">
        <i class="fas fa-magnifying-glass" aria-hidden="true" style="opacity:.8"></i>
        <input type="search" name="Position[title]" placeholder="Search students, internships, messages&hellip;" autocomplete="off">
        <kbd class="org-kbd" aria-hidden="true">Ctrl K</kbd>
    </form>

    <div class="org-actions">
        <button type="button" class="org-icon-btn" id="orgThemeToggle" title="Toggle theme" aria-label="Toggle theme">
            <i class="fas fa-moon"></i>
        </button>

        <a href="<?= Url::to(['/notification/index']) ?>" class="org-icon-btn" title="Notifications" aria-label="Notifications">
            <i class="fas fa-bell"></i>
            <?php if ((int) $unreadNotificationsCount > 0): ?>
                <span class="org-dot" aria-hidden="true"></span>
            <?php endif; ?>
        </a>

        <div class="dropdown">
            <button class="org-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="org-avatar"><?= ProfileAvatar::widget(['type' => 'organization', 'organization' => $org ?? null, 'size' => 'sm', 'lazy' => false]) ?></span>
                <span class="d-none d-md-inline" style="font-weight:900"><?= Html::encode($orgName) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius:16px;min-width:220px">
                <li><?= Html::a('<i class="fas fa-building me-2"></i>Company Profile', ['/profile/view-organization'], ['class' => 'dropdown-item']) ?></li>
                <li><?= Html::a('<i class="fas fa-shield-halved me-2"></i>Settings & Security', ['/profile/organization'], ['class' => 'dropdown-item']) ?></li>
                <li><hr class="dropdown-divider"></li>
                <li><?= Html::a('<i class="fas fa-right-from-bracket me-2"></i>Sign out', ['/site/logout'], ['class' => 'dropdown-item', 'data-method' => 'post', 'data-params' => ['type' => 'manual']]) ?></li>
            </ul>
        </div>
    </div>
</header>


