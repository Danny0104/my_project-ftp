<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var \yii\web\View $this */

$currentRoute = Yii::$app->controller->route;

$isActive = static function (string $route) use ($currentRoute): string {
    return $currentRoute === $route ? ' active' : '';
};

$isPositionsSection = str_starts_with($currentRoute, 'position/');

$navItems = [
    ['label' => 'Home', 'url' => ['/site/index'], 'active' => $isActive('site/index')],
    ['label' => 'Positions', 'url' => ['/position/index'], 'active' => $isPositionsSection ? ' active' : ''],
    ['label' => 'About', 'url' => ['/site/about'], 'active' => $isActive('site/about')],
    ['label' => 'Contact', 'url' => ['/site/contact'], 'active' => $isActive('site/contact')],
];

$dashboardUrl = ['/dashboard'];
$profileUrl = ['/profile/view-organization'];
if (!Yii::$app->user->isGuest) {
    $authUser = Yii::$app->user->identity;
    $dashboardUrl = ($authUser->role ?? '') === 'student'
        ? ['/dashboard/student']
        : ['/dashboard'];
    $profileUrl = ($authUser->role ?? '') === 'student'
        ? ['/profile/view-student']
        : ['/profile/view-organization'];
}
?>
<header class="site-public-navbar" id="sitePublicNavbar" data-public-navbar>
    <div class="site-public-navbar__progress" id="siteNavbarProgress" role="presentation" aria-hidden="true"></div>
    <div class="site-public-navbar__spotlight" aria-hidden="true"></div>

    <nav class="site-public-navbar__bar navbar navbar-expand-lg navbar-dark" aria-label="Main navigation">
        <div class="container site-public-navbar__container">
            <a class="site-public-navbar__brand navbar-brand" href="<?= Url::to(['/site/index']) ?>">
                <span class="site-public-navbar__brand-icon" aria-hidden="true">
                    <i class="fas fa-graduation-cap"></i>
                </span>
                <span class="site-public-navbar__brand-text">Field Training Platform</span>
            </a>

            <ul class="site-public-navbar__nav site-public-navbar__nav--desktop navbar-nav">
                <?php foreach ($navItems as $item): ?>
                    <li class="nav-item">
                        <a class="site-public-navbar__link nav-link<?= $item['active'] ?>"
                           href="<?= Url::to($item['url']) ?>"><?= Html::encode($item['label']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="site-public-navbar__actions">
                <?php if (Yii::$app->user->isGuest): ?>
                    <a class="site-public-navbar__btn site-public-navbar__btn--ghost"
                       href="<?= Url::to(['/site/login']) ?>">Login</a>
                    <a class="site-public-navbar__btn site-public-navbar__btn--primary"
                       href="<?= Url::to(['/site/signup']) ?>">Sign Up</a>
                <?php else: ?>
                    <div class="dropdown site-public-navbar__user">
                        <a class="site-public-navbar__user-toggle dropdown-toggle nav-link"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1" aria-hidden="true"></i><?= Html::encode($authUser->username) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end site-public-navbar__dropdown">
                            <li><?= Html::a('Dashboard', $dashboardUrl, ['class' => 'dropdown-item']) ?></li>
                            <li><?= Html::a('Profile', $profileUrl, ['class' => 'dropdown-item']) ?></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><?= Html::a('Logout', ['/site/logout'], ['class' => 'dropdown-item', 'data-method' => 'post', 'data-params' => ['type' => 'manual']]) ?></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <button class="site-public-navbar__toggle navbar-toggler"
                    type="button"
                    id="sitePublicNavbarToggle"
                    aria-controls="sitePublicNavbarDrawer"
                    aria-expanded="false"
                    aria-label="Open navigation menu">
                <span class="site-public-navbar__toggle-bar"></span>
                <span class="site-public-navbar__toggle-bar"></span>
                <span class="site-public-navbar__toggle-bar"></span>
            </button>
        </div>
    </nav>
</header>

<div class="site-public-navbar__drawer" id="sitePublicNavbarDrawer" aria-hidden="true">
    <div class="site-public-navbar__drawer-backdrop" id="sitePublicNavbarBackdrop" tabindex="-1"></div>
    <aside class="site-public-navbar__drawer-panel" role="dialog" aria-modal="true" aria-label="Navigation menu">
        <div class="site-public-navbar__drawer-header">
            <a class="site-public-navbar__brand site-public-navbar__brand--drawer" href="<?= Url::to(['/site/index']) ?>">
                <span class="site-public-navbar__brand-icon" aria-hidden="true">
                    <i class="fas fa-graduation-cap"></i>
                </span>
                <span class="site-public-navbar__brand-text">Field Training Platform</span>
            </a>
            <button type="button"
                    class="site-public-navbar__drawer-close"
                    id="sitePublicNavbarClose"
                    aria-label="Close navigation menu">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="site-public-navbar__drawer-nav" aria-label="Mobile navigation">
            <ul class="site-public-navbar__nav site-public-navbar__nav--mobile">
                <?php foreach ($navItems as $item): ?>
                    <li class="nav-item">
                        <a class="site-public-navbar__link nav-link<?= $item['active'] ?>"
                           href="<?= Url::to($item['url']) ?>"><?= Html::encode($item['label']) ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <div class="site-public-navbar__drawer-actions">
            <?php if (Yii::$app->user->isGuest): ?>
                <a class="site-public-navbar__btn site-public-navbar__btn--ghost site-public-navbar__btn--block"
                   href="<?= Url::to(['/site/login']) ?>">Login</a>
                <a class="site-public-navbar__btn site-public-navbar__btn--primary site-public-navbar__btn--block"
                   href="<?= Url::to(['/site/signup']) ?>">Sign Up</a>
            <?php else: ?>
                <a class="site-public-navbar__btn site-public-navbar__btn--ghost site-public-navbar__btn--block"
                   href="<?= Url::to($dashboardUrl) ?>">Dashboard</a>
                <a class="site-public-navbar__btn site-public-navbar__btn--ghost site-public-navbar__btn--block"
                   href="<?= Url::to($profileUrl) ?>">Profile</a>
                <?= Html::a('Logout', ['/site/logout'], [
                    'class' => 'site-public-navbar__btn site-public-navbar__btn--primary site-public-navbar__btn--block',
                    'data-method' => 'post',
                    'data-params' => ['type' => 'manual'],
                ]) ?>
            <?php endif; ?>
        </div>
    </aside>
</div>
