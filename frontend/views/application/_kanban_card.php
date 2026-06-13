<?php

use common\models\Application;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

/** @var Application $app */
/** @var int $match */
/** @var array $timeline */

$orgName = $app->position ? ($app->position->organization->name ?? 'N/A') : 'N/A';
$positionTitle = $app->position ? $app->position->title : 'N/A';
$location = $app->position->location ?? ($app->position->organization->location ?? '—');
$statusKey = spAppStatusKey($app->status);
$statusText = Application::getStatusOptions()[$app->status] ?? $app->status;
$column = spAppKanbanColumn($app->status);
$deadline = $app->position
    ? ($app->position->application_deadline ?: ftpEstimatedDeadline((int) $app->position->created_at))
    : null;
$daysLeft = $deadline ? ftpDaysUntil($deadline) : null;

$drawerPayload = [
    'id' => $app->id,
    'title' => $positionTitle,
    'org' => $orgName,
    'location' => $location,
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

<article class="sp-at-kanban-card sp-at-enter"
         data-app-id="<?= (int) $app->id ?>"
         data-app-status="<?= Html::encode($statusKey) ?>"
         data-app-column="<?= Html::encode($column) ?>"
         data-search-text="<?= Html::encode(strtolower($orgName . ' ' . $positionTitle . ' ' . $location . ' ' . ($app->position->field_of_study ?? ''))) ?>"
         data-org="<?= Html::encode(strtolower($orgName)) ?>"
         data-location="<?= Html::encode(strtolower((string) $location)) ?>"
         data-type="<?= Html::encode(strtolower((string) ($app->position->category ?? $app->position->field_of_study ?? ''))) ?>"
         data-applied-ts="<?= (int) $app->created_at ?>"
         data-app-json="<?= Html::encode(json_encode($drawerPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>">
    <div class="sp-at-kanban-card-glow"></div>
    <header class="sp-at-kanban-card-head">
        <div class="sp-at-kanban-logo"><?= \common\widgets\ProfileAvatar::widget(['type' => 'organization', 'organization' => $app->position->organization ?? null, 'size' => 'sm', 'fillSlot' => true]) ?></div>
        <div class="sp-at-kanban-meta">
            <h3><?= Html::encode(StringHelper::truncate($positionTitle, 42)) ?></h3>
            <span><?= Html::encode($orgName) ?></span>
        </div>
        <?php if ($match > 0): ?>
            <span class="sp-at-match-badge"><?= (int) $match ?>%</span>
        <?php endif; ?>
    </header>
    <div class="sp-at-kanban-details">
        <?php if ($location && $location !== '—'): ?>
            <span><i class="fas fa-location-dot"></i> <?= Html::encode(StringHelper::truncate($location, 24)) ?></span>
        <?php endif; ?>
        <span><i class="fas fa-calendar"></i> <?= date('M d', $app->created_at) ?></span>
        <?php if ($daysLeft !== null): ?>
            <span class="<?= $daysLeft <= 7 ? 'is-urgent' : '' ?>"><i class="fas fa-hourglass-half"></i> <?= (int) $daysLeft ?>d</span>
        <?php endif; ?>
    </div>
    <span class="sp-at-status-pill sp-at-status-pill--<?= Html::encode($statusKey) ?>">
        <span class="sp-at-status-pulse"></span><?= Html::encode($statusText) ?>
    </span>
    <div class="sp-at-mini-track" aria-label="Progress">
        <?php foreach ($timeline['steps'] as $i => $step): ?>
            <?php
            $cls = '';
            if ($i < $timeline['activeIndex']) {
                $cls = 'is-done';
            } elseif ($i === $timeline['activeIndex']) {
                $cls = 'is-active';
            }
            if ($timeline['isRejected'] && $i === $timeline['activeIndex']) {
                $cls = 'is-rejected';
            }
            ?>
            <span class="<?= $cls ?>" title="<?= Html::encode($step['label']) ?>"></span>
        <?php endforeach; ?>
    </div>
    <footer class="sp-at-kanban-foot">
        <button type="button" class="sp-at-kanban-open" data-at-open="<?= (int) $app->id ?>">
            <i class="fas fa-arrow-up-right-from-square"></i> Open
        </button>
        <div class="sp-at-kanban-menu dropdown">
            <button type="button" class="sp-at-icon-btn" data-bs-toggle="dropdown" aria-label="Actions"><i class="fas fa-ellipsis"></i></button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><?= Html::a('<i class="fas fa-eye me-2"></i> Full details', ['application/view', 'id' => $app->id], ['class' => 'dropdown-item']) ?></li>
                <?php if ($app->position): ?>
                    <li><?= Html::a('<i class="fas fa-briefcase me-2"></i> View role', ['position/view', 'id' => $app->position->id], ['class' => 'dropdown-item']) ?></li>
                <?php endif; ?>
                <?php if ($app->canWithdraw()): ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><?= Html::a('<i class="fas fa-ban me-2 text-danger"></i> Withdraw', ['application/withdraw', 'id' => $app->id], [
                        'class' => 'dropdown-item text-danger',
                        'data' => ['confirm' => 'Withdraw this application?', 'method' => 'post'],
                    ]) ?></li>
                <?php endif; ?>
            </ul>
        </div>
    </footer>
</article>
