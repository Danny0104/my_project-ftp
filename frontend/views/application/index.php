<?php

use common\models\Application;
use frontend\assets\StudentApplicationsAsset;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var Application[] $applications */

$this->title = 'My Applications';
require_once __DIR__ . '/../dashboard/_student_helpers.php';

$isStudent = !Yii::$app->user->isGuest && Yii::$app->user->identity->role === 'student';
if ($isStudent) {
    echo $this->render('_student_applications', ['applications' => $applications]);
    return;
}

$statusOptions = Application::getStatusOptions();

$counts = [
    'total' => count($applications),
    'pending' => 0,
    'review' => 0,
    'interview' => 0,
    'accepted' => 0,
    'rejected' => 0,
];

foreach ($applications as $app) {
    switch ($app->status) {
        case Application::STATUS_PENDING:
            $counts['pending']++;
            break;
        case Application::STATUS_UNDER_REVIEW:
            $counts['review']++;
            break;
        case Application::STATUS_ORG_APPROVED:
        case Application::STATUS_UNIVERSITY_APPROVED:
            $counts['interview']++;
            break;
        case Application::STATUS_APPROVED:
        case Application::STATUS_COMPLETED:
            $counts['accepted']++;
            break;
        case Application::STATUS_REJECTED:
            $counts['rejected']++;
            break;
    }
}

?>

<div class="sp-module">
<div class="sp-page-header sp-page-header-row">
    <div>
        <h1>Application Tracker</h1>
        <p>Monitor every step of your field training applications in one professional dashboard.</p>
    </div>
    <?= Html::a('<i class="fas fa-search me-1"></i> Browse Opportunities', ['position/index'], ['class' => 'sp-btn-primary']) ?>
</div>

<div class="sp-stats-grid">
    <div class="sp-stat-card sp-stat-card--total">
        <div class="sp-stat-card-icon"><i class="fas fa-layer-group"></i></div>
        <div class="sp-stat-card-value" data-count="<?= (int) $counts['total'] ?>">0</div>
        <div class="sp-stat-card-label">Total</div>
    </div>
    <div class="sp-stat-card sp-stat-card--pending">
        <div class="sp-stat-card-icon"><i class="fas fa-clock"></i></div>
        <div class="sp-stat-card-value" data-count="<?= (int) $counts['pending'] ?>">0</div>
        <div class="sp-stat-card-label">Pending</div>
    </div>
    <div class="sp-stat-card sp-stat-card--review">
        <div class="sp-stat-card-icon"><i class="fas fa-eye"></i></div>
        <div class="sp-stat-card-value" data-count="<?= (int) $counts['review'] ?>">0</div>
        <div class="sp-stat-card-label">Reviewed</div>
    </div>
    <div class="sp-stat-card sp-stat-card--interview">
        <div class="sp-stat-card-icon"><i class="fas fa-video"></i></div>
        <div class="sp-stat-card-value" data-count="<?= (int) $counts['interview'] ?>">0</div>
        <div class="sp-stat-card-label">Interview</div>
    </div>
    <div class="sp-stat-card sp-stat-card--accepted">
        <div class="sp-stat-card-icon"><i class="fas fa-check-circle"></i></div>
        <div class="sp-stat-card-value" data-count="<?= (int) $counts['accepted'] ?>">0</div>
        <div class="sp-stat-card-label">Accepted</div>
    </div>
    <div class="sp-stat-card sp-stat-card--rejected">
        <div class="sp-stat-card-icon"><i class="fas fa-times-circle"></i></div>
        <div class="sp-stat-card-value" data-count="<?= (int) $counts['rejected'] ?>">0</div>
        <div class="sp-stat-card-label">Rejected</div>
    </div>
</div>

<div class="sp-app-toolbar sp-glass" style="padding:14px 18px;border-radius:16px;">
    <div class="sp-app-tabs">
        <button type="button" class="sp-app-tab is-active" data-app-filter="all">All</button>
        <button type="button" class="sp-app-tab" data-app-filter="pending">Pending</button>
        <button type="button" class="sp-app-tab" data-app-filter="review">Under Review</button>
        <button type="button" class="sp-app-tab" data-app-filter="approved">Accepted</button>
        <button type="button" class="sp-app-tab" data-app-filter="rejected">Rejected</button>
        <button type="button" class="sp-app-tab" data-app-filter="withdrawn">Withdrawn</button>
    </div>
    <input type="search" class="sp-app-search" id="spAppSearch" placeholder="Search applications…" aria-label="Search applications">
</div>

<div class="sp-app-list mt-3">
    <?php if (!empty($applications)): ?>
        <?php foreach ($applications as $app): ?>
            <?php
            $orgName = $app->position ? ($app->position->organization->name ?? 'N/A') : 'N/A';
            $positionTitle = $app->position ? $app->position->title : 'N/A';
            $statusKey = spAppStatusKey($app->status);
            $statusText = $statusOptions[$app->status] ?? ucfirst($app->status);
            $timeline = ftpTimelineState($app);
            $nextStep = 'Awaiting recruiter response';
            if ($app->status === Application::STATUS_UNDER_REVIEW) {
                $nextStep = 'Application is being reviewed';
            } elseif ($app->status === Application::STATUS_APPROVED) {
                $nextStep = 'Congratulations — offer accepted path';
            } elseif ($app->status === Application::STATUS_REJECTED) {
                $nextStep = 'Application closed';
            }
            ?>
            <article class="sp-app-card" data-app-status="<?= Html::encode($statusKey) ?>" data-search-text="<?= Html::encode(strtolower($orgName . ' ' . $positionTitle)) ?>">
                <div class="sp-app-card-main">
                    <div class="sp-app-logo"><?= \common\widgets\ProfileAvatar::widget(['type' => 'organization', 'organization' => $app->position->organization ?? null, 'size' => 'sm', 'fillSlot' => true]) ?></div>
                    <div class="sp-app-info">
                        <h2 class="sp-app-title"><?= Html::encode($positionTitle) ?></h2>
                        <p class="sp-app-org"><?= Html::encode($orgName) ?></p>
                        <span class="sp-status-badge sp-status-badge--<?= Html::encode($statusKey) ?>">
                            <?= Html::encode($statusText) ?>
                        </span>
                        <div class="sp-timeline mt-2" aria-label="Application progress">
                            <?php foreach ($timeline['steps'] as $i => $step): ?>
                                <?php
                                $cls = '';
                                if ($i < $timeline['activeIndex']) {
                                    $cls = 'is-done';
                                } elseif ($i === $timeline['activeIndex']) {
                                    $cls = 'is-active';
                                }
                                ?>
                                <span class="sp-timeline-step <?= $cls ?>" title="<?= Html::encode($step['label']) ?>"></span>
                            <?php endforeach; ?>
                        </div>
                        <p class="small text-muted mt-2 mb-0">
                            Applied <?= date('M d, Y', $app->created_at) ?> · Next: <?= Html::encode($nextStep) ?>
                        </p>
                    </div>
                    <div class="sp-app-actions">
                        <button type="button" class="sp-btn-ghost" data-expand-card aria-expanded="false">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <?= Html::a('<i class="fas fa-eye"></i>', ['application/view', 'id' => $app->id], [
                            'class' => 'sp-btn-ghost',
                            'title' => 'View details',
                        ]) ?>
                        <?php if ($app->canWithdraw()): ?>
                            <?= Html::a('<i class="fas fa-ban"></i>', ['application/withdraw', 'id' => $app->id], [
                                'class' => 'sp-btn-ghost text-danger',
                                'title' => 'Withdraw',
                                'data' => [
                                    'confirm' => 'Are you sure you want to withdraw this application?',
                                    'method' => 'post',
                                ],
                            ]) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="sp-app-card-expand">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong class="small text-uppercase text-muted">Timeline</strong>
                            <ul class="list-unstyled small mt-2 mb-0">
                                <?php foreach ($timeline['steps'] as $i => $step): ?>
                                    <li class="mb-1">
                                        <i class="fas fa-<?= $i <= $timeline['activeIndex'] ? 'check-circle text-primary' : 'circle text-muted' ?> me-1"></i>
                                        <?= Html::encode($step['label']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <strong class="small text-uppercase text-muted">Details</strong>
                            <p class="small mt-2 mb-2">Organization location: <?= Html::encode($app->position->organization->location ?? '—') ?></p>
                            <p class="small mb-0">Status updated: <?= ftpRelativeTime((int) $app->created_at) ?></p>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="sp-empty">
            <i class="fas fa-inbox d-block"></i>
            <h3>No applications yet</h3>
            <p>Start exploring opportunities and submit your first application today.</p>
            <?= Html::a('Browse Opportunities', ['position/index'], ['class' => 'sp-btn-primary']) ?>
        </div>
    <?php endif; ?>
</div>

<?php
$this->registerJs(<<<'JS'
document.getElementById('spAppSearch')?.addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('[data-search-text]').forEach(function (card) {
        card.hidden = q !== '' && !card.getAttribute('data-search-text').includes(q);
    });
});
JS
);
?>
</div>
