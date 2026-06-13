<?php

use common\models\Application;
use common\models\Position;
use common\models\Student;
use common\services\EligibilityResult;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

/** @var Position $position */
/** @var Student|null $student */
/** @var EligibilityResult|null $eligibility */
/** @var Application|null $application */
/** @var string $viewMode grid|list */

$orgName = $position->organization->name ?? 'Organization';
$isRemote = ftpIsRemote($position->location);
$isHybrid = $position->location && preg_match('/hybrid/i', (string) $position->location);
$workMode = $isRemote ? 'remote' : ($isHybrid ? 'hybrid' : 'on-site');
$deadlineMeta = ftpPositionDeadlineMeta($position);
$daysLeft = $deadlineMeta['days'];
$deadlineLabel = (string) $deadlineMeta['label'];
$match = $eligibility ? (int) $eligibility->matchScore : 0;
$categoryKey = strtolower(trim((string) ($position->category ?? '')));
if ($categoryKey === '' && !empty($position->field_of_study)) {
    $firstField = trim(explode(',', (string) $position->field_of_study)[0]);
    $resolved = \common\models\FieldOfStudy::resolve($firstField);
    $categoryKey = strtolower($resolved ? $resolved->category : $firstField);
    $categoryKey = preg_replace('/\s+/', '-', $categoryKey) ?? '';
}
$isEligible = $eligibility ? $eligibility->eligible : false;
$skills = array_filter(array_map('trim', explode(',', (string) ($position->skills_required ?? ''))));
$tags = strtolower(($position->field_of_study ?? '') . ' ' . ($position->category ?? ''));
$tags .= ' ' . $workMode;
if (!$deadlineMeta['is_closed'] && $daysLeft !== null && $daysLeft <= 7) {
    $tags .= ' closing';
}
if ($match >= 75) {
    $tags .= ' recommended';
}
$searchBlob = strtolower(implode(' ', [
    $position->title,
    $orgName,
    $position->description,
    $position->location,
    $position->field_of_study,
    $position->skills_required,
    $position->category,
]));
$hasApplied = $application !== null;

$insight = 'Explore details and check your match score';
if ($eligibility && $eligibility->badge === 'best_fit') {
    $insight = 'Strong match — best fit for your profile';
} elseif ($match >= 82) {
    $insight = 'Your profile matches ' . $match . '% of this internship';
} elseif ($match >= 70 && $student && $student->field_of_study) {
    $insight = 'Strong match for your ' . $student->field_of_study . ' skills';
} elseif ($deadlineMeta['is_urgent']) {
    $insight = 'Closing soon — ' . $deadlineLabel;
} elseif ($daysLeft !== null && $daysLeft <= 14) {
    $insight = 'Trending internship in your field';
}

$statusKey = $application ? spAppStatusKey($application->status) : '';
$statusLabel = $application ? (Application::getStatusOptions()[$application->status] ?? $application->status) : '';
$journey = $application ? spAppJourneyTimeline($application->status) : null;
$actionButtonLabel = 'Applied';
if ($application) {
    if (in_array($application->status, [Application::STATUS_COMPLETED], true)) {
        $actionButtonLabel = 'Completed';
    } elseif (in_array($application->status, [Application::STATUS_APPROVED], true)) {
        $actionButtonLabel = 'Approved';
    } elseif ($application->status === Application::STATUS_REJECTED) {
        $actionButtonLabel = 'Rejected';
    } elseif ($application->status === Application::STATUS_PENDING) {
        $actionButtonLabel = 'Pending review';
    } else {
        $actionButtonLabel = $statusLabel;
    }
}
?>

<article class="sp-om-card sp-om-card--<?= Html::encode($viewMode) ?> sp-om-enter"
         data-position-id="<?= (int) $position->id ?>"
         data-field="<?= Html::encode(strtolower($position->field_of_study ?? '')) ?>"
         data-category="<?= Html::encode($categoryKey) ?>"
         data-tags="<?= Html::encode($tags) ?>"
         data-work-mode="<?= Html::encode($workMode) ?>"
         data-match="<?= (int) $match ?>"
         data-deadline-days="<?= $daysLeft === null ? 999 : (int) $daysLeft ?>"
         data-search="<?= Html::encode($searchBlob) ?>"
         data-title="<?= Html::encode($position->title) ?>"
         data-org="<?= Html::encode($orgName) ?>"
         data-insight="<?= Html::encode($insight) ?>"
         data-desc="<?= Html::encode(StringHelper::truncate(strip_tags((string) ($position->description ?? '')), 220)) ?>"
         data-view-url="<?= Html::encode(Url::to(['position/view', 'id' => $position->id])) ?>">
    <div class="sp-om-card-top">
        <div class="sp-om-logo" aria-hidden="true"><?= ProfileAvatar::widget(['type' => 'organization', 'organization' => $position->organization ?? null, 'size' => 'sm', 'fillSlot' => true]) ?></div>
        <div class="sp-om-card-intro">
            <div class="sp-om-card-title-row">
                <h2 class="sp-om-title">
                    <?= Html::a(Html::encode($position->title), ['position/view', 'id' => $position->id], ['class' => 'sp-om-title-link']) ?>
                </h2>
                <?php if ($position->organization): ?>
                    <span class="sp-om-verified" title="Registered organization"><i class="fas fa-circle-check"></i></span>
                <?php endif; ?>
            </div>
            <p class="sp-om-org"><i class="fas fa-building"></i> <?= Html::encode($orgName) ?></p>
        </div>
        <div class="sp-om-match-ring" data-match-ring="<?= (int) $match ?>" title="Match score">
            <svg viewBox="0 0 36 36" aria-hidden="true">
                <circle class="sp-om-match-bg" cx="18" cy="18" r="15.5"></circle>
                <circle class="sp-om-match-fg" cx="18" cy="18" r="15.5"
                        style="--sp-match: <?= (int) $match ?>"></circle>
            </svg>
            <span><?= (int) $match ?>%</span>
        </div>
    </div>

    <?php if ($deadlineMeta['is_urgent'] && !$deadlineMeta['is_closed']): ?>
        <div class="sp-om-deadline-banner" role="status">
            <i class="fas fa-hourglass-half"></i> <?= Html::encode($deadlineLabel) ?>
        </div>
    <?php endif; ?>

    <?php if (!$hasApplied): ?>
        <p class="sp-om-insight"><i class="fas fa-wand-magic-sparkles"></i> <?= Html::encode($insight) ?></p>
    <?php endif; ?>

    <div class="sp-om-meta">
        <?php if ($position->location): ?>
            <span><i class="fas fa-location-dot"></i><?= Html::encode(StringHelper::truncate($position->location, 28)) ?></span>
        <?php endif; ?>
        <?php if ($position->duration): ?>
            <span><i class="fas fa-clock"></i><?= Html::encode($position->duration) ?></span>
        <?php endif; ?>
        <span class="sp-om-mode sp-om-mode--<?= Html::encode($workMode) ?>"><?= Html::encode(ucfirst(str_replace('-', ' ', $workMode))) ?></span>
        <?php if ($position->category): ?>
            <span><i class="fas fa-tag"></i><?= Html::encode(StringHelper::truncate($position->category, 20)) ?></span>
        <?php endif; ?>
    </div>

    <div class="sp-om-tags">
        <?php if ($isEligible): ?>
            <span class="sp-om-badge sp-om-badge--ok"><i class="fas fa-check"></i> Eligible</span>
        <?php elseif ($eligibility): ?>
            <span class="sp-om-badge sp-om-badge--muted"><i class="fas fa-lock"></i> Restricted</span>
        <?php endif; ?>
        <?php if ($eligibility && $eligibility->badge === 'best_fit'): ?>
            <span class="sp-om-badge sp-om-badge--ai"><i class="fas fa-star"></i> Best fit</span>
        <?php endif; ?>
        <?php if (!$deadlineMeta['is_closed'] && $daysLeft !== null && $daysLeft <= 14): ?>
            <span class="sp-om-badge sp-om-badge--warn"><i class="fas fa-hourglass-half"></i> <?= Html::encode($deadlineLabel) ?></span>
        <?php elseif ($deadlineMeta['is_closed']): ?>
            <span class="sp-om-badge sp-om-badge--muted"><i class="fas fa-lock"></i> Closed</span>
        <?php endif; ?>
        <?php foreach (array_slice($skills, 0, 4) as $skill): ?>
            <button type="button" class="sp-om-skill" data-skill-filter="<?= Html::encode(strtolower($skill)) ?>"><?= Html::encode($skill) ?></button>
        <?php endforeach; ?>
    </div>

    <?php if ($hasApplied && $application): ?>
        <div class="sp-om-app-status sp-om-app-status--<?= Html::encode($statusKey) ?>">
            <div class="sp-om-app-status-head">
                <span><i class="fas fa-circle-notch"></i> <?= Html::encode($statusLabel) ?></span>
                <?= Html::a('Track', ['application/index'], ['class' => 'sp-om-link']) ?>
            </div>
            <div class="sp-om-timeline" role="list" aria-label="Application progress">
                <?php
                $timeline = $journey ?? spAppJourneyTimeline($application->status);
                $activeIndex = (int) ($timeline['index'] ?? 0);
                foreach ($timeline['steps'] as $i => $step):
                    $cls = $i < $activeIndex ? 'is-done' : ($i === $activeIndex ? 'is-active' : '');
                    if ($application->status === Application::STATUS_REJECTED && $step['key'] === 'interview') {
                        $cls = 'is-rejected';
                    }
                ?>
                    <span class="sp-om-timeline-step <?= $cls ?>" role="listitem"><?= Html::encode($step['label']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="sp-om-card-foot">
        <div class="sp-om-actions-left">
            <button type="button" class="sp-om-icon-btn sp-save-btn" data-save-id="<?= (int) $position->id ?>" title="Save" aria-label="Save opportunity">
                <i class="far fa-bookmark"></i>
            </button>
            <button type="button" class="sp-om-icon-btn" data-om-quick-view="<?= (int) $position->id ?>" title="Quick view">
                <i class="fas fa-expand"></i>
            </button>
            <button type="button" class="sp-om-icon-btn" data-om-share="<?= Html::encode(Url::to(['position/view', 'id' => $position->id], true)) ?>" title="Share">
                <i class="fas fa-share-nodes"></i>
            </button>
        </div>
        <div class="sp-om-actions-right">
            <?= Html::a('Details', ['position/view', 'id' => $position->id], ['class' => 'sp-om-btn sp-om-btn--ghost']) ?>
            <?php if ($hasApplied): ?>
                <?= Html::a(
                    '<i class="fas fa-check"></i> ' . Html::encode($actionButtonLabel),
                    ['application/index'],
                    ['class' => 'sp-om-btn sp-om-btn--applied', 'title' => Html::encode($statusLabel)]
                ) ?>
            <?php elseif ($isEligible): ?>
                <?= Html::a('<i class="fas fa-paper-plane"></i> Apply', Url::to(['position/view', 'id' => $position->id]) . '#apply', [
                    'class' => 'sp-om-btn sp-om-btn--primary',
                ]) ?>
            <?php else: ?>
                <button type="button"
                        class="sp-om-btn sp-om-btn--disabled"
                        data-eligibility-check="<?= (int) $position->id ?>"
                        data-eligibility-msg="<?= Html::encode($eligibility ? $eligibility->getPrimaryMessage() : 'Not eligible') ?>">
                    <i class="fas fa-lock"></i> Not eligible
                </button>
            <?php endif; ?>
        </div>
    </div>
</article>
