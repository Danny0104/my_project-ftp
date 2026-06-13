<?php

use backend\assets\AdminPlatformAsset;
use backend\assets\EnterpriseSaasFinalAsset;
use common\components\SessionSecurity;
use common\models\Admin;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;
use yii\widgets\Breadcrumbs;
use common\widgets\Alert;

AdminPlatformAsset::register($this);
EnterpriseSaasFinalAsset::register($this);
SessionSecurity::registerMonitor($this, true);

$adminTheme = 'light';
if (!Yii::$app->user->isGuest) {
    $adminIdentity = Yii::$app->user->identity;
    if ($adminIdentity instanceof Admin && !empty($adminIdentity->preferences)) {
        $prefs = json_decode($adminIdentity->preferences, true);
        if (is_array($prefs) && !empty($prefs['theme'])) {
            $adminTheme = $prefs['theme'];
        }
    }
}

$this->registerJs(
    'window.ftAdminThemePreference = ' . Json::htmlEncode($adminTheme) . ';',
    View::POS_HEAD
);

$bodyClass = 'ap-admin-layout ap-admin-body admin-dashboard ft-dashboard-root';
if ($adminTheme === 'dark') {
    $bodyClass .= ' ap-dark';
}
if (!empty($this->params['apBodyClass'])) {
    $bodyClass .= ' ' . $this->params['apBodyClass'];
}

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="ft-dashboard-root">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> · Admin · Field Training Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php $this->head() ?>
</head>
<body class="<?= Html::encode($bodyClass) ?>">
<?php $this->beginBody() ?>

<div class="ap-sidebar-overlay" id="apSidebarOverlay"></div>

<div class="ap-app">
    <?= $this->render('_sidebar') ?>

    <div class="ap-main">
        <?= $this->render('_topbar') ?>

        <div class="ap-content-wrap">
            <?php if (!empty($this->params['breadcrumbs'])): ?>
                <nav class="ap-breadcrumbs" aria-label="Breadcrumb">
                    <?= Breadcrumbs::widget([
                        'links' => $this->params['breadcrumbs'],
                        'homeLink' => ['label' => 'Dashboard', 'url' => ['site/dash']],
                        'options' => ['class' => 'ap-breadcrumb-list'],
                        'itemTemplate' => '<li class="ap-breadcrumb-item">{link}</li>',
                        'activeItemTemplate' => '<li class="ap-breadcrumb-item is-active">{link}</li>',
                    ]) ?>
                </nav>
            <?php endif; ?>

            <?= Alert::widget() ?>

            <main class="ap-content ap-page-enter admin-dashboard-scope">
                <?= $content ?>
            </main>
        </div>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
