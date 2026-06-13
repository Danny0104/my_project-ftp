<?php

use common\models\Application;
use common\models\Position;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Opportunities';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'opportunities';

$active = (int) Position::find()->where(['or', ['status' => 'Active'], ['status' => 'active']])->count();
$totalApps = (int) Application::find()->count();
?>

<div class="ap-module">
    <?= $this->render('../layouts/_page_header', [
        'title' => 'Opportunity management',
        'subtitle' => 'Internship performance, conversion metrics, and demand analytics',
        'actions' => [
            Html::a('<i class="fas fa-plus"></i> Create opportunity', ['create'], ['class' => 'ap-btn ap-btn-primary']),
        ],
    ]) ?>

    <?= $this->render('../layouts/partials/_kpi_grid', [
        'cards' => [
            ['label' => 'Total opportunities', 'value' => (int) $dataProvider->totalCount, 'icon' => 'fa-briefcase', 'accent' => 'blue'],
            ['label' => 'Active listings', 'value' => $active, 'icon' => 'fa-circle-check', 'accent' => 'green', 'trend' => 'Live'],
            ['label' => 'Applications', 'value' => $totalApps, 'icon' => 'fa-file-lines', 'accent' => 'purple'],
            ['label' => 'Avg apps / role', 'value' => $dataProvider->totalCount > 0 ? (int) round($totalApps / $dataProvider->totalCount) : 0, 'icon' => 'fa-chart-simple', 'accent' => 'teal'],
        ],
    ]) ?>

    <div class="ap-panel ap-crud-panel ap-glass">
        <div class="ap-crud-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0 ap-table'],
                'columns' => [
                    'id',
                    'title',
                    'field_of_study',
                    'category',
                    'location',
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => fn($m) => '<span class="ap-tag ' . (strtolower($m->status) === 'active' ? 'ap-tag--success' : 'ap-tag--warning') . '">' . Html::encode($m->status) . '</span>',
                    ],
                    [
                        'label' => 'Applications',
                        'format' => 'raw',
                        'value' => function ($m) {
                            $n = Application::find()->where(['position_id' => $m->id])->count();
                            $heat = $n > 10 ? 'ap-tag--success' : ($n > 3 ? 'ap-tag--info' : '');
                            return '<span class="ap-tag ' . $heat . '">' . (int) $n . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'created_at',
                        'format' => ['date', 'php:M d, Y'],
                    ],
                    ['class' => 'yii\grid\ActionColumn'],
                ],
            ]) ?>
        </div>
    </div>
</div>
