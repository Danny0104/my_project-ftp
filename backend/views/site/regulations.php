<?php

use common\models\PlatformRegulation;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var common\models\PlatformRegulation[] $regulations */

$this->title = 'Regulations & Policy';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'regulations';

$csrf = Yii::$app->request->csrfParam;
$token = Yii::$app->request->getCsrfToken();
?>

<div class="ap-module">
<?= $this->render('../layouts/_page_header', [
    'title' => 'Policy & compliance center',
    'subtitle' => 'Regulation categories, version tracking, and eligibility rules',
    'actions' => [
        Html::button('<i class="fas fa-plus"></i> Add Regulation', ['class' => 'ap-btn ap-btn-primary', 'data-ap-open-modal' => 'regulationForm']),
    ],
]) ?>

<?= $this->render('../layouts/partials/_kpi_grid', [
    'cards' => [
        ['label' => 'Active policies', 'value' => count($regulations), 'icon' => 'fa-scale-balanced', 'accent' => 'blue'],
        ['label' => 'Policy keys', 'value' => count(array_unique(array_map(static fn($r) => $r->key, $regulations))), 'icon' => 'fa-folder', 'accent' => 'purple'],
        ['label' => 'Compliance', 'value' => 100, 'icon' => 'fa-shield', 'accent' => 'green', 'suffix' => '%'],
        ['label' => 'Documented', 'value' => count(array_filter($regulations, static fn($r) => !empty($r->description))), 'icon' => 'fa-file-lines', 'accent' => 'amber'],
    ],
]) ?>

<div class="ap-card-grid">
    <?php foreach ($regulations as $reg): ?>
        <div class="ap-panel ap-glass">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h3 style="margin:0;font-size:0.95rem;font-weight:600"><?= Html::encode($reg->key) ?></h3>
                <span class="ap-tag ap-tag--info">Active</span>
            </div>
            <p style="font-size:1.25rem;font-weight:700;margin:8px 0"><?= Html::encode($reg->value) ?></p>
            <?php if ($reg->description): ?>
                <p class="text-muted small mb-0"><?= Html::encode($reg->description) ?></p>
            <?php endif; ?>
            <p class="text-muted small mt-2 mb-3">Updated <?= date('M d, Y', $reg->updated_at) ?></p>
            <div class="d-flex gap-2">
                <?= Html::button('Edit', [
                    'class' => 'ap-btn ap-btn-ghost ap-btn-sm',
                    'data-ap-open-modal' => 'regulationForm',
                    'data-prefill-id' => $reg->id,
                    'data-prefill-key' => $reg->key,
                    'data-prefill-value' => $reg->value,
                    'data-prefill-description' => $reg->description,
                ]) ?>
                <?= Html::button('Delete', [
                    'class' => 'ap-btn ap-btn-ghost ap-btn-sm',
                    'data-ap-delete' => Url::to(['site/delete-regulation']),
                    'data-id' => $reg->id,
                    'data-confirm' => 'Delete this regulation?',
                ]) ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="ap-panel ap-glass mt-4">
    <h3 style="margin:0 0 12px;font-size:1rem"><i class="fas fa-flask me-2"></i>Policy simulator</h3>
    <p class="text-muted mb-3">Student–opportunity eligibility is enforced on the frontend using these regulations.</p>
    <?= Html::a('View audit logs', ['site/audit-logs'], ['class' => 'ap-btn ap-btn-ghost']) ?>
    <?= Html::a('Manage fields of study', ['site/faculties'], ['class' => 'ap-btn ap-btn-primary']) ?>
</div>
</div>

<div class="ap-modal-backdrop" data-ap-modal="regulationForm">
    <div class="ap-modal ap-glass">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 style="margin:0;font-size:1.1rem">Regulation</h2>
            <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" data-ap-close-modal>&times;</button>
        </div>
        <form data-ap-ajax-form="<?= Url::to(['site/save-regulation']) ?>">
            <input type="hidden" name="<?= $csrf ?>" value="<?= $token ?>">
            <input type="hidden" name="PlatformRegulation[id]" value="" data-ap-prefill-target="id">
            <div class="mb-3">
                <label class="form-label">Key</label>
                <input class="form-control" name="PlatformRegulation[key]" required data-ap-prefill-target="key">
            </div>
            <div class="mb-3">
                <label class="form-label">Value</label>
                <textarea class="form-control" name="PlatformRegulation[value]" rows="2" required data-ap-prefill-target="value"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="PlatformRegulation[description]" rows="3" data-ap-prefill-target="description"></textarea>
            </div>
            <button type="submit" class="ap-btn ap-btn-primary">Save regulation</button>
        </form>
    </div>
</div>
