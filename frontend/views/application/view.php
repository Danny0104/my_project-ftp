<?php

use common\models\Application;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var Application $model */

require_once __DIR__ . '/../dashboard/_student_helpers.php';

$this->title = 'Application Details';

$position = $model->position;
if (!$position) {
    throw new \yii\web\NotFoundHttpException('Position not found for this application.');
}
$org = $position->organization ?? null;
$orgName = $org->name ?? 'Organization';
$statusOptions = Application::getStatusOptions();
$statusText = $statusOptions[$model->status] ?? ucfirst(str_replace('_', ' ', $model->status));
$statusKey = spAppStatusKey($model->status);
$timeline = ftpTimelineState($model);

$steps = [
    ['label' => 'Submitted', 'done' => true],
    ['label' => 'Under review', 'done' => in_array($model->status, [
        Application::STATUS_UNDER_REVIEW,
        Application::STATUS_ORG_APPROVED,
        Application::STATUS_UNIVERSITY_APPROVED,
        Application::STATUS_APPROVED,
        Application::STATUS_COMPLETED,
    ], true)],
    ['label' => 'Shortlisted', 'done' => in_array($model->status, [
        Application::STATUS_ORG_APPROVED,
        Application::STATUS_UNIVERSITY_APPROVED,
        Application::STATUS_APPROVED,
        Application::STATUS_COMPLETED,
    ], true)],
    ['label' => 'Interview', 'done' => in_array($model->status, [
        Application::STATUS_UNIVERSITY_APPROVED,
        Application::STATUS_APPROVED,
        Application::STATUS_COMPLETED,
    ], true)],
    ['label' => 'Decision', 'done' => in_array($model->status, [
        Application::STATUS_APPROVED,
        Application::STATUS_REJECTED,
        Application::STATUS_COMPLETED,
    ], true)],
];
?>

<div class="sp-module sp-app-detail">
    <header class="sp-page-header sp-page-header-row">
        <div class="d-flex align-items-start gap-3">
            <?= ProfileAvatar::widget(['type' => 'organization', 'organization' => $org, 'size' => 'lg']) ?>
            <div>
            <?= Html::a('<i class="fas fa-arrow-left"></i> Back to applications', ['application/index'], ['class' => 'sp-btn-ghost btn-sm mb-2']) ?>
            <h1><?= Html::encode($position->title ?? 'Application') ?></h1>
            <p><i class="fas fa-building me-1"></i><?= Html::encode($orgName) ?></p>
            </div>
        </div>
        <span class="sp-status-badge sp-status-badge--<?= Html::encode($statusKey) ?>"><?= Html::encode($statusText) ?></span>
    </header>

    <div class="sp-app-detail-grid">
        <div class="sp-app-detail-main">
            <section class="sp-panel sp-glass sp-app-detail-pipeline">
                <h2><i class="fas fa-route me-2"></i>Application pipeline</h2>
                <div class="sp-pipeline-track">
                    <?php foreach ($steps as $i => $step):
                        $isActive = !$step['done'] && ($i === 0 || $steps[$i - 1]['done']);
                        $cls = $step['done'] ? 'is-done' : ($isActive ? 'is-active' : '');
                        if ($model->status === Application::STATUS_REJECTED && $step['label'] === 'Decision') {
                            $cls = 'is-rejected';
                        }
                        ?>
                        <div class="sp-pipeline-step <?= $cls ?>">
                            <div class="sp-pipeline-dot">
                                <?php if ($step['done']): ?><i class="fas fa-check"></i><?php endif; ?>
                            </div>
                            <span><?= Html::encode($step['label']) ?></span>
                        </div>
                        <?php if ($i < count($steps) - 1): ?>
                            <div class="sp-pipeline-connector <?= $step['done'] ? 'is-done' : '' ?>"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($model->status === Application::STATUS_REJECTED): ?>
                    <div class="sp-insight-banner sp-insight-banner--warning mt-3">
                        <i class="fas fa-circle-info"></i>
                        <span>This application was not successful. Review feedback below and explore other opportunities.</span>
                    </div>
                <?php elseif ($model->status === Application::STATUS_APPROVED || $model->status === Application::STATUS_COMPLETED): ?>
                    <div class="sp-insight-banner sp-insight-banner--success mt-3">
                        <i class="fas fa-party-horn"></i>
                        <span>Congratulations — your application has been approved. Check messages for next steps.</span>
                    </div>
                <?php endif; ?>
            </section>

            <section class="sp-panel sp-glass">
                <h2><i class="fas fa-briefcase me-2"></i>Position details</h2>
                <dl class="sp-detail-list">
                    <div><dt>Title</dt><dd><?= Html::encode($position->title ?? '—') ?></dd></div>
                    <div><dt>Organization</dt><dd><?= Html::encode($orgName) ?></dd></div>
                    <div><dt>Location</dt><dd><?= Html::encode($position->location ?? '—') ?></dd></div>
                    <div><dt>Duration</dt><dd><?= Html::encode($position->duration ?? '—') ?></dd></div>
                    <div><dt>Field</dt><dd><?= Html::encode($position->field_of_study ?? '—') ?></dd></div>
                </dl>
                <?php if ($position && $position->description): ?>
                    <h3 class="h6 mt-3">Description</h3>
                    <p class="sp-detail-desc"><?= nl2br(Html::encode($position->description)) ?></p>
                <?php endif; ?>
            </section>

            <?php if ($model->cover_letter): ?>
                <section class="sp-panel sp-glass">
                    <h2><i class="fas fa-envelope-open-text me-2"></i>Cover letter</h2>
                    <p class="mb-0"><?= nl2br(Html::encode($model->cover_letter)) ?></p>
                </section>
            <?php endif; ?>
        </div>

        <aside class="sp-app-detail-aside">
            <section class="sp-panel sp-glass">
                <h2 class="h6">Application info</h2>
                <dl class="sp-detail-list sp-detail-list--compact">
                    <div><dt>Applied</dt><dd><?= date('M d, Y · H:i', $model->created_at) ?></dd></div>
                    <div><dt>Last updated</dt><dd><?= date('M d, Y · H:i', $model->updated_at) ?></dd></div>
                    <div><dt>Reference</dt><dd>#<?= (int) $model->id ?></dd></div>
                </dl>
            </section>

            <section class="sp-panel sp-glass">
                <h2 class="h6">Actions</h2>
                <div class="d-grid gap-2">
                    <?php if ($position): ?>
                        <?= Html::a('<i class="fas fa-external-link-alt me-1"></i> View opportunity', ['position/view', 'id' => $model->position_id], ['class' => 'sp-btn-primary']) ?>
                    <?php endif; ?>
                    <?= Html::a('<i class="fas fa-list me-1"></i> All applications', ['application/index'], ['class' => 'sp-btn-ghost']) ?>
                    <?php if ($model->canWithdraw()): ?>
                        <?= Html::a('<i class="fas fa-ban me-1"></i> Withdraw', ['application/withdraw', 'id' => $model->id], [
                            'class' => 'sp-btn-ghost text-danger',
                            'data' => [
                                'confirm' => 'Are you sure you want to withdraw this application?',
                                'method' => 'post',
                            ],
                        ]) ?>
                    <?php endif; ?>
                </div>
            </section>

            <?php if ($model->feedback): ?>
                <section class="sp-panel sp-glass">
                    <h2 class="h6"><i class="fas fa-comment-dots me-1"></i> Recruiter feedback</h2>
                    <p class="mb-0 small"><?= nl2br(Html::encode($model->feedback)) ?></p>
                </section>
            <?php endif; ?>

            <section class="sp-panel sp-glass sp-insight-card">
                <h2 class="h6"><i class="fas fa-wand-magic-sparkles me-1"></i> Smart insight</h2>
                <p class="small text-muted mb-0">
                    <?php if ($model->status === Application::STATUS_PENDING): ?>
                        Applications are typically reviewed within 5–7 business days. Keep your profile and CV up to date.
                    <?php elseif ($model->status === Application::STATUS_UNDER_REVIEW): ?>
                        Your application is actively being reviewed. Check messages for interview invitations.
                    <?php else: ?>
                        Track similar roles on the opportunities page to maximize your placement chances.
                    <?php endif; ?>
                </p>
            </section>
        </aside>
    </div>
</div>
