<?php

use yii\grid\GridView;
use yii\helpers\Html;
use common\models\User;

/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Users';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'users';

$pending = (int) User::find()->where(['status' => User::STATUS_PENDING])->count();
$active = (int) User::find()->where(['status' => User::STATUS_ACTIVE])->count();
?>

<div class="ap-module">
    <?= $this->render('../layouts/_page_header', [
        'title' => 'User management',
        'subtitle' => 'Platform accounts, roles, and registration approvals',
        'actions' => [
            Html::a('<i class="fas fa-inbox"></i> Approval center', ['site/approvals'], ['class' => 'ap-btn ap-btn-ghost']),
            Html::a('<i class="fas fa-plus"></i> Create user', ['create'], ['class' => 'ap-btn ap-btn-primary']),
        ],
    ]) ?>

    <?= $this->render('../layouts/partials/_kpi_grid', [
        'cards' => [
            ['label' => 'Total users', 'value' => (int) $dataProvider->totalCount, 'icon' => 'fa-users', 'accent' => 'blue'],
            ['label' => 'Active', 'value' => $active, 'icon' => 'fa-circle-check', 'accent' => 'green'],
            ['label' => 'Pending approval', 'value' => $pending, 'icon' => 'fa-clock', 'accent' => 'amber'],
            ['label' => 'Students', 'value' => (int) User::find()->where(['role' => 'student'])->count(), 'icon' => 'fa-user-graduate', 'accent' => 'purple'],
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
                        'attribute' => 'role',
                        'format' => 'raw',
                        'value' => fn($m) => '<span class="ap-tag ap-tag--info">' . Html::encode(ucfirst($m->role)) . '</span>',
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function ($m) {
                            $map = [
                                User::STATUS_ACTIVE => ['Active', 'ap-tag--success'],
                                User::STATUS_PENDING => ['Pending', 'ap-tag--warning'],
                                User::STATUS_INACTIVE => ['Inactive', ''],
                            ];
                            [$label, $cls] = $map[$m->status] ?? ['Unknown', ''];
                            return '<span class="ap-tag ' . $cls . '">' . $label . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'created_at',
                        'format' => ['date', 'php:M d, Y'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {update} {delete} {approve} {reject}',
                        'buttons' => [
                            'approve' => function ($url, $model) {
                                if ($model->status === User::STATUS_PENDING) {
                                    return Html::a('Approve', ['approve', 'id' => $model->id], [
                                        'class' => 'ap-btn ap-btn-success ap-btn-sm',
                                        'data-method' => 'post',
                                        'data-confirm' => 'Approve this user?',
                                    ]);
                                }
                                return '';
                            },
                            'reject' => function ($url, $model) {
                                if ($model->status === User::STATUS_PENDING) {
                                    return Html::a('Reject', ['reject', 'id' => $model->id], [
                                        'class' => 'ap-btn ap-btn-danger ap-btn-sm',
                                        'data-method' => 'post',
                                        'data-confirm' => 'Reject this user?',
                                    ]);
                                }
                                return '';
                            },
                        ],
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
