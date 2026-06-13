<?php

use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Notifications';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'notifications';

$models = $dataProvider->getModels();
$unread = 0;
foreach ($models as $m) {
    if (!$m->is_read) {
        $unread++;
    }
}
?>

<div class="ap-module">
    <?= $this->render('../layouts/_page_header', [
        'title' => 'Notification center',
        'subtitle' => 'Delivery tracking, read status, and broadcast history',
        'actions' => [
            Html::a('<i class="fas fa-bullhorn"></i> Broadcast', ['site/send-announcement'], ['class' => 'ap-btn ap-btn-ghost']),
            Html::a('<i class="fas fa-plus"></i> Create', ['create'], ['class' => 'ap-btn ap-btn-primary']),
        ],
    ]) ?>

    <?= $this->render('../layouts/partials/_kpi_grid', [
        'cards' => [
            ['label' => 'Total messages', 'value' => (int) $dataProvider->totalCount, 'icon' => 'fa-bell', 'accent' => 'blue'],
            ['label' => 'Unread (page)', 'value' => $unread, 'icon' => 'fa-envelope', 'accent' => 'amber'],
            ['label' => 'Read (page)', 'value' => count($models) - $unread, 'icon' => 'fa-envelope-open', 'accent' => 'green'],
            ['label' => 'This week', 'value' => (int) \common\models\Notification::find()->where(['>=', 'created_at', strtotime('-7 days')])->count(), 'icon' => 'fa-calendar-week', 'accent' => 'purple'],
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
                    [
                        'attribute' => 'message',
                        'value' => fn($m) => \yii\helpers\StringHelper::truncate($m->message ?? '', 80),
                    ],
                    'sender_type',
                    [
                        'attribute' => 'is_read',
                        'format' => 'raw',
                        'value' => fn($m) => $m->is_read ? '<span class="ap-tag ap-tag--success">Read</span>' : '<span class="ap-tag ap-tag--warning">Unread</span>',
                    ],
                    ['attribute' => 'created_at', 'format' => ['datetime', 'php:M d, Y']],
                    ['class' => 'yii\grid\ActionColumn'],
                ],
            ]) ?>
        </div>
    </div>
</div>
