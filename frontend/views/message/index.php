<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $conversations */
/** @var int $unreadMessages */
/** @var int $activeConversationId */
/** @var \common\models\Application[] $pendingApplications */

$this->title = 'Messages';

$isOrganization = !Yii::$app->user->isGuest && Yii::$app->user->identity->role === 'organization';
$markReadUrl = Yii::$app->urlManager->createUrl(['dashboard/mark-notification-read']);
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->getCsrfToken();

$partialVars = compact(
    'conversations', 'unreadMessages', 'activeConversationId', 'pendingApplications',
    'markReadUrl', 'csrfParam', 'csrfToken'
);
?>
<div class="notification-hub messages-hub">
<?php
if ($isOrganization) {
    echo $this->render('_org_hub', $partialVars);
} else {
    echo $this->render('_student_hub', $partialVars);
}
?>
</div>
