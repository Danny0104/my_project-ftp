<?php

use common\models\Application;
use yii\helpers\Html;
use yii\grid\GridView;

/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Applications';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'applications';

$stages = [
    Application::STATUS_PENDING => ['label' => 'New', 'color' => 'blue'],
    Application::STATUS_UNDER_REVIEW => ['label' => 'Under review', 'color' => 'amber'],
    Application::STATUS_ORG_APPROVED => ['label' => 'Shortlisted', 'color' => 'teal'],
    Application::STATUS_UNIVERSITY_APPROVED => ['label' => 'Interviewed', 'color' => 'purple'],
    Application::STATUS_APPROVED => ['label' => 'Approved', 'color' => 'green'],
    Application::STATUS_REJECTED => ['label' => 'Rejected', 'color' => 'red'],
    Application::STATUS_COMPLETED => ['label' => 'Hired', 'color' => 'green'],
];

$pipelineCounts = [];
foreach (array_keys($stages) as $status) {
    $pipelineCounts[$status] = (int) Application::find()->where(['status' => $status])->count();
}
$totalPipeline = max(1, array_sum($pipelineCounts));

$grouped = [];
foreach ($dataProvider->getModels() as $app) {
    $grouped[$app->status][] = $app;
}

$pending = $pipelineCounts[Application::STATUS_PENDING] + $pipelineCounts[Application::STATUS_UNDER_REVIEW];
$approved = $pipelineCounts[Application::STATUS_APPROVED]
    + $pipelineCounts[Application::STATUS_ORG_APPROVED]
    + $pipelineCounts[Application::STATUS_UNIVERSITY_APPROVED]
    + $pipelineCounts[Application::STATUS_COMPLETED];
?>

<div class="ap-module" id="apApplicationsModule">
    <?= $this->render('../layouts/_page_header', [
        'title' => 'Recruitment center',
        'subtitle' => 'ATS pipeline, candidate scoring, and placement workflow',
        'actions' => [
            Html::a('<i class="fas fa-inbox"></i> Approval center', ['site/approvals'], ['class' => 'ap-btn ap-btn-ghost']),
            Html::a('<i class="fas fa-plus"></i> Create', ['create'], ['class' => 'ap-btn ap-btn-primary']),
        ],
    ]) ?>

    <?= $this->render('../layouts/partials/_kpi_grid', [
        'cards' => [
            ['label' => 'Pending review', 'value' => $pending, 'icon' => 'fa-clock', 'accent' => 'amber'],
            ['label' => 'Approved / hired', 'value' => $approved, 'icon' => 'fa-circle-check', 'accent' => 'green'],
            ['label' => 'Rejected', 'value' => $pipelineCounts[Application::STATUS_REJECTED], 'icon' => 'fa-ban', 'accent' => 'red'],
            ['label' => 'Total applications', 'value' => (int) $dataProvider->totalCount, 'icon' => 'fa-file-lines', 'accent' => 'blue'],
        ],
    ]) ?>

    <div class="ap-module-tabs" role="tablist">
        <button type="button" class="is-active" data-ap-app-view="kanban">Pipeline board</button>
        <button type="button" data-ap-app-view="table">Table view</button>
    </div>

    <div id="apAppKanbanView">
        <div class="ap-kanban">
            <?php foreach ($stages as $status => $meta):
                $count = $pipelineCounts[$status];
                $pct = round(100 * $count / $totalPipeline);
                $cards = $grouped[$status] ?? [];
                ?>
                <div class="ap-kanban-col">
                    <h4><?= Html::encode($meta['label']) ?></h4>
                    <div class="ap-kanban-count" data-ap-count="<?= $count ?>">0</div>
                    <div class="ap-kanban-bar" style="margin-bottom:10px"><span style="width:<?= $pct ?>%"></span></div>
                    <div class="ap-kanban-cards">
                        <?php foreach (array_slice($cards, 0, 5) as $app): ?>
                            <a href="<?= \yii\helpers\Url::to(['view', 'id' => $app->id]) ?>" class="ap-kanban-card">
                                <strong><?= Html::encode($app->student->user->username ?? 'Student #' . $app->student_id) ?></strong>
                                <span><?= Html::encode($app->position->title ?? 'Position') ?></span>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($count > 5): ?>
                            <div class="ap-kanban-more">+<?= $count - 5 ?> more in this stage</div>
                        <?php endif; ?>
                        <?php if ($count === 0): ?>
                            <div class="ap-kanban-more">No candidates</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="apAppTableView" class="d-none">
        <div class="ap-panel ap-crud-panel ap-glass">
            <div class="ap-crud-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'tableOptions' => ['class' => 'table table-hover mb-0 ap-table'],
                    'columns' => [
                        'id',
                        [
                            'attribute' => 'student_id',
                            'value' => fn($m) => $m->student->user->username ?? $m->student_id,
                        ],
                        [
                            'attribute' => 'position_id',
                            'value' => fn($m) => $m->position->title ?? $m->position_id,
                        ],
                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'value' => fn($m) => '<span class="ap-tag ap-tag--info">' . Html::encode(ucfirst(str_replace('_', ' ', $m->status))) . '</span>',
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
</div>

<?php
$this->registerJs(<<<'JS'
(function () {
  var tabs = document.querySelectorAll('[data-ap-app-view]');
  var kanban = document.getElementById('apAppKanbanView');
  var table = document.getElementById('apAppTableView');
  if (!tabs.length) return;
  tabs.forEach(function (btn) {
    btn.addEventListener('click', function () {
      tabs.forEach(function (b) { b.classList.remove('is-active'); });
      btn.classList.add('is-active');
      var view = btn.getAttribute('data-ap-app-view');
      if (kanban) kanban.classList.toggle('d-none', view !== 'kanban');
      if (table) table.classList.toggle('d-none', view !== 'table');
    });
  });
})();
JS
);
?>
