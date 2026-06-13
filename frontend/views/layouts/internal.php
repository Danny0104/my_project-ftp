<?php

/** @var \yii\web\View $this */
/** @var string $content */

use common\components\SessionSecurity;
use common\widgets\Alert;
use frontend\assets\AppAsset;
use frontend\assets\EnterpriseSaasFinalAsset;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Url;

AppAsset::register($this);
EnterpriseSaasFinalAsset::register($this);
$this->registerCssFile('@web/css/design-tokens.css');
$this->registerCssFile('@web/css/internal-layout.css');

SessionSecurity::registerMonitor($this, true);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="ft-dashboard-root">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="ft-internal-layout d-flex flex-column h-100">
<?php $this->beginBody() ?>

<header>
    <?php
    $user = Yii::$app->user->isGuest ? null : Yii::$app->user->identity;
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar navbar-expand-md navbar-custom fixed-top',
        ],
    ]);
    $menuItems = [
        ['label' => 'Home', 'url' => ['/site/index']],
        ['label' => 'About', 'url' => ['/site/about']],
        ['label' => 'Contact', 'url' => ['/site/contact']],
    ];
    if (Yii::$app->user->isGuest) {
        $menuItems[] = ['label' => 'Signup', 'url' => ['/site/signup']];
    } else {
        if ($user->status == \common\models\User::STATUS_ACTIVE) {
            $dashboardUrl = ['/dashboard/index'];
            if ($user->role === 'student') {
                $dashboardUrl = ['/dashboard/student'];
            } elseif ($user->role === 'organization') {
                $dashboardUrl = ['/dashboard/index'];
            }

            $accountItems = [
                ['label' => 'Dashboard', 'url' => $dashboardUrl],
            ];
            if ($user->role === 'student') {
                $accountItems[] = ['label' => 'View Profile', 'url' => ['/profile/view-student']];
                $accountItems[] = ['label' => 'Edit Profile', 'url' => ['/profile/student']];
            } elseif ($user->role === 'organization') {
                $accountItems[] = ['label' => 'View Profile', 'url' => ['/profile/view-organization']];
                $accountItems[] = ['label' => 'Edit Profile', 'url' => ['/profile/organization']];
            } elseif ($user->role === 'admin') {
                $accountItems[] = ['label' => 'Admin', 'url' => ['/admin/index']];
            }
            $accountItems[] = ['label' => 'Applications', 'url' => ['/application/index']];
            $accountItems[] = [
                'label' => 'Notifications <span id="notif-badge" class="badge bg-danger ms-1" style="display:none"></span>',
                'url' => ['/notification/index'],
                'encode' => false,
            ];
            $menuItems[] = [
                'label' => 'Account',
                'items' => $accountItems,
            ];
        }
    }

    echo Nav::widget([
        'options' => ['class' => 'navbar-nav me-auto mb-2 mb-md-0'],
        'items' => $menuItems,
    ]);
    if (Yii::$app->user->isGuest) {
        echo Html::tag('div', Html::a('Login', ['/site/login'], ['class' => ['btn btn-link login text-decoration-none']]), ['class' => ['d-flex']]);
    } else {
        echo Html::beginForm(['/site/logout'], 'post', ['class' => 'd-flex'])
            . Html::hiddenInput('type', 'manual')
            . Html::submitButton(
                'Logout (' . Yii::$app->user->identity->username . ')',
                ['class' => 'btn btn-link logout text-decoration-none']
            )
            . Html::endForm();
    }
    NavBar::end();
    ?>
</header>

<main role="main" class="flex-shrink-0 ft-internal-main">
    <div class="container">
        <?= Breadcrumbs::widget([
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<footer class="footer mt-auto py-3 text-muted">
    <div class="container">
        <p class="float-start">&copy; <?= Html::encode(Yii::$app->name) ?> <?= date('Y') ?></p>
    </div>
</footer>

<?php $this->endBody() ?>
<?php
$this->registerJs(<<<'JS'
function updateNotifBadge() {
    if (typeof $ === 'undefined') return;
    $.getJSON('/notification/unread-count', function(data) {
        var badge = $('#notif-badge');
        if (!badge.length) return;
        if (data.count > 0) {
            badge.text(data.count).show();
        } else {
            badge.hide();
        }
    });
}
$(function() {
    updateNotifBadge();
    setInterval(updateNotifBadge, 10000);
});
JS
);
?>
</body>
</html>
<?php $this->endPage();
