<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var \common\models\Notification[] $notifications */
/** @var string $activeCategory */

$this->title = 'Notifications';

$unreadCount = \common\models\Notification::getUnreadCount((int) Yii::$app->user->id);

$markReadUrl = Yii::$app->urlManager->createUrl(['dashboard/mark-notification-read']);
$markUnreadUrl = Yii::$app->urlManager->createUrl(['dashboard/mark-notification-unread']);
$deleteUrl = Yii::$app->urlManager->createUrl(['dashboard/delete-notification']);
$markAllReadUrl = Yii::$app->urlManager->createUrl(['dashboard/mark-all-notifications-read']);
$archiveReadUrl = Yii::$app->urlManager->createUrl(['notification/archive-read']);
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->getCsrfToken();

$isOrganization = !Yii::$app->user->isGuest && Yii::$app->user->identity->role === 'organization';

$partialVars = compact(
    'notifications', 'dataProvider', 'unreadCount', 'activeCategory',
    'markReadUrl', 'markUnreadUrl', 'deleteUrl', 'markAllReadUrl', 'archiveReadUrl',
    'csrfParam', 'csrfToken', 'isOrganization'
);
?>
<div class="notification-hub notifications-only">
    <?= $this->render('_notifications_feed', $partialVars) ?>
</div>
<?php
$this->registerJs(<<<JS
\$('#markAllRead').on('click', function() {
    \$.ajax({
        url: '{$markAllReadUrl}',
        type: 'POST',
        data: { '{$csrfParam}': '{$csrfToken}' },
        success: function(response) {
            if (response.success) location.reload();
        }
    });
});

\$('#archiveReadNotifications').on('click', function() {
    \$.ajax({
        url: '{$archiveReadUrl}',
        type: 'POST',
        data: { '{$csrfParam}': '{$csrfToken}' },
        success: function(response) {
            if (response.success) location.reload();
        }
    });
});

\$(document).on('click', '.mark-read-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var id = \$(this).data('id');
    var card = \$(this).closest('.sp-notif-card');
    \$.ajax({
        url: '{$markReadUrl}',
        type: 'POST',
        data: { notification_id: id, '{$csrfParam}': '{$csrfToken}' },
        success: function(response) {
            if (response.success) {
                card.removeClass('unread').addClass('read');
                card.find('.sp-tag--unread').remove();
                card.find('.mark-read-btn').remove();
            }
        }
    });
});

\$(document).on('click', '.mark-unread-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var id = \$(this).data('id');
    \$.ajax({
        url: '{$markUnreadUrl}',
        type: 'POST',
        data: { notification_id: id, '{$csrfParam}': '{$csrfToken}' },
        success: function(response) {
            if (response.success) location.reload();
        }
    });
});

\$(document).on('click', '.delete-notification-btn', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var id = \$(this).data('id');
    var card = \$(this).closest('.sp-notif-card');
    if (!confirm('Archive this notification?')) return;
    \$.ajax({
        url: '{$deleteUrl}',
        type: 'POST',
        data: { notification_id: id, '{$csrfParam}': '{$csrfToken}' },
        success: function(response) {
            if (response.success) {
                card.addClass('sp-notif-removing');
                setTimeout(function() { card.remove(); }, 280);
            }
        }
    });
});
JS
);
