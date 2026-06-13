<?php

/** @var yii\web\View $this */
/** @var common\models\SupportTicket $ticket */
/** @var string $subject */
/** @var string $body */

use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->title = 'New Support Ticket';
?>

<div class="support-compose container-fluid" style="max-width:980px">
    <div class="support-compose__header">
        <div>
            <h1 class="h3 mb-1">Create a support ticket</h1>
            <p class="text-muted mb-0">We typically respond within one business day.</p>
        </div>
        <?= Html::a('Back to Support', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <form method="post" action="<?= Url::to(['create']) ?>" class="support-compose__form">
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>

        <div class="mb-3">
            <label class="form-label">Subject</label>
            <input class="form-control support-input" name="subject" value="<?= Html::encode($subject) ?>" placeholder="Brief summary" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea class="form-control support-input" rows="6" name="body" placeholder="Describe the issue with as much detail as possible" required><?= Html::encode($body) ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary support-btn"><i class="fas fa-paper-plane me-1"></i>Create ticket</button>
    </form>
</div>

