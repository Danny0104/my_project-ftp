<?php
// Legacy organization dashboard prototype removed.
// The new organization panel lives in `frontend/views/dashboard/organization.php`
// and is served from `DashboardController::actionIndex()` for organization users.
?>

<div class="container py-4">
    <div class="alert alert-info">
        Dashboard has moved to the new organization panel.
        <?= \yii\helpers\Html::a('Open Organization Dashboard', ['dashboard/index'], ['class' => 'alert-link']) ?>
    </div>
</div>