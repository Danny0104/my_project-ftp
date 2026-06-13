<?php
/** @var yii\web\View $this */
/** @var string $titleValue */
/** @var string $messageValue */

use yii\helpers\Html;
use backend\assets\AdminSupportAsset;

$this->title = 'Support Broadcast';
$this->params['breadcrumbs'][] = ['label' => 'Support Hub', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'support';
AdminSupportAsset::register($this);
?>

<div class="ap-module ap-support-broadcast" style="max-width:860px">
    <div class="ap-exec-hero mb-3">
        <div>
            <h1>Broadcast Announcement</h1>
            <p>Send a support update announcement to all users.</p>
        </div>
    </div>

    <div class="ap-card p-3">
        <form method="post">
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input class="form-control" name="title" value="<?= Html::encode($titleValue) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea class="form-control" name="message" rows="6" required><?= Html::encode($messageValue) ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Send announcement</button>
                <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </form>
    </div>
</div>

