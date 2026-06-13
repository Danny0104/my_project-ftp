<?php

use common\models\Application;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

/** @var Application $app */
/** @var int $match */
/** @var array $journey */

$orgName = $app->position ? ($app->position->organization->name ?? 'Organization') : '—';
$positionTitle = $app->position ? $app->position->title : 'Application';
$location = $app->position->location ?? ($app->position->organization->location ?? '');
$statusKey = spAppStatusKey($app->status);
$statusText = Application::getStatusOptions()[$app->status] ?? $app->status;
$journeyStage = spAppJourneyStage($app->status);
$deadline = $app->position
    ? ($app->position->application_deadline ?: ftpEstimatedDeadline((int) $app->position->created_at))
    : null;
$daysLeft = $deadline ? ftpDaysUntil($deadline) : null;

$skills = [];
if ($app->position && $app->position->skills_required) {
    $skills = array_slice(array_filter(array_map('trim', explode(',', $app->position->skills_required))), 0, 3);
}

$drawerPayload = [
    'id' => $app->id,
    'title' => $positionTitle,
    'org' => $orgName,
    'location' => $location ?: '—',
    'status' => $statusText,
    'statusKey' => $statusKey,
    'match' => $match,
    'applied' => date('M d, Y', $app->created_at),
    'updated' => $app->updated_at ? ftpRelativeTime((int) $app->updated_at) : ftpRelativeTime((int) $app->created_at),
    'feedback' => $app->feedback ?: 'No feedback yet.',
    'cover' => $app->cover_letter ? 'Cover letter submitted' : 'No cover letter on file',
    'resume' => $app->resume_url ? 'Resume attached' : 'Using profile CV',
    'viewUrl' => Url::to(['application/view', 'id' => $app->id]),
    'withdrawUrl' => $app->canWithdraw() ? Url::to(['application/withdraw', 'id' => $app->id]) : '',
    'field' => $app->position->field_of_study ?? '',
    'duration' => $app->position->duration ?? '',
];
?>

<article class="spa-card spa-reveal"
         data-spa-card
         data-app-id="<?= (int) $app->id ?>"
         data-app-status="<?= Html::encode($statusKey) ?>"
         data-journey="<?= Html::encode($journeyStage) ?>"
         data-search-text="<?= Html::encode(strtolower($orgName . ' ' . $positionTitle . ' ' . $location)) ?>"
         data-org="<?= Html::encode(strtolower($orgName)) ?>"
         data-applied-ts="<?= (int) $app->created_at ?>"
         data-app-json="<?= Html::encode(json_encode($drawerPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>">
    <div class="spa-card-glow" aria-hidden="true"></div>
    <header class="spa-card-head">
        <div class="spa-card-logo"><?= \common\widgets\ProfileAvatar::widget(['type' => 'organization', 'organization' => $app->position->organization ?? null, 'size' => 'sm', 'fillSlot' => true]) ?></div>
        <div class="spa-card-intro">
            <h3><?= Html::encode($positionTitle) ?></h3>
            <span><?= Html::encode($orgName) ?></span>
        </div>
        <?php if ($match > 0): ?>
            <div class="spa-match-ring" title="Profile match">
                <svg viewBox="0 0 36 36" aria-hidden="true">
                    <circle class="spa-match-bg" cx="18" cy="18" r="15"></circle>
                    <circle class="spa-match-fg" cx="18" cy="18" r="15"
                            stroke-dasharray="<?= (int) round(94 * $match / 100) ?> 94"></circle>
                </svg>
                <span><?= (int) $match ?>%</span>
            </div>
        <?php endif; ?>
    </header>

    <div class="spa-card-meta">
        <span><i class="fas fa-calendar-check"></i> Applied <?= date('M j, Y', $app->created_at) ?></span>
        <?php if ($daysLeft !== null): ?>
            <span class="<?= $daysLeft <= 7 ? 'is-urgent' : '' ?>">
                <i class="fas fa-hourglass-half"></i> <?= (int) $daysLeft ?> days left
            </span>
        <?php endif; ?>
        <?php if ($location): ?>
            <span><i class="fas fa-location-dot"></i> <?= Html::encode(StringHelper::truncate($location, 28)) ?></span>
        <?php endif; ?>
    </div>

    <span class="spa-status spa-status--<?= Html::encode($statusKey) ?>">
        <span class="spa-status-dot" aria-hidden="true"></span>
        <?= Html::encode($statusText) ?>
    </span>

    <div class="spa-journey-mini" aria-label="Application progress">
        <?php foreach ($journey['steps'] as $i => $step):
            $cls = '';
            if ($app->status === Application::STATUS_REJECTED && $i === $journey['index']) {
                $cls = 'is-rejected';
            } elseif ($i < $journey['index']) {
                $cls = 'is-done';
            } elseif ($i === $journey['index']) {
                $cls = 'is-active';
            }
            ?>
            <span class="spa-journey-step <?= $cls ?>" data-label="<?= Html::encode($step['label']) ?>"></span>
            <?php if ($i < count($journey['steps']) - 1): ?>
                <span class="spa-journey-line <?= $i < $journey['index'] ? 'is-done' : '' ?>"></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($skills)): ?>
        <div class="spa-skills">
            <?php foreach ($skills as $skill): ?>
                <span><?= Html::encode($skill) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <footer class="spa-card-foot">
        <?= Html::a('<i class="fas fa-arrow-up-right-from-square"></i> View details', ['application/view', 'id' => $app->id], [
            'class' => 'spa-btn spa-btn--ghost spa-btn--sm',
        ]) ?>
        <?= Html::a('<i class="fas fa-comment"></i> Message', ['message/index'], [
            'class' => 'spa-btn spa-btn--ghost spa-btn--sm',
        ]) ?>
        <?php if ($app->canWithdraw()): ?>
            <?= Html::a('<i class="fas fa-ban"></i> Withdraw', ['application/withdraw', 'id' => $app->id], [
                'class' => 'spa-btn spa-btn--danger spa-btn--sm',
                'data' => ['confirm' => 'Withdraw this application?', 'method' => 'post'],
            ]) ?>
        <?php endif; ?>
        <button type="button" class="spa-btn spa-btn--primary spa-btn--sm" data-spa-open="<?= (int) $app->id ?>">
            Quick view
        </button>
    </footer>
</article>
