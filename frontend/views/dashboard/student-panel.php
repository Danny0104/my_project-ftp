<?php

use common\models\Application;
use common\models\Position;
use common\models\Student;
use common\widgets\ProfileAvatar;
use frontend\assets\StudentDashboardAsset;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var Student $student */
/** @var string|null $studentField */
/** @var Application[] $applications */
/** @var Application[] $recentApplications */
/** @var \common\models\Notification[] $notifications */
/** @var \common\models\Notification[] $announcements */
/** @var int $applicationCount */
/** @var int $acceptedCount */
/** @var int $pendingCount */
/** @var int $underReviewCount */
/** @var int $availablePositionsCount */
/** @var int $unreadNotificationsCount */
/** @var int $unreadMessagesCount */
/** @var int $profileCompletion */
/** @var int $profilePercentile */
/** @var array $profileTasks */
/** @var Position[] $recommendedPositions */
/** @var Position|null $featuredPosition */
/** @var Position[] $upcomingDeadlines */
/** @var string $greeting */
/** @var string $displayName */
/** @var int $interviewCount */

require_once __DIR__ . '/_student_helpers.php';

StudentDashboardAsset::register($this);

$this->title = 'Home';

$statusOptions = Application::getStatusOptions();

// Sort deadlines by soonest (already open listings from closingSoon)
$deadlineList = $upcomingDeadlines;
$deadlineService = new \common\services\PublicPositionService();
usort($deadlineList, static function (Position $a, Position $b) use ($deadlineService) {
    return $deadlineService->effectiveDeadlineTimestamp($a) <=> $deadlineService->effectiveDeadlineTimestamp($b);
});
$deadlineList = array_slice($deadlineList, 0, 4);

// Match scores for recommendations
$oppCards = [];
foreach (array_slice($recommendedPositions, 0, 4) as $pos) {
    $match = 0;
    if ($student instanceof Student && !empty($student->field_of_study)) {
        $match = (int) Yii::$app->eligibility->computeFitScore($student, $pos);
    }
    $oppCards[] = ['position' => $pos, 'match' => $match];
}

$nextTask = null;
foreach ($profileTasks as $task) {
    if (empty($task['done'])) {
        $nextTask = $task;
        break;
    }
}

$insightText = $studentField
    ? 'Roles in ' . $studentField . ' are trending — ' . $availablePositionsCount . ' open now.'
    : $availablePositionsCount . ' internships are live on the marketplace.';

$actionTitle = 'Explore matching internships';
$actionDesc = 'Your profile is ready — browse roles curated for your field and apply in one click.';
$actionUrl = ['position/index'];
$actionLabel = 'Browse opportunities';

if ($nextTask && $profileCompletion < 100) {
    $actionTitle = 'Complete: ' . $nextTask['label'];
    $actionDesc = 'A stronger profile unlocks better match scores and faster approvals.';
    $actionUrl = $nextTask['url'];
    $actionLabel = 'Continue profile';
} elseif ($pendingCount > 0) {
    $actionTitle = $pendingCount . ' application' . ($pendingCount > 1 ? 's' : '') . ' awaiting review';
    $actionDesc = 'Track status and respond to any organization messages promptly.';
    $actionUrl = ['application/index'];
    $actionLabel = 'View applications';
} elseif ($unreadNotificationsCount > 0) {
    $actionTitle = $unreadNotificationsCount . ' unread notification' . ($unreadNotificationsCount > 1 ? 's' : '');
    $actionDesc = 'Stay on top of updates from organizations and your university.';
    $actionUrl = ['notification/index', 'view' => 'notifications'];
    $actionLabel = 'Open inbox';
}

$inProgress = $pendingCount + $underReviewCount;
$segPending = max(0, $pendingCount);
$segReview = max(0, $underReviewCount);
$segSuccess = max(0, $acceptedCount);
$otherCount = max(0, $applicationCount - $segPending - $segReview - $segSuccess);

$firstName = explode(' ', trim($displayName))[0] ?: $displayName;
$isProfileComplete = (int) $profileCompletion >= 100;
?>

<div class="scc is-loading" id="studentCommandCenter" data-scc-page>
    <div class="scc-ambient" aria-hidden="true"></div>

    <header class="scc-hero scc-reveal<?= $isProfileComplete ? ' scc-hero--compact' : '' ?>">
        <div class="scc-hero-inner">
            <div>
                <p class="scc-greeting-line"><?= Html::encode($greeting) ?></p>
                <h1 class="scc-headline"><?= Html::encode($firstName) ?>, your command center</h1>
                <p class="scc-subline"><?= Html::encode($insightText) ?></p>
                <div class="scc-hero-pills">
                    <?php if (!$isProfileComplete): ?>
                        <span class="scc-pill scc-pill--warn">
                            <i class="fas fa-sparkles"></i> Profile <?= (int) $profileCompletion ?>% complete
                        </span>
                    <?php endif; ?>
                    <?php if ($unreadNotificationsCount > 0): ?>
                        <span class="scc-pill">
                            <i class="fas fa-bell"></i>
                            <span data-scc-count="<?= (int) $unreadNotificationsCount ?>"><?= (int) $unreadNotificationsCount ?></span> new
                        </span>
                    <?php endif; ?>
                    <span class="scc-pill">
                        <i class="fas fa-briefcase"></i>
                        <span data-scc-count="<?= (int) $availablePositionsCount ?>"><?= (int) $availablePositionsCount ?></span> open roles
                    </span>
                </div>
            </div>
            <div class="scc-orb-wrap<?= $isProfileComplete ? ' scc-orb-wrap--complete' : '' ?>"<?= $isProfileComplete ? '' : ' aria-label="Profile completion ' . (int) $profileCompletion . ' percent"' ?>>
                <div class="scc-orb">
                    <div class="scc-orb-photo"><?= ProfileAvatar::widget(['type' => 'student', 'student' => $student, 'size' => 'lg', 'lazy' => false, 'fillSlot' => true]) ?></div>
                    <?php if (!$isProfileComplete): ?>
                        <div class="scc-orb-inner">
                            <span class="scc-orb-value" data-scc-count="<?= (int) $profileCompletion ?>" data-scc-suffix="%"><?= (int) $profileCompletion ?>%</span>
                            <span class="scc-orb-label">Ready</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <nav class="scc-command scc-reveal" data-scc-delay="1" aria-label="Quick actions">
        <?= Html::a('<i class="fas fa-compass"></i> Browse roles', ['position/index'], [
            'class' => 'scc-cmd scc-cmd--primary',
            'data-scc-magnetic' => '1',
        ]) ?>
        <?= Html::a('<i class="fas fa-file-lines"></i> Applications', ['application/index'], [
            'class' => 'scc-cmd',
            'data-scc-magnetic' => '1',
        ]) ?>
        <?= Html::a(
            '<i class="fas fa-envelope"></i> Messages' . ($unreadMessagesCount > 0
                ? '<span class="scc-cmd-badge" data-msg-unread-badge data-nav-badge="messages">' . (int) $unreadMessagesCount . '</span>'
                : ''),
            ['message/index'],
            ['class' => 'scc-cmd', 'data-scc-magnetic' => '1']
        ) ?>
        <?= Html::a('<i class="fas fa-user"></i> Profile', ['profile/view-student'], [
            'class' => 'scc-cmd',
            'data-scc-magnetic' => '1',
        ]) ?>
    </nav>

    <?php if ($featuredPosition): ?>
        <?php
        $fMatch = $student ? (int) Yii::$app->eligibility->computeFitScore($student, $featuredPosition) : 0;
        $fOrg = $featuredPosition->organization->name ?? 'Organization';
        ?>
        <div class="scc-spotlight scc-reveal" data-scc-delay="1">
            <div>
                <h3><i class="fas fa-star me-2"></i>Top pick for you</h3>
                <p><?= Html::encode(StringHelper::truncate($featuredPosition->title, 56)) ?> · <?= Html::encode($fOrg) ?> · <?= (int) $fMatch ?>% match</p>
            </div>
            <?= Html::a('View role <i class="fas fa-arrow-right ms-1"></i>', ['position/view', 'id' => $featuredPosition->id], [
                'class' => 'scc-btn scc-btn--primary',
            ]) ?>
        </div>
    <?php endif; ?>

    <div class="scc-main">
        <div class="scc-stack">
            <section class="scc-panel scc-glass scc-reveal" data-scc-delay="2" aria-labelledby="scc-action-heading">
                <div class="scc-panel-head">
                    <h2 id="scc-action-heading"><i class="fas fa-bolt"></i> Requires attention</h2>
                </div>
                <div class="scc-action">
                    <div class="scc-action-icon"><i class="fas fa-arrow-trend-up"></i></div>
                    <div class="scc-action-body">
                        <strong><?= Html::encode($actionTitle) ?></strong>
                        <p><?= Html::encode($actionDesc) ?></p>
                        <?= Html::a(Html::encode($actionLabel) . ' <i class="fas fa-arrow-right"></i>', $actionUrl, [
                            'class' => 'scc-btn scc-btn--primary',
                            'data-scc-magnetic' => '1',
                        ]) ?>
                    </div>
                </div>
            </section>

            <section class="scc-panel scc-glass scc-reveal" data-scc-delay="2" aria-labelledby="scc-rec-heading">
                <div class="scc-panel-head">
                    <h2 id="scc-rec-heading"><i class="fas fa-wand-magic-sparkles"></i> Recommended for you</h2>
                    <?= Html::a('See all', ['position/index'], ['class' => 'scc-link']) ?>
                </div>
                <?php if (!empty($oppCards)): ?>
                    <div class="scc-opp-grid">
                        <?php foreach ($oppCards as ['position' => $pos, 'match' => $match]):
                            $orgName = $pos->organization->name ?? 'Organization';
                            $deadlineMeta = ftpPositionDeadlineMeta($pos);
                            ?>
                            <article class="scc-opp" data-scc-tilt>
                                <div class="scc-opp-top">
                                    <div class="scc-opp-logo"><?= ProfileAvatar::widget(['type' => 'organization', 'organization' => $pos->organization ?? null, 'size' => 'sm', 'fillSlot' => true]) ?></div>
                                    <div class="scc-opp-meta">
                                        <h3 class="scc-opp-title"><?= Html::encode($pos->title) ?></h3>
                                        <p class="scc-opp-org"><?= Html::encode($orgName) ?></p>
                                    </div>
                                    <span class="scc-opp-match"><?= (int) $match ?>%</span>
                                </div>
                                <div class="scc-opp-foot">
                                    <span class="scc-opp-tag">
                                        <i class="fas fa-hourglass-half"></i>
                                        <?= Html::encode($deadlineMeta['label']) ?>
                                    </span>
                                    <div class="scc-opp-actions">
                                        <button type="button" class="scc-opp-icon sp-save-btn" data-save-id="<?= (int) $pos->id ?>" title="Save" aria-label="Save">
                                            <i class="far fa-bookmark"></i>
                                        </button>
                                        <?= Html::a('<i class="fas fa-arrow-right"></i>', ['position/view', 'id' => $pos->id], [
                                            'class' => 'scc-opp-icon',
                                            'title' => 'View',
                                        ]) ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="scc-empty"><?= $isProfileComplete
                        ? 'No recommendations right now — check back as new roles are posted.'
                        : 'No recommendations yet — complete your profile to unlock matches.' ?></p>
                <?php endif; ?>
            </section>

            <?php if ($profileCompletion < 100): ?>
                <section class="scc-panel scc-glass scc-reveal" data-scc-delay="3" aria-labelledby="scc-tasks-heading">
                    <div class="scc-panel-head">
                        <h2 id="scc-tasks-heading"><i class="fas fa-list-check"></i> Profile checklist</h2>
                        <?= Html::a('Edit profile', ['profile/edit-profile'], ['class' => 'scc-link']) ?>
                    </div>
                    <ul class="scc-tasks">
                        <?php foreach ($profileTasks as $task): ?>
                            <li class="scc-task <?= !empty($task['done']) ? 'is-done' : '' ?>">
                                <span class="scc-task-check"><?= !empty($task['done']) ? '✓' : '' ?></span>
                                <span><?= Html::encode($task['label']) ?></span>
                                <?php if (empty($task['done'])): ?>
                                    <?= Html::a('Add', $task['url'], ['class' => 'scc-link']) ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </div>

        <aside class="scc-stack">
            <section class="scc-panel scc-glass scc-reveal" data-scc-delay="2" aria-labelledby="scc-progress-heading">
                <div class="scc-panel-head">
                    <h2 id="scc-progress-heading"><i class="fas fa-route"></i> Application progress</h2>
                    <?= Html::a('Track all', ['application/index'], ['class' => 'scc-link']) ?>
                </div>
                <?php if ($applicationCount > 0): ?>
                    <div class="scc-pipeline" role="presentation">
                        <?php if ($segPending > 0): ?>
                            <span class="scc-pipeline-seg scc-pipeline-seg--pending" style="flex: <?= (int) $segPending ?> 1 0"></span>
                        <?php endif; ?>
                        <?php if ($segReview > 0): ?>
                            <span class="scc-pipeline-seg scc-pipeline-seg--review" style="flex: <?= (int) $segReview ?> 1 0"></span>
                        <?php endif; ?>
                        <?php if ($segSuccess > 0): ?>
                            <span class="scc-pipeline-seg scc-pipeline-seg--success" style="flex: <?= (int) $segSuccess ?> 1 0"></span>
                        <?php endif; ?>
                        <?php if ($otherCount > 0): ?>
                            <span class="scc-pipeline-seg scc-pipeline-seg--muted" style="flex: <?= (int) $otherCount ?> 1 0"></span>
                        <?php endif; ?>
                    </div>
                    <div class="scc-pipeline-legend">
                        <div class="scc-legend-item">
                            <span class="scc-legend-dot" style="background:#94a3b8"></span>
                            <div><strong data-scc-count="<?= (int) $pendingCount ?>">0</strong> Pending</div>
                        </div>
                        <div class="scc-legend-item">
                            <span class="scc-legend-dot" style="background:#6366f1"></span>
                            <div><strong data-scc-count="<?= (int) $inProgress ?>">0</strong> In progress</div>
                        </div>
                        <div class="scc-legend-item">
                            <span class="scc-legend-dot" style="background:#10b981"></span>
                            <div><strong data-scc-count="<?= (int) $acceptedCount ?>">0</strong> Accepted</div>
                        </div>
                        <div class="scc-legend-item">
                            <span class="scc-legend-dot" style="background:var(--scc-muted)"></span>
                            <div><strong data-scc-count="<?= (int) $applicationCount ?>">0</strong> Total</div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="scc-empty">No applications yet. Find a role and apply when you're ready.</p>
                    <?= Html::a('Browse internships', ['position/index'], ['class' => 'scc-btn scc-btn--primary']) ?>
                <?php endif; ?>
            </section>

            <section class="scc-panel scc-glass scc-reveal" data-scc-delay="3" aria-labelledby="scc-deadline-heading">
                <div class="scc-panel-head">
                    <h2 id="scc-deadline-heading"><i class="fas fa-calendar-day"></i> Upcoming deadlines</h2>
                </div>
                <?php if (!empty($deadlineList)): ?>
                    <ul class="scc-deadlines">
                        <?php foreach ($deadlineList as $pos):
                            $deadlineMeta = ftpPositionDeadlineMeta($pos);
                            $days = $deadlineMeta['days'];
                            $urgent = !empty($deadlineMeta['is_urgent']);
                            ?>
                            <li class="scc-deadline <?= $urgent ? 'is-urgent' : '' ?>">
                                <div class="scc-deadline-date">
                                    <?php if ($days === null): ?>
                                        <strong>—</strong>
                                    <?php elseif ($days === 0): ?>
                                        <strong>!</strong>
                                        <span>today</span>
                                    <?php else: ?>
                                        <strong><?= (int) $days ?></strong>
                                        <span>days</span>
                                    <?php endif; ?>
                                </div>
                                <div class="scc-deadline-body">
                                    <?= Html::a(Html::encode(StringHelper::truncate($pos->title, 42)), ['position/view', 'id' => $pos->id]) ?>
                                    <small><?= Html::encode($pos->organization->name ?? '') ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="scc-empty">No deadlines in the next window.</p>
                <?php endif; ?>
            </section>

            <section class="scc-panel scc-glass scc-reveal" data-scc-delay="4" aria-labelledby="scc-activity-heading">
                <div class="scc-panel-head">
                    <h2 id="scc-activity-heading"><i class="fas fa-clock-rotate-left"></i> Recent activity</h2>
                    <?= Html::a('View all', ['notification/index', 'view' => 'notifications'], ['class' => 'scc-link']) ?>
                </div>
                <?php if (!empty($announcements)): ?>
                    <ul class="scc-activity">
                        <?php foreach ($announcements as $note): ?>
                            <li class="<?= (int) $note->is_read === 0 ? 'is-unread' : '' ?>">
                                <span class="scc-activity-dot" aria-hidden="true"></span>
                                <div>
                                    <p><strong><?= Html::encode(StringHelper::truncate($note->title, 48)) ?></strong><br>
                                        <?= Html::encode(StringHelper::truncate($note->message, 80)) ?></p>
                                    <time datetime="<?= date('c', (int) $note->created_at) ?>">
                                        <?= Yii::$app->formatter->asRelativeTime($note->created_at) ?>
                                    </time>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="scc-empty">You're all caught up.</p>
                <?php endif; ?>
            </section>

            <?php if (!empty($recentApplications)): ?>
                <section class="scc-panel scc-glass scc-reveal" data-scc-delay="4" aria-labelledby="scc-recent-heading">
                    <div class="scc-panel-head">
                        <h2 id="scc-recent-heading"><i class="fas fa-paper-plane"></i> Latest applications</h2>
                    </div>
                    <ul class="scc-activity">
                        <?php foreach (array_slice($recentApplications, 0, 3) as $app):
                            if (!$app->position) {
                                continue;
                            }
                            $label = $statusOptions[$app->status] ?? $app->status;
                            ?>
                            <li>
                                <span class="scc-activity-dot" style="background:#6366f1"></span>
                                <div>
                                    <p>
                                        <strong><?= Html::encode(StringHelper::truncate($app->position->title, 40)) ?></strong><br>
                                        Status: <?= Html::encode($label) ?>
                                    </p>
                                    <time><?= Yii::$app->formatter->asRelativeTime($app->created_at) ?></time>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</div>
