<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use common\models\Application;
use common\models\User;

/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Students';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'students';

function apProfileStrength($model): int
{
    $items = [
        !empty($model->university),
        !empty($model->field_of_study),
        !empty($model->cv),
        !empty($model->personal_statement),
    ];
    return (int) round((count(array_filter($items)) / count($items)) * 100);
}

$activeCount = (int) User::find()
    ->innerJoin('student s', 's.user_id = user.id')
    ->where(['user.status' => User::STATUS_ACTIVE])
    ->count();
$withGpa = (int) \common\models\Student::find()->andWhere(['not', ['gpa' => null]])->andWhere(['<>', 'gpa', ''])->count();
?>

<div class="ap-module">
    <?= $this->render('../layouts/_page_header', [
        'title' => 'Student intelligence',
        'subtitle' => 'Profiles, academic insights, internship engagement, and placement readiness',
        'actions' => [
            Html::a('<i class="fas fa-download"></i> Export', '#', ['class' => 'ap-btn ap-btn-ghost']),
            Html::a('<i class="fas fa-plus"></i> Add student', ['create'], ['class' => 'ap-btn ap-btn-primary']),
        ],
    ]) ?>

    <?= $this->render('../layouts/partials/_kpi_grid', [
        'cards' => [
            ['label' => 'Total students', 'value' => (int) $dataProvider->totalCount, 'icon' => 'fa-user-graduate', 'accent' => 'blue'],
            ['label' => 'Active accounts', 'value' => $activeCount, 'icon' => 'fa-circle-check', 'accent' => 'green', 'trend' => 'Live'],
            ['label' => 'GPA on file', 'value' => $withGpa, 'icon' => 'fa-star', 'accent' => 'amber'],
            ['label' => 'Applications', 'value' => (int) Application::find()->count(), 'icon' => 'fa-file-lines', 'accent' => 'purple'],
        ],
    ]) ?>

    <?= $this->render('../layouts/partials/_module_toolbar', [
        'searchPlaceholder' => 'Search by name, email, university…',
        'searchId' => 'apStudentSearch',
        'searchTarget' => 'apStudentGrid',
        'viewToggleId' => 'apStudentGrid',
    ]) ?>

    <?php if ($dataProvider->getTotalCount() > 0): ?>
        <div class="ap-card-grid ap-view--grid" id="apStudentGrid">
            <?php foreach ($dataProvider->getModels() as $model): ?>
                <?php
                $strength = apProfileStrength($model);
                $appCount = Application::find()->where(['student_id' => $model->id])->count();
                $risk = $strength < 50 ? 'High' : ($strength < 80 ? 'Medium' : 'Low');
                $riskClass = $strength < 50 ? 'ap-tag--danger' : ($strength < 80 ? 'ap-tag--warning' : 'ap-tag--success');
                ?>
                <article class="ap-entity-card ap-glass"
                         data-search="<?= Html::encode(strtolower(($model->user->username ?? '') . ' ' . ($model->user->email ?? '') . ' ' . $model->university . ' ' . $model->field_of_study)) ?>">
                    <div class="ap-entity-card-head">
                        <?php
                        \frontend\assets\ProfileAvatarAsset::register($this);
                        echo \common\widgets\ProfileAvatar::widget(['type' => 'student', 'student' => $model, 'size' => 'md', 'cssClass' => 'ap-entity-avatar']);
                        ?>
                        <div>
                            <h3><?= Html::encode($model->user->username ?? 'Unknown') ?></h3>
                            <div class="ap-entity-meta"><i class="fas fa-id-badge"></i> <?= Html::encode($model->student_id) ?></div>
                            <span class="ap-tag <?= $model->user && $model->user->status == User::STATUS_ACTIVE ? 'ap-tag--success' : 'ap-tag--warning' ?>">
                                <?= $model->user && $model->user->status == User::STATUS_ACTIVE ? 'Active' : 'Inactive' ?>
                            </span>
                            <span class="ap-tag <?= $riskClass ?>" style="margin-left:4px">Risk <?= $risk ?></span>
                        </div>
                    </div>
                    <div class="ap-entity-details">
                        <div class="ap-entity-row"><i class="fas fa-envelope"></i> <?= Html::encode($model->user->email ?? 'N/A') ?></div>
                        <div class="ap-entity-row"><i class="fas fa-university"></i> <?= Html::encode($model->university) ?></div>
                        <?php if ($model->field_of_study): ?>
                            <div class="ap-entity-row"><i class="fas fa-book"></i> <?= Html::encode($model->field_of_study) ?></div>
                        <?php endif; ?>
                        <?php if ($model->gpa): ?>
                            <div class="ap-entity-row"><i class="fas fa-star"></i> GPA <?= Html::encode($model->gpa) ?></div>
                        <?php endif; ?>
                        <div class="ap-entity-row"><i class="fas fa-briefcase"></i> <?= (int) $appCount ?> internship applications</div>
                        <div>
                            <small class="text-muted">Profile strength <?= $strength ?>%</small>
                            <div class="ap-progress-bar"><div class="ap-progress-fill" style="width:<?= $strength ?>%"></div></div>
                        </div>
                    </div>
                    <div class="ap-entity-actions">
                        <?= Html::a('View', ['view', 'id' => $model->id], ['class' => 'ap-btn ap-btn-ghost ap-btn-sm']) ?>
                        <?= Html::a('Edit', ['update', 'id' => $model->id], ['class' => 'ap-btn ap-btn-primary ap-btn-sm']) ?>
                        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
                            'class' => 'ap-btn ap-btn-danger ap-btn-sm',
                            'data' => ['confirm' => 'Delete this student?', 'method' => 'post'],
                        ]) ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 d-flex justify-content-center">
            <?= LinkPager::widget(['pagination' => $dataProvider->getPagination()]) ?>
        </div>
    <?php else: ?>
        <div class="ap-empty ap-glass">
            <i class="fas fa-user-graduate"></i>
            <h3>No students yet</h3>
            <p>Add the first student to begin managing field training placements.</p>
            <?= Html::a('<i class="fas fa-plus"></i> Add student', ['create'], ['class' => 'ap-btn ap-btn-primary']) ?>
        </div>
    <?php endif; ?>
</div>
