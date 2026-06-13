<?php

use yii\helpers\Html;
use yii\grid\GridView;
use common\models\EligibilityAuditLog;

/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var yii\data\ActiveDataProvider $activityProvider */

$this->title = 'Audit Logs';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'audit';

$blocked = (int) EligibilityAuditLog::find()->where(['eligible' => 0])->count();
$eligible = (int) EligibilityAuditLog::find()->where(['eligible' => 1])->count();
?>

<div class="ap-module">
    <?= $this->render('../layouts/_page_header', [
        'title' => 'Audit & security center',
        'subtitle' => 'Eligibility checks, application attempts, and compliance events',
        'actions' => [
            Html::a('<i class="fas fa-download"></i> Export', ['audit-logs', 'export' => 1], ['class' => 'ap-btn ap-btn-ghost']),
        ],
    ]) ?>

    <?= $this->render('../layouts/partials/_kpi_grid', [
        'cards' => [
            ['label' => 'Total events', 'value' => (int) $dataProvider->totalCount, 'icon' => 'fa-clipboard-list', 'accent' => 'blue'],
            ['label' => 'Eligible', 'value' => $eligible, 'icon' => 'fa-circle-check', 'accent' => 'green'],
            ['label' => 'Blocked', 'value' => $blocked, 'icon' => 'fa-shield-halved', 'accent' => 'red'],
            ['label' => 'Risk flags', 'value' => $blocked > 0 ? (int) round(100 * $blocked / max(1, $eligible + $blocked)) : 0, 'icon' => 'fa-triangle-exclamation', 'accent' => 'amber', 'suffix' => '%'],
        ],
    ]) ?>

    <div class="ap-panel ap-crud-panel ap-glass">
        <div class="ap-crud-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0 ap-table'],
                'columns' => [
                    'id',
                    'user_id',
                    'position_id',
                    [
                        'attribute' => 'eligible',
                        'format' => 'raw',
                        'value' => fn($m) => $m->eligible
                            ? '<span class="ap-tag ap-tag--success">Eligible</span>'
                            : '<span class="ap-tag ap-tag--danger">Blocked</span>',
                    ],
                    'match_score',
                    'action',
                    [
                        'attribute' => 'created_at',
                        'format' => ['datetime', 'php:M d, Y H:i'],
                    ],
                ],
            ]) ?>
        </div>
    </div>

    <div class="ap-panel ap-crud-panel ap-glass mt-4">
        <div class="ap-panel-head px-3 pt-3">
            <h3 style="margin:0;font-size:1rem"><i class="fas fa-list-check me-2"></i>Platform activity log</h3>
        </div>
        <div class="ap-crud-body">
            <?= GridView::widget([
                'dataProvider' => $activityProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0 ap-table'],
                'columns' => [
                    'id',
                    'user_id',
                    'action',
                    'entity_type',
                    'entity_id',
                    [
                        'attribute' => 'meta_json',
                        'format' => 'ntext',
                        'contentOptions' => ['style' => 'max-width:280px;white-space:pre-wrap'],
                    ],
                    [
                        'attribute' => 'created_at',
                        'format' => ['datetime', 'php:M d, Y H:i'],
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
