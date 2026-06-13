<?php

/** @var \yii\web\View $this */
/** @var string $content */

use common\components\SessionSecurity;
use yii\helpers\Json;
use yii\web\View;
use common\models\Notification;
use common\models\Organization;
use common\services\SupportService;
use common\widgets\Alert;
use frontend\assets\EnterpriseSaasFinalAsset;
use frontend\assets\OrganizationAsset;
use yii\bootstrap5\Html;
use yii\helpers\Url;

OrganizationAsset::register($this);
EnterpriseSaasFinalAsset::register($this);

SessionSecurity::registerMonitor($this, true);

$this->registerJs(
    'window.ftOrgApi = ' . Json::htmlEncode([
        'updateStage' => Url::to(['/application/update-stage']),
        'deletePosition' => Url::to(['/position/delete']),
        'togglePositionStatus' => Url::to(['/position/toggle-status']),
    ]) . ';',
    View::POS_HEAD
);

$user = Yii::$app->user->identity;
$org = null;
if ($user && !Yii::$app->user->isGuest && $user->role === 'organization') {
    $org = Organization::findOrCreateForUserId((int) Yii::$app->user->id);
}

$orgName = $org->name ?? ($user ? ($user->organization_name ?? $user->username ?? 'Organization') : 'Organization');

$unreadNotificationsCount = 0;
$unreadMessagesCount = 0;
$supportUnreadCount = 0;
if (!Yii::$app->user->isGuest) {
    $unreadNotificationsCount = Notification::getUnreadCount((int) Yii::$app->user->id);
    $unreadMessagesCount = (new \common\services\ChatService())->countUnreadForUser((int) Yii::$app->user->id);
    $supportUnreadCount = (new SupportService())->countUnreadForUser((int) Yii::$app->user->id);
}

$navActive = $this->params['orgNavActive'] ?? 'dashboard';
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="ft-dashboard-root" data-theme="dark">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> · Organization · Field Training Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <?php $this->head() ?>
</head>
<body class="org-dashboard-layout org-body organization-dashboard">
<?php $this->beginBody() ?>

<div class="org-sidebar-overlay" id="orgSidebarOverlay" aria-hidden="true"></div>

<div class="org-app">
    <?= $this->render('@frontend/views/organization/_sidebar', [
        'navActive' => $navActive,
        'org' => $org,
        'orgName' => $orgName,
        'unreadNotificationsCount' => $unreadNotificationsCount,
        'unreadMessagesCount' => $unreadMessagesCount,
        'supportUnreadCount' => $supportUnreadCount,
    ]) ?>

    <div class="org-main">
        <?= $this->render('@frontend/views/organization/_topbar', [
            'org' => $org,
            'orgName' => $orgName,
            'unreadNotificationsCount' => $unreadNotificationsCount,
        ]) ?>

        <div class="org-content org-page-enter organization-dashboard-scope <?= Html::encode($this->params['orgContentClass'] ?? '') ?>">
            <?= Alert::widget() ?>
            <?= $content ?>
        </div>
    </div>
</div>

<div class="org-toast-stack" id="orgToastStack" aria-live="polite" aria-relevant="additions"></div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

