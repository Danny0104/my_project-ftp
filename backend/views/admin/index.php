<?php

use yii\grid\GridView;
use yii\helpers\Html;
use common\models\Admin;

/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Administrators';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'admins';

$active = (int) Admin::find()->where(['status' => Admin::STATUS_ACTIVE])->count();
?>

<div class="ap-module">
    <?= $this->render('../layouts/_page_header', [
        'title' => 'Team & access management',
        'subtitle' => 'Administrator accounts, roles, and security oversight',
        'actions' => [
            Html::a('<i class="fas fa-clipboard-list"></i> Audit logs', ['site/audit-logs'], ['class' => 'ap-btn ap-btn-ghost']),
            Html::a('<i class="fas fa-plus"></i> Create admin', ['create'], ['class' => 'ap-btn ap-btn-primary']),
        ],
    ]) ?>

    <?= $this->render('../layouts/partials/_kpi_grid', [
        'cards' => [
            ['label' => 'Admin accounts', 'value' => (int) $dataProvider->totalCount, 'icon' => 'fa-user-shield', 'accent' => 'blue'],
            ['label' => 'Active', 'value' => $active, 'icon' => 'fa-lock-open', 'accent' => 'green'],
            ['label' => 'Inactive', 'value' => (int) $dataProvider->totalCount - $active, 'icon' => 'fa-lock', 'accent' => 'amber'],
            ['label' => 'Sessions', 'value' => 1, 'icon' => 'fa-desktop', 'accent' => 'purple', 'trend' => 'You'],
        ],
    ]) ?>

    <div class="ap-panel ap-crud-panel ap-glass">
        <div class="ap-crud-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0 ap-table'],
                'columns' => [
                    'id',
                    'username',
                    'email',
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => fn($m) => '<span class="ap-tag ' . ($m->status == Admin::STATUS_ACTIVE ? 'ap-tag--success' : 'ap-tag--warning') . '">' . Html::encode($m->status == Admin::STATUS_ACTIVE ? 'Active' : 'Inactive') . '</span>',
                    ],
                    'created_at:datetime',
                    ['class' => 'yii\grid\ActionColumn'],
                ],
            ]) ?>
        </div>
    </div>
</div>
