<?php

/** @var \yii\web\View $this */
/** @var string $content */

use common\components\SessionSecurity;
use common\models\Notification;
use common\services\ProfileCompletionService;
use common\services\SupportService;
use common\models\Student;
use common\widgets\Alert;
use common\widgets\ProfileAvatar;
use frontend\assets\EnterpriseSaasFinalAsset;
use frontend\assets\StudentAsset;
use yii\bootstrap5\Html;
use yii\helpers\Url;

require_once __DIR__ . '/../dashboard/_student_helpers.php';

StudentAsset::register($this);
EnterpriseSaasFinalAsset::register($this);

SessionSecurity::registerMonitor($this, true);

$user = Yii::$app->user->identity;
$student = Student::findOne(['user_id' => Yii::$app->user->id]);
$studentField = $student ? ($student->field_of_study ?? null) : null;
$displayName = ($student && $student->user) ? ($student->user->username ?? 'Student') : ($user ? ($user->username ?? 'Student') : 'Student');

$profileCompletionService = new ProfileCompletionService();
$profileCompletion = $student instanceof Student
    ? $profileCompletionService->dashboardPercent($student)
    : 0;
$isProfileComplete = $profileCompletion >= 100;

$unreadNotificationsCount = Notification::getUnreadCount((int) Yii::$app->user->id);
$unreadMessagesCount = (new \common\services\ChatService())->countUnreadForUser((int) Yii::$app->user->id);
$supportUnreadCount = (new SupportService())->countUnreadForUser((int) Yii::$app->user->id);

$navActive = $this->params['ftpNavActive'] ?? '';

$navSections = [
    ['Main', [
        ['dashboard', 'Dashboard', ['dashboard/student'], 'fas fa-house'],
        ['opportunities', 'Opportunities', ['position/index'], 'fas fa-briefcase'],
        ['applications', 'Applications', ['application/index'], 'fas fa-file-lines'],
        ['interviews', 'Interviews', ['interview/index'], 'fas fa-video'],
    ]],
    ['Communication', [
        ['messages', 'Messages', ['message/index'], 'fas fa-envelope', $unreadMessagesCount],
        ['notifications', 'Notifications', ['notification/index'], 'fas fa-bell', $unreadNotificationsCount],
    ]],
    ['Account', [
        ['profile', 'Profile', ['profile/view-student'], 'fas fa-user'],
        ['edit-profile', 'Edit Profile', ['profile/edit-profile'], 'fas fa-user-pen'],
        ['settings', 'Settings', ['profile/settings'], 'fas fa-gear'],
    ]],
    ['Support', [
        ['help', 'Help Center', ['site/contact'], 'fas fa-circle-question', $supportUnreadCount],
    ]],
];

$ringOffset = 126 - (126 * $profileCompletion / 100);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="ft-dashboard-root">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> | Field Training Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php $this->head() ?>
</head>
<body class="ftp-dashboard-layout ftp-student-body student-dashboard">
<?php $this->beginBody() ?>

<svg width="0" height="0" aria-hidden="true" style="position:absolute">
    <defs>
        <linearGradient id="ftpRingGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#38bdf8"/>
            <stop offset="100%" stop-color="#6366f1"/>
        </linearGradient>
    </defs>
</svg>

<div class="ftp-sidebar-overlay" id="ftpSidebarOverlay" aria-hidden="true"></div>

<div class="ftp-app">
    <aside class="ftp-sidebar" id="ftpSidebar" aria-label="Student navigation">
        <div class="ftp-sidebar-brand">
            <div class="ftp-sidebar-logo"><i class="fas fa-graduation-cap" aria-hidden="true"></i></div>
            <div class="ftp-sidebar-brand-text">
                <span>Field Training</span>
                <span>Career Platform</span>
            </div>
            <button type="button" class="ft-drawer-close" id="ftpSidebarClose" aria-label="Close menu">
                <i class="fas fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="ftp-sidebar-nav">
            <?php foreach ($navSections as [$sectionLabel, $items]): ?>
                <span class="ftp-nav-section-label"><?= Html::encode($sectionLabel) ?></span>
                <ul class="ftp-sidebar-nav-list">
                    <?php foreach ($items as $item):
                        $key = $item[0];
                        $title = $item[1];
                        $route = $item[2];
                        $icon = $item[3];
                        $badge = $item[4] ?? 0;
                        ?>
                        <li>
                            <a href="<?= Url::to($route) ?>"
                               class="ftp-sidebar-link <?= $navActive === $key ? 'active' : '' ?>"
                               data-ftp-tooltip="<?= Html::encode($title) ?>">
                                <i class="<?= Html::encode($icon) ?>" aria-hidden="true"></i>
                                <span class="ftp-nav-text"><?= Html::encode($title) ?></span>
                                <?php if (in_array($key, ['messages', 'notifications', 'help'], true)): ?>
                                    <span class="badge-count"
                                          data-nav-badge="<?= Html::encode($key) ?>"
                                          <?= $badge <= 0 ? 'hidden' : '' ?>><?= $badge > 0 ? (int) $badge : '' ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </nav>

        <div class="ftp-sidebar-profile<?= $isProfileComplete ? ' ftp-sidebar-profile--complete' : '' ?>">
            <div class="ftp-profile-ring-wrap">
                <div class="ftp-profile-ring" aria-hidden="true">
                    <?php if (!$isProfileComplete): ?>
                        <svg viewBox="0 0 48 48">
                            <circle class="ftp-profile-ring-bg" cx="24" cy="24" r="20"/>
                            <circle class="ftp-profile-ring-fill" cx="24" cy="24" r="20"
                                    stroke-dasharray="126"
                                    stroke-dashoffset="<?= (int) $ringOffset ?>"/>
                        </svg>
                    <?php endif; ?>
                    <span class="ftp-profile-ring-avatar"><?= ProfileAvatar::widget(['type' => 'student', 'student' => $student, 'size' => 'sm', 'lazy' => false, 'fillSlot' => true]) ?></span>
                </div>
                <div class="ftp-sidebar-profile-meta">
                    <strong><?= Html::encode($displayName) ?></strong>
                    <span><?= Html::encode($studentField ?: ($isProfileComplete ? 'Student' : 'Complete your profile')) ?></span>
                    <?php if (!$isProfileComplete): ?>
                        <div class="ftp-profile-ring-label">Profile <?= $profileCompletion ?>% complete</div>
                    <?php endif; ?>
                </div>
            </div>
            <button type="button" class="ftp-sidebar-collapse-btn" id="ftpSidebarCollapse" aria-label="Collapse sidebar">
                <i class="fas fa-angles-left" id="ftpSidebarCollapseIcon"></i>
                <span class="ftp-nav-text ftp-sidebar-collapse-label">Collapse</span>
            </button>
        </div>
    </aside>

    <div class="ftp-main">
        <header class="ftp-topbar">
            <button type="button" class="ftp-topbar-toggle" id="ftpSidebarToggle" aria-label="Open menu">
                <i class="fas fa-bars"></i>
            </button>

            <form class="ftp-search" id="ftpSearchForm" action="<?= Url::to(['position/index']) ?>" method="get" role="search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" name="Position[title]" placeholder="Search internships, companies, locations…" aria-label="Search opportunities">
            </form>

            <div class="ftp-topbar-actions">
                <?= Html::a('<i class="fas fa-plus"></i>', ['position/index'], [
                    'class' => 'ftp-icon-btn d-none d-md-flex',
                    'title' => 'Browse opportunities',
                ]) ?>

                <button type="button" class="ftp-theme-btn" id="ftpThemeToggle" title="Toggle theme" aria-label="Toggle theme">
                    <i class="fas fa-moon" id="ftpThemeIcon"></i>
                </button>

                <a href="<?= Url::to(['notification/index']) ?>" class="ftp-icon-btn" title="Notifications">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadNotificationsCount > 0): ?>
                        <span class="dot" aria-hidden="true"></span>
                    <?php endif; ?>
                </a>

                <div class="dropdown">
                    <button type="button" class="ftp-profile-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="ftp-avatar"><?= ProfileAvatar::widget(['type' => 'student', 'student' => $student, 'size' => 'xs', 'lazy' => false]) ?></span>
                        <span class="d-none d-md-inline"><?= Html::encode($displayName) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius:12px;min-width:200px">
                        <li><?= Html::a('<i class="fas fa-user me-2"></i>My Profile', ['profile/view-student'], ['class' => 'dropdown-item']) ?></li>
                        <li><?= Html::a('<i class="fas fa-pen me-2"></i>Edit Profile', ['profile/edit-profile'], ['class' => 'dropdown-item']) ?></li>
                        <li><?= Html::a('<i class="fas fa-gear me-2"></i>Settings', ['profile/settings'], ['class' => 'dropdown-item']) ?></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><?= Html::a('<i class="fas fa-right-from-bracket me-2"></i>Sign out', ['site/logout'], ['class' => 'dropdown-item', 'data-method' => 'post', 'data-params' => ['type' => 'manual']]) ?></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="ftp-content ftp-page-enter student-dashboard-scope">
            <?= Alert::widget() ?>
            <?= $content ?>
        </main>
    </div>
</div>

<div class="ftp-toast-stack" id="ftpToastStack" aria-live="polite"></div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
