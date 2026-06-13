<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Send Announcement';
$this->params['breadcrumbs'][] = ['label' => 'Notifications', 'url' => ['notification/index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'announcements';
?>

<?= $this->render('../layouts/_page_header', [
    'title' => 'Broadcast announcement',
    'subtitle' => 'Send platform-wide notifications to students and organizations',
]) ?>

<div class="ap-dash-grid">
    <div class="ap-panel ap-glass">
        <?php $form = ActiveForm::begin(); ?>
        <div class="mb-3">
            <label class="form-label" for="title">Announcement title *</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="target_role">Target audience</label>
            <select class="form-control" id="target_role" name="target_role">
                <option value="all">All users</option>
                <option value="student">Students only</option>
                <option value="organization">Organizations only</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label" for="message">Message *</label>
            <textarea class="form-control" id="message" name="message" rows="6" required placeholder="Enter your announcement…"></textarea>
        </div>
        <p class="text-muted small"><i class="fas fa-info-circle me-1"></i>Delivered instantly to user notification centers.</p>
        <div class="d-flex gap-2 mt-3">
            <button type="submit" class="ap-btn ap-btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
            <?= Html::a('Cancel', ['notification/index'], ['class' => 'ap-btn ap-btn-ghost']) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
    <div class="ap-panel ap-glass">
        <h3 style="margin:0 0 16px;font-size:1rem">Preview</h3>
        <div class="ap-widget-item" style="flex-direction:column;align-items:flex-start">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="ap-tag ap-tag--info">Admin</span>
                <strong id="preview-title">Announcement title</strong>
            </div>
            <p id="preview-message" class="mb-0 text-muted">Your message preview appears here…</p>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<'JS'
document.getElementById('title').addEventListener('input', function() {
    document.getElementById('preview-title').textContent = this.value || 'Announcement title';
});
document.getElementById('message').addEventListener('input', function() {
    document.getElementById('preview-message').textContent = this.value || 'Your message preview appears here…';
});
JS
);
?>
