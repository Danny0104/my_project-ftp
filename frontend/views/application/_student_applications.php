<?php

use common\models\Application;
use common\models\Position;
use common\models\Student;
use frontend\assets\StudentApplicationsAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var Application[] $applications */

require_once __DIR__ . '/../dashboard/_student_helpers.php';

StudentApplicationsAsset::register($this);

$this->title = 'My Applications';

$statusOptions = Application::getStatusOptions();
$student = Student::findOne(['user_id' => Yii::$app->user->id]);

$stats = [
    'submitted' => 0,
    'interviews' => 0,
    'offers' => 0,
];

$journeyCounts = [
    'applied' => 0,
    'review' => 0,
    'shortlisted' => 0,
    'interview' => 0,
    'accepted' => 0,
];

foreach ($applications as $app) {
    if ($app->status === Application::STATUS_WITHDRAWN) {
        continue;
    }
    $stats['submitted']++;
    $stage = spAppJourneyStage($app->status);
    if (isset($journeyCounts[$stage])) {
        $journeyCounts[$stage]++;
    }
    if (in_array($app->status, [Application::STATUS_ORG_APPROVED, Application::STATUS_UNIVERSITY_APPROVED], true)) {
        $stats['interviews']++;
    }
    if (in_array($app->status, [Application::STATUS_APPROVED, Application::STATUS_COMPLETED], true)) {
        $stats['offers']++;
    }
}

$profileCompletion = $student ? (int) Yii::$app->eligibility->profileCompletionPercent($student) : 0;

$insights = [];
if ($profileCompletion < 100) {
    $insights[] = [
        'icon' => 'fa-user-pen',
        'tone' => 'violet',
        'title' => 'Profile completion at ' . (int) $profileCompletion . '%',
        'body' => 'A complete profile improves match scores and shortlist chances.',
        'cta' => 'Complete profile',
        'url' => ['profile/student'],
    ];
}

$urgentDoc = null;
foreach ($applications as $app) {
    if ($app->status === Application::STATUS_WITHDRAWN) {
        continue;
    }
    if (in_array($app->status, [Application::STATUS_PENDING, Application::STATUS_UNDER_REVIEW], true)
        && empty($app->cover_letter)) {
        $urgentDoc = $app;
        break;
    }
}
if ($urgentDoc) {
    $insights[] = [
        'icon' => 'fa-file-circle-exclamation',
        'tone' => 'amber',
        'title' => 'Application needs documents',
        'body' => 'Add a cover letter for ' . ($urgentDoc->position->title ?? 'your application') . '.',
        'cta' => 'Update application',
        'url' => ['application/view', 'id' => $urgentDoc->id],
    ];
}

foreach ($applications as $app) {
    if (in_array($app->status, [Application::STATUS_ORG_APPROVED, Application::STATUS_UNIVERSITY_APPROVED], true)) {
        $insights[] = [
            'icon' => 'fa-video',
            'tone' => 'blue',
            'title' => 'Interview stage in progress',
            'body' => ($app->position->organization->name ?? 'An organization') . ' is reviewing you for ' . ($app->position->title ?? 'a role') . '.',
            'cta' => 'View application',
            'url' => ['application/view', 'id' => $app->id],
        ];
        break;
    }
}

$recommended = [];
if ($student) {
    $appliedIds = array_map(static fn($a) => (int) $a->position_id, $applications);
    $recoService = new \common\services\OpportunityRecommendationService();
    foreach ($recoService->forYou($student, $appliedIds, 4) as $pos) {
        $eval = Yii::$app->eligibility->evaluateBrowse($student, $pos);
        $recommended[] = [
            'position' => $pos,
            'match' => (int) $eval->matchScore,
            'reason' => $eval->getPrimaryMessage(),
            'skills' => array_slice(array_filter(array_map('trim', explode(',', (string) $pos->skills_required))), 0, 4),
        ];
    }
}

if (count($insights) < 3) {
    $insights[] = [
        'icon' => 'fa-compass',
        'tone' => 'emerald',
        'title' => 'New opportunities available',
        'body' => 'Browse internships matched to your profile and apply in one click.',
        'cta' => 'Explore roles',
        'url' => ['position/index'],
    ];
}
$insights = array_slice($insights, 0, 4);

$exportRows = [];
foreach ($applications as $app) {
    $exportRows[] = [
        $app->position->title ?? '',
        $app->position->organization->name ?? '',
        $statusOptions[$app->status] ?? $app->status,
        date('Y-m-d', $app->created_at),
    ];
}

$journeySteps = [
    ['key' => 'applied', 'label' => 'Applied', 'icon' => 'fa-paper-plane'],
    ['key' => 'review', 'label' => 'Under Review', 'icon' => 'fa-eye'],
    ['key' => 'shortlisted', 'label' => 'Shortlisted', 'icon' => 'fa-star'],
    ['key' => 'interview', 'label' => 'Interview', 'icon' => 'fa-video'],
    ['key' => 'accepted', 'label' => 'Accepted', 'icon' => 'fa-check'],
];

$orgs = [];
foreach ($applications as $app) {
    $n = $app->position->organization->name ?? null;
    if ($n) {
        $orgs[strtolower($n)] = $n;
    }
}
asort($orgs);
?>

<div class="spa" id="spaApplications" data-spa-ready="0">
    <div class="spa-ambient" aria-hidden="true">
        <span class="spa-orb spa-orb--1"></span>
        <span class="spa-orb spa-orb--2"></span>
        <span class="spa-orb spa-orb--3"></span>
        <span class="spa-mesh"></span>
        <span class="spa-particles"></span>
        <i class="spa-float-icon fas fa-briefcase"></i>
        <i class="spa-float-icon fas fa-rocket"></i>
        <i class="spa-float-icon fas fa-graduation-cap"></i>
        <i class="spa-float-icon fas fa-chart-line"></i>
    </div>

    <section class="spa-hero spa-reveal" data-spa-hero>
        <div class="spa-hero-glass">
            <div class="spa-hero-inner">
                <div class="spa-hero-copy">
                    <p class="spa-eyebrow"><i class="fas fa-layer-group"></i> Applications</p>
                    <h1>Your internship pipeline</h1>
                    <p class="spa-hero-lead">Track every role, next step, and decision — without the analytics noise.</p>
                </div>
                <div class="spa-stat-pills">
                    <div class="spa-pill">
                        <i class="fas fa-paper-plane"></i>
                        <div>
                            <strong data-count="<?= (int) $stats['submitted'] ?>">0</strong>
                            <span>Submitted</span>
                        </div>
                    </div>
                    <div class="spa-pill spa-pill--accent">
                        <i class="fas fa-video"></i>
                        <div>
                            <strong data-count="<?= (int) $stats['interviews'] ?>">0</strong>
                            <span>Interviews</span>
                        </div>
                    </div>
                    <div class="spa-pill spa-pill--success">
                        <i class="fas fa-trophy"></i>
                        <div>
                            <strong data-count="<?= (int) $stats['offers'] ?>">0</strong>
                            <span>Offers</span>
                        </div>
                    </div>
                </div>
                <div class="spa-hero-actions">
                    <?= Html::a('<i class="fas fa-search"></i> Browse opportunities', ['position/index'], ['class' => 'spa-btn spa-btn--primary']) ?>
                    <button type="button" class="spa-btn spa-btn--ghost" id="spaExportBtn"><i class="fas fa-download"></i> Export</button>
                </div>
            </div>
        </div>
    </section>

    <section class="spa-actions spa-reveal" aria-label="Smart actions">
        <header class="spa-section-head">
            <h2><i class="fas fa-wand-magic-sparkles"></i> Action center</h2>
            <span>What needs your attention</span>
        </header>
        <div class="spa-action-grid">
            <?php foreach ($insights as $i => $item): ?>
                <a href="<?= Html::encode(\yii\helpers\Url::to($item['url'])) ?>" class="spa-action-card spa-action-card--<?= Html::encode($item['tone']) ?>" style="--spa-delay: <?= (int) $i * 70 ?>ms">
                    <span class="spa-action-icon"><i class="fas <?= Html::encode($item['icon']) ?>"></i></span>
                    <div>
                        <h3><?= Html::encode($item['title']) ?></h3>
                        <p><?= Html::encode($item['body']) ?></p>
                        <span class="spa-action-cta"><?= Html::encode($item['cta']) ?> <i class="fas fa-arrow-right"></i></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if (!empty($applications)): ?>
        <section class="spa-pipeline spa-reveal" id="spaPipeline" aria-label="Application journey">
            <header class="spa-section-head">
                <h2><i class="fas fa-route"></i> Your journey</h2>
                <span>Tap a stage to filter</span>
            </header>
            <div class="spa-pipeline-track">
                <div class="spa-pipeline-fill" id="spaPipelineFill"></div>
                <?php foreach ($journeySteps as $i => $step): ?>
                    <button type="button"
                            class="spa-pipeline-node"
                            data-spa-journey-filter="<?= Html::encode($step['key']) ?>"
                            data-spa-journey-index="<?= (int) $i ?>">
                        <span class="spa-pipeline-icon"><i class="fas <?= $step['icon'] ?>"></i></span>
                        <span class="spa-pipeline-label"><?= Html::encode($step['label']) ?></span>
                        <em class="spa-pipeline-count"><?= (int) ($journeyCounts[$step['key']] ?? 0) ?></em>
                    </button>
                    <?php if ($i < count($journeySteps) - 1): ?>
                        <span class="spa-pipeline-connector" aria-hidden="true"></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="spa-workspace spa-reveal">
            <div class="spa-toolbar">
                <div class="spa-tabs" role="tablist">
                    <button type="button" class="spa-tab is-active" data-spa-filter="all">All</button>
                    <button type="button" class="spa-tab" data-spa-filter="pending">Pending</button>
                    <button type="button" class="spa-tab" data-spa-filter="review">In review</button>
                    <button type="button" class="spa-tab" data-spa-filter="interview">Interview</button>
                    <button type="button" class="spa-tab" data-spa-filter="approved">Accepted</button>
                    <button type="button" class="spa-tab" data-spa-filter="rejected">Rejected</button>
                </div>
                <div class="spa-filters">
                    <label class="spa-search">
                        <i class="fas fa-search"></i>
                        <input type="search" id="spaSearch" placeholder="Search roles or organizations…" autocomplete="off">
                    </label>
                    <select class="form-select form-select-sm spa-select" id="spaSort" aria-label="Sort">
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                        <option value="org">Organization A–Z</option>
                    </select>
                    <select class="form-select form-select-sm spa-select" id="spaOrgFilter" aria-label="Organization">
                        <option value="">All organizations</option>
                        <?php foreach ($orgs as $key => $name): ?>
                            <option value="<?= Html::encode($key) ?>"><?= Html::encode($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="spa-skeleton-grid" id="spaSkeleton" aria-hidden="true">
                <?php for ($s = 0; $s < 6; $s++): ?>
                    <div class="spa-skeleton-card">
                        <div class="spa-shimmer"></div>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="spa-cards" id="spaCards" hidden>
                <?php foreach ($applications as $i => $app):
                    $match = 0;
                    if ($student && $app->position) {
                        $match = (int) Yii::$app->eligibility->evaluate($student, $app->position, 'browse')->matchScore;
                    }
                    echo $this->render('_spa_application_card', [
                        'app' => $app,
                        'match' => $match,
                        'journey' => spAppJourneyTimeline($app->status),
                    ]);
                endforeach; ?>
            </div>

            <div class="spa-empty-filter" id="spaEmptyFilter" hidden>
                <i class="fas fa-filter-circle-xmark"></i>
                <p>No applications match your filters.</p>
                <button type="button" class="spa-btn spa-btn--ghost" id="spaClearFilters">Clear filters</button>
            </div>
        </section>
    <?php else: ?>
        <section class="spa-empty spa-reveal">
            <div class="spa-empty-glow" aria-hidden="true"></div>
            <div class="spa-loader-orbit" aria-hidden="true"><span></span><span></span><span></span></div>
            <h2>Start your journey</h2>
            <p>No applications yet. Explore internships matched to your profile and submit your first application.</p>
            <?= Html::a('<i class="fas fa-compass"></i> Browse opportunities', ['position/index'], ['class' => 'spa-btn spa-btn--primary']) ?>
        </section>
    <?php endif; ?>

    <?php if (!empty($recommended)): ?>
        <section class="spa-recommend spa-reveal" id="spaRecommend">
            <header class="spa-section-head">
                <h2><i class="fas fa-sparkles"></i> Recommended for you</h2>
                <span>Roles aligned with your profile</span>
            </header>
            <div class="spa-recommend-grid">
                <?php foreach ($recommended as $i => $rec):
                    $pos = $rec['position'];
                    $org = $pos->organization->name ?? 'Organization';
                    ?>
                    <article class="spa-rec-card spa-reveal" style="--spa-delay: <?= (int) $i * 60 ?>ms">
                        <div class="spa-rec-head">
                            <div class="spa-rec-logo"><?= \common\widgets\ProfileAvatar::widget(['type' => 'organization', 'organization' => $pos->organization ?? null, 'size' => 'sm', 'fillSlot' => true]) ?></div>
                            <div>
                                <h3><?= Html::encode($pos->title) ?></h3>
                                <span><?= Html::encode($org) ?></span>
                            </div>
                            <span class="spa-rec-match"><?= (int) $rec['match'] ?>% match</span>
                        </div>
                        <p class="spa-rec-why"><strong>Why it matches:</strong> <?= Html::encode(StringHelper::truncate($rec['reason'], 120)) ?></p>
                        <?php if (!empty($rec['skills'])): ?>
                            <div class="spa-skills spa-skills--rec">
                                <?php foreach ($rec['skills'] as $skill): ?>
                                    <span><?= Html::encode($skill) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <footer class="spa-rec-foot">
                            <?= Html::a('View role', ['position/view', 'id' => $pos->id], ['class' => 'spa-btn spa-btn--ghost spa-btn--sm']) ?>
                            <?= Html::a('<i class="fas fa-bolt"></i> Quick apply', ['position/view', 'id' => $pos->id, '#' => 'apply'], ['class' => 'spa-btn spa-btn--primary spa-btn--sm']) ?>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<div class="spa-drawer-backdrop" id="spaDrawerBackdrop" hidden></div>
<aside class="spa-drawer" id="spaDrawer" aria-hidden="true">
    <header class="spa-drawer-head">
        <div>
            <h2 id="spaDrawerTitle">Application</h2>
            <p id="spaDrawerOrg"></p>
        </div>
        <button type="button" class="spa-icon-btn" id="spaDrawerClose" aria-label="Close"><i class="fas fa-times"></i></button>
    </header>
    <div class="spa-drawer-body">
        <span class="spa-status" id="spaDrawerStatus"></span>
        <div class="spa-drawer-grid">
            <div><span>Match</span><strong id="spaDrawerMatch">—</strong></div>
            <div><span>Applied</span><strong id="spaDrawerApplied">—</strong></div>
            <div><span>Location</span><strong id="spaDrawerLocation">—</strong></div>
            <div><span>Updated</span><strong id="spaDrawerUpdated">—</strong></div>
        </div>
        <section class="spa-drawer-section">
            <h3><i class="fas fa-route"></i> Journey</h3>
            <ul class="spa-drawer-timeline" id="spaDrawerTimeline"></ul>
        </section>
        <section class="spa-drawer-section">
            <h3><i class="fas fa-file-lines"></i> Documents</h3>
            <p class="small mb-1" id="spaDrawerCover"></p>
            <p class="small mb-0" id="spaDrawerResume"></p>
        </section>
        <section class="spa-drawer-section">
            <h3><i class="fas fa-comment-dots"></i> Feedback</h3>
            <p class="small mb-0" id="spaDrawerFeedback"></p>
        </section>
    </div>
    <footer class="spa-drawer-foot">
        <a href="#" class="spa-btn spa-btn--primary" id="spaDrawerView">View details</a>
        <a href="<?= \yii\helpers\Url::to(['message/index']) ?>" class="spa-btn spa-btn--ghost">Message organization</a>
        <a href="#" class="spa-btn spa-btn--danger" id="spaDrawerWithdraw" hidden>Withdraw</a>
    </footer>
</aside>

<script>
window.spAtExportData = <?= Json::htmlEncode($exportRows) ?>;
</script>
