<?php
use yii\grid\GridView;
use yii\helpers\Html;
use common\models\Application;

$this->title = 'My Applications';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="application-my-applications">
    <h1><?= Html::encode($this->title) ?></h1>
    
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= Yii::$app->session->getFlash('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= Yii::$app->session->getFlash('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (Yii::$app->session->hasFlash('warning')): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?= Yii::$app->session->getFlash('warning') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-bordered'],
        'columns' => [
            [
                'attribute' => 'position.title',
                'label' => 'Position',
                'format' => 'html',
                'value' => function($model) {
                    if (!$model->position) {
                        return '<span class="text-muted">(Position Deleted)</span>';
                    }
                    return Html::a($model->position->title, ['view', 'id' => $model->id], [
                        'class' => 'text-decoration-none'
                    ]);
                }
            ],
            [
                'attribute' => 'position.organization.name',
                'label' => 'Organization',
                'value' => function($model) {
                    return $model->position && $model->position->organization ? 
                        $model->position->organization->name : 'N/A';
                }
            ],
            [
                'attribute' => 'status',
                'format' => 'html',
                'value' => function($model) {
                    $badgeClass = $model->getStatusBadgeClass();
                    $statusText = Application::getStatusOptions()[$model->status] ?? ucfirst($model->status);
                    return '<span class="badge bg-' . $badgeClass . '">' . $statusText . '</span>';
                }
            ],
            [
                'attribute' => 'created_at',
                'format' => ['date', 'php:Y-m-d H:i'],
                'label' => 'Applied On',
                'headerOptions' => ['style' => 'width: 150px;'],
            ],
            [
                'attribute' => 'updated_at',
                'format' => ['date', 'php:Y-m-d H:i'],
                'label' => 'Last Updated',
                'headerOptions' => ['style' => 'width: 150px;'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {withdraw}',
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        return Html::a('<i class="fas fa-eye"></i>', ['view', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-primary',
                            'title' => 'View Details'
                        ]);
                    },
                    'withdraw' => function ($url, $model, $key) {
                        if ($model->canWithdraw()) {
                            return Html::a('<i class="fas fa-times"></i>', ['withdraw', 'id' => $model->id], [
                                'class' => 'btn btn-sm btn-outline-danger',
                                'title' => 'Withdraw Application',
                                'data' => [
                                    'confirm' => 'Are you sure you want to withdraw this application?',
                                    'method' => 'post',
                                ]
                            ]);
                        }
                        return '';
                    },
                ],
                'headerOptions' => ['style' => 'width: 120px;'],
            ],
        ],
    ]) ?>
</div> 