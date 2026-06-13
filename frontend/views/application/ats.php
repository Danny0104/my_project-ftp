<?php
/** @var yii\web\View $this */
/** @var common\models\Application[] $applications */
/** @var common\models\Organization $organization */

use common\models\Application;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

$this->title = 'Applications ATS';

$columns = [
    Application::STATUS_PENDING => 'New',
    Application::STATUS_UNDER_REVIEW => 'Under Review',
    Application::STATUS_ORG_APPROVED => 'Shortlisted',
    Application::STATUS_UNIVERSITY_APPROVED => 'Interview',
    Application::STATUS_APPROVED => 'Approved',
    Application::STATUS_REJECTED => 'Rejected',
    Application::STATUS_COMPLETED => 'Hired',
];
$byStatus = [];
foreach (array_keys($columns) as $status) {
    $byStatus[$status] = [];
}
foreach ($applications as $app) {
    if (!array_key_exists($app->status, $byStatus)) {
        continue;
    }
    $byStatus[$app->status][] = $app;
}

function atsNextStatus(string $current): ?string
{
    $flow = [
        Application::STATUS_PENDING => Application::STATUS_UNDER_REVIEW,
        Application::STATUS_UNDER_REVIEW => Application::STATUS_ORG_APPROVED,
        Application::STATUS_ORG_APPROVED => Application::STATUS_UNIVERSITY_APPROVED,
        Application::STATUS_UNIVERSITY_APPROVED => Application::STATUS_APPROVED,
        Application::STATUS_APPROVED => Application::STATUS_COMPLETED,
    ];
    return $flow[$current] ?? null;
}
?>

<div class="org-page-header">
    <div>
        <h1>Applications ATS</h1>
        <p>Kanban workflow for screening, shortlisting, interviewing, and hiring internship candidates.</p>
    </div>
    <div class="org-page-actions">
        <a class="org-btn org-btn-ghost" href="<?= Url::to(['position/index']) ?>"><i class="fas fa-briefcase"></i> Opportunities</a>
        <a class="org-btn org-btn-primary" href="<?= Url::to(['message/index']) ?>"><i class="fas fa-comments"></i> Messages</a>
    </div>
</div>

<section data-org-ats>
    <div class="org-ats-toolbar">
        <div class="chips">
            <button type="button" class="org-chip is-active" data-org-ats-filter="all">All pipelines</button>
            <button type="button" class="org-chip" data-org-ats-filter="top">Top matches</button>
            <button type="button" class="org-chip" data-org-ats-filter="risk">At-risk</button>
        </div>
        <input class="org-input" id="orgAtsSearch" placeholder="Search candidate or role…" />
    </div>

    <div class="org-kanban">
        <?php foreach ($columns as $status => $label): ?>
            <?php $items = $byStatus[$status] ?? []; ?>
            <div class="org-col" data-col="<?= Html::encode($status) ?>">
                <div class="org-col-head">
                    <h3><?= Html::encode($label) ?></h3>
                    <span class="org-col-count"><?= count($items) ?></span>
                </div>

                <?php foreach ($items as $app): ?>
                    <?php
                    $studentName = $app->student && $app->student->user ? ($app->student->user->username ?? 'Student') : ('Student #' . (int) $app->student_id);
                    $program = $app->position->title ?? 'Internship';
                    $skills = array_filter(array_map('trim', explode(',', (string)($app->student->skills ?? ''))));
                    $next = atsNextStatus($app->status);
                    $score = min(98, 50 + (int) round((float)($app->student->gpa ?? 2.4) * 10));
                    $isTop = $score >= 80;
                    $isAtRisk = $score < 60 || ((time() - (int) $app->created_at) > 14 * 86400 && in_array($app->status, [Application::STATUS_PENDING, Application::STATUS_UNDER_REVIEW], true));
                    ?>
                    <article class="org-app-card org-app-card--with-avatar"
                             draggable="true"
                             data-id="<?= (int) $app->id ?>"
                             data-status="<?= Html::encode($app->status) ?>"
                             data-top-match="<?= $isTop ? '1' : '0' ?>"
                             data-at-risk="<?= $isAtRisk ? '1' : '0' ?>"
                             data-search="<?= Html::encode(strtolower($studentName . ' ' . $program)) ?>">
                        <div class="org-app-card-head">
                            <?= ProfileAvatar::widget(['type' => 'student', 'student' => $app->student ?? null, 'size' => 'sm']) ?>
                            <h4><?= Html::encode($studentName) ?></h4>
                        </div>
                        <p><?= Html::encode($program) ?></p>
                        <p>Match score: <strong><?= $score ?>%</strong> · GPA: <?= Html::encode((string)($app->student->gpa ?? 'N/A')) ?></p>
                        <div class="org-skill-row">
                            <?php foreach (array_slice($skills, 0, 3) as $skill): ?>
                                <span class="org-skill"><?= Html::encode($skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="org-app-actions">
                            <?php if ($next): ?>
                                <button type="button" data-id="<?= (int) $app->id ?>" data-stage-update="<?= Html::encode($next) ?>">
                                    Move next
                                </button>
                            <?php endif; ?>
                            <?php if ($app->status !== Application::STATUS_REJECTED): ?>
                                <button type="button" data-id="<?= (int) $app->id ?>" data-stage-update="<?= Application::STATUS_REJECTED ?>">
                                    Reject
                                </button>
                            <?php endif; ?>
                            <a href="<?= Url::to(['message/index']) ?>">Message</a>
                            <?php if ($app->student && $app->student->cv): ?>
                                <a href="<?= Url::to(['/organization/students/download-cv', 'id' => (int) $app->student_id]) ?>" data-pjax="0">CV</a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>


