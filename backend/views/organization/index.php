<?php

use common\models\Application;
use common\models\Position;
use yii\helpers\Html;
use yii\widgets\LinkPager;

/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Organizations';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'organizations';

$verified = 0;
foreach ($dataProvider->getModels() as $m) {
    if (!empty($m->website)) {
        $verified++;
    }
}
?>

<div class="ap-module">
    <?= $this->render('../layouts/_page_header', [
        'title' => 'Partnership management',
        'subtitle' => 'Organization analytics, hiring trends, and internship performance',
        'actions' => [
            Html::a('<i class="fas fa-plus"></i> Add organization', ['create'], ['class' => 'ap-btn ap-btn-primary']),
        ],
    ]) ?>

    <?= $this->render('../layouts/partials/_kpi_grid', [
        'cards' => [
            ['label' => 'Total partners', 'value' => (int) $dataProvider->totalCount, 'icon' => 'fa-building', 'accent' => 'blue'],
            ['label' => 'With website', 'value' => $verified, 'icon' => 'fa-badge-check', 'accent' => 'green', 'trend' => 'Verified'],
            ['label' => 'Open positions', 'value' => (int) Position::find()->where(['or', ['status' => 'Active'], ['status' => 'active']])->count(), 'icon' => 'fa-briefcase', 'accent' => 'purple'],
            ['label' => 'Applications', 'value' => (int) Application::find()->count(), 'icon' => 'fa-chart-line', 'accent' => 'teal'],
        ],
    ]) ?>

    <?= $this->render('../layouts/partials/_module_toolbar', [
        'searchPlaceholder' => 'Search organizations…',
        'searchId' => 'apOrgSearch',
        'searchTarget' => 'apOrgGrid',
        'viewToggleId' => 'apOrgGrid',
    ]) ?>

    <?php if ($dataProvider->getTotalCount() > 0): ?>
        <div class="ap-org-grid ap-card-grid ap-view--grid" id="apOrgGrid">
            <?php foreach ($dataProvider->getModels() as $model):
                $posCount = Position::find()->where(['organization_id' => $model->id])->count();
                $appCount = (int) Application::find()
                    ->alias('a')
                    ->innerJoin(['p' => Position::tableName()], 'p.id = a.position_id AND p.organization_id = :oid', [':oid' => $model->id])
                    ->count();
                $score = min(100, 20 + $posCount * 15 + ($model->website ? 25 : 0) + min(40, $appCount * 2));
                ?>
                <article class="ap-org-card ap-glass"
                         data-search="<?= Html::encode(strtolower($model->name . ' ' . $model->location . ' ' . ($model->description ?? ''))) ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h3 style="margin:0;font-size:1rem;font-weight:700"><?= Html::encode($model->name) ?></h3>
                        <?php if ($model->website): ?>
                            <span class="ap-tag ap-tag--success">Verified</span>
                        <?php else: ?>
                            <span class="ap-tag ap-tag--warning">Pending</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mb-2"><i class="fas fa-location-dot"></i> <?= Html::encode($model->location ?: '—') ?></p>
                    <p class="small mb-3" style="line-height:1.45"><?= Html::encode(\yii\helpers\StringHelper::truncate($model->description ?? '', 100)) ?></p>
                    <div class="d-flex gap-3 small mb-2">
                        <span><strong><?= (int) $posCount ?></strong> roles</span>
                        <span><strong><?= (int) $appCount ?></strong> applications</span>
                    </div>
                    <div>
                        <small class="text-muted">Engagement score <?= $score ?>%</small>
                        <div class="ap-progress-bar"><div class="ap-progress-fill" style="width:<?= $score ?>%"></div></div>
                    </div>
                    <div class="ap-entity-actions mt-3">
                        <?= Html::a('View', ['view', 'id' => $model->id], ['class' => 'ap-btn ap-btn-ghost ap-btn-sm']) ?>
                        <?= Html::a('Edit', ['update', 'id' => $model->id], ['class' => 'ap-btn ap-btn-primary ap-btn-sm']) ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 d-flex justify-content-center">
            <?= LinkPager::widget(['pagination' => $dataProvider->getPagination()]) ?>
        </div>
    <?php else: ?>
        <div class="ap-empty ap-glass">
            <i class="fas fa-building"></i>
            <h3>No organizations</h3>
            <p>Add your first partner organization to publish internship opportunities.</p>
            <?= Html::a('<i class="fas fa-plus"></i> Add organization', ['create'], ['class' => 'ap-btn ap-btn-primary']) ?>
        </div>
    <?php endif; ?>
</div>
