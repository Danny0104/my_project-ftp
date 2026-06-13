<?php

/** @var yii\web\View $this */
/** @var common\models\Position $model */
/** @var common\models\Application|null $application */
/** @var common\models\Student|null $student */
/** @var common\services\EligibilityResult|null $eligibility */
/** @var int|null $profileCompletion */
/** @var array{matched: int, total: int, percent: int} $skillsOverlap */
/** @var int $applicationCount */
/** @var array<int, string> $allowedFieldNames */
/** @var common\models\Position[] $similarPositions */
/** @var int $orgActiveCount */
/** @var int|null $orgHireRate */
/** @var array|null $deadlineMeta */
/** @var bool|null $acceptingApplications */
use common\models\PlatformRegulation;
use common\services\PublicPositionService;
use yii\helpers\Html;
use yii\helpers\Url;

$identity = Yii::$app->user->identity;
$isGuest = Yii::$app->user->isGuest;
$isStudent = !$isGuest && $identity && $identity->role === 'student';
$isOrganization = !$isGuest && $identity && $identity->role === 'organization';

$hasApplied = $application !== null;

$blob = strtolower(trim(implode(' ', array_filter([
    (string) $model->location,
    (string) $model->criteria,
    (string) $model->description,
]))));
$workType = 'on-site';
if (str_contains($blob, 'hybrid')) {
    $workType = 'hybrid';
} elseif (str_contains($blob, 'remote')) {
    $workType = 'remote';
}
$workTypeClass = [
    'remote' => 'pd-badge--type-remote',
    'hybrid' => 'pd-badge--type-hybrid',
    'on-site' => 'pd-badge--type-onsite',
][$workType];
$workTypeLabel = ['on-site' => 'On-site', 'hybrid' => 'Hybrid', 'remote' => 'Remote'][$workType];

$publicService = new PublicPositionService();
$deadlineMeta = $deadlineMeta ?? $publicService->deadlineMeta($model);
$acceptingApplications = $acceptingApplications ?? $publicService->isAcceptingApplications($model);
$deadlineTs = $deadlineMeta['timestamp'];
$daysLeft = $deadlineMeta['days'];

$minGpaDisplay = $model->min_gpa;
if ($minGpaDisplay === null || $minGpaDisplay === '') {
    $minGpaDisplay = PlatformRegulation::getValue('min_gpa_default');
}

$org = $model->organization;

$skillTags = array_filter(array_map('trim', explode(',', (string) $model->skills_required)));

$canApplyStudent = $acceptingApplications && $isStudent && $student && $eligibility && $eligibility->eligible && !$hasApplied && !$isOrganization;
$showApplyWizard = $canApplyStudent;
$applicationsClosed = !$acceptingApplications;
$applyBlockedStudent = $isStudent && $student && $eligibility && !$eligibility->eligible && !$hasApplied;

$matchScore = ($isStudent && $student && $eligibility) ? (int) $eligibility->matchScore : 0;
$showMatchRing = $isStudent && $student && $eligibility;

$bestFit = $eligibility && $eligibility->badge === 'best_fit';

$profileReadiness = null;
$applicationQuestions = [];
if ($showApplyWizard && $student && $eligibility) {
    $profileReadiness = (new \common\services\ApplicationWizardService())->buildProfileReadiness(
        $student,
        $identity,
        $eligibility,
        $profileCompletion
    );
    $applicationQuestions = $model->getApplicationQuestions();
}

$isStudentShell = $isStudent; // Same layout routing as Opportunities index.

$shareUrl = Yii::$app->request->absoluteUrl;
?>

<?php
$bookmarkUrl = '';
if ($isStudent && !Yii::$app->user->isGuest) {
    $bookmarkUrl = \yii\helpers\Url::to(['/position/toggle-bookmark']);
}
?>
<div class="pd-page" data-position-id="<?= (int) $model->id ?>"<?= $bookmarkUrl !== '' ? ' data-bookmark-url="' . \yii\helpers\Html::encode($bookmarkUrl) . '"' : '' ?>>
    <?php if (!$isStudentShell): ?>
        <div class="container py-2">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><?= Html::a('Home', ['/site/index']) ?></li>
                    <li class="breadcrumb-item"><?= Html::a('Internships', ['/position/index']) ?></li>
                    <li class="breadcrumb-item active text-truncate" style="max-width: 280px"><?= Html::encode($model->title) ?></li>
                </ol>
            </nav>
        </div>
    <?php endif; ?>

    <div class="<?= $isStudentShell ? '' : 'container' ?>">
        <nav class="pd-section-nav" aria-label="On this page">
            <a href="#section-overview"><?= $isStudentShell ? 'Overview' : 'Overview' ?></a>
            <a href="#section-role"><?= Html::encode('Role') ?></a>
            <a href="#section-requirements"><?= Html::encode('Requirements') ?></a>
            <a href="#section-org"><?= Html::encode('Organization') ?></a>
            <a href="#section-timeline"><?= Html::encode('Timeline') ?></a>
            <a href="#section-supervisor"><?= Html::encode('University') ?></a>
        </nav>

        <div class="pd-hero pd-reveal">
            <div class="pd-hero-inner">
                <div class="pd-hero-top">
                    <div class="d-flex gap-3 flex-grow-1">
                        <div class="pd-org-logo" aria-hidden="true"><?= \common\widgets\ProfileAvatar::widget(['type' => 'organization', 'organization' => $org, 'size' => 'md', 'fillSlot' => true]) ?></div>
                        <div class="flex-grow-1 min-w-0">
                            <h1 class="pd-hero-title"><?= Html::encode($model->title) ?></h1>
                            <p class="pd-hero-org">
                                <span><?= Html::encode($org->name ?? 'Partner organization') ?></span>
                                <?php if ($org && $org->isVerified()): ?>
                                    <span class="pd-badge pd-badge--verified"><i class="fas fa-shield-halved"></i> Verified partner</span>
                                <?php endif; ?>
                                <?php if ($bestFit): ?>
                                    <span class="pd-badge pd-badge--best"><i class="fas fa-star"></i> Best fit</span>
                                <?php endif; ?>
                                <span class="pd-badge <?= Html::encode($workTypeClass) ?>"><?= Html::encode($workTypeLabel) ?></span>
                            </p>
                            <div class="pd-hero-meta">
                                <?php if ($model->location): ?>
                                    <span class="pd-chip"><i class="fas fa-location-dot"></i><?= Html::encode($model->location) ?></span>
                                <?php endif; ?>
                                <?php if ($model->duration): ?>
                                    <span class="pd-chip"><i class="fas fa-clock"></i><?= Html::encode($model->duration) ?></span>
                                <?php endif; ?>
                                <span class="pd-chip"><i class="fas fa-users"></i><?= (int) $applicationCount ?> applicants</span>
                                <span class="pd-chip"><i class="fas fa-calendar-plus"></i>Posted <?= Yii::$app->formatter->asDate($model->created_at, 'medium') ?></span>
                                <span class="pd-chip<?= $deadlineMeta['is_closed'] ? ' pd-chip--closed' : ($deadlineMeta['is_urgent'] ? ' pd-chip--urgent' : '') ?>">
                                    <i class="fas fa-hourglass-half"></i><?= Html::encode($deadlineMeta['label']) ?> · <?= Yii::$app->formatter->asDate($deadlineTs, 'medium') ?>
                                </span>
                            </div>
                            <div class="pd-hero-actions">
                                <?php if ($applicationsClosed): ?>
                                    <button type="button" class="pd-btn-primary" disabled>
                                        <i class="fas fa-lock me-2"></i>Applications closed
                                    </button>
                                    <p class="pd-hero-closed-note mb-0">This internship is no longer accepting applications.</p>
                                <?php elseif ($isGuest): ?>
                                    <?= Html::a('<i class="fas fa-right-to-bracket me-2"></i>Sign in to apply', ['/site/login'], ['class' => 'pd-btn-primary']) ?>
                                <?php elseif ($isOrganization): ?>
                                    <?= Html::a('<i class="fas fa-building me-2"></i>Organization dashboard', ['/site/index'], ['class' => 'pd-btn-ghost']) ?>
                                <?php elseif ($hasApplied): ?>
                                    <?= Html::a('<i class="fas fa-file-lines me-2"></i>View application', ['application/view', 'id' => $application->id], ['class' => 'pd-btn-primary']) ?>
                                <?php elseif ($applyBlockedStudent): ?>
                                    <button type="button" class="pd-btn-primary" disabled aria-describedby="pd-restrict-help">
                                        <i class="fas fa-lock me-2"></i>Apply not available
                                    </button>
                                    <button type="button" class="pd-btn-ghost" data-bs-toggle="modal" data-bs-target="#pdRestrictedModal">
                                        <i class="fas fa-circle-info me-2"></i>Why?
                                    </button>
                                <?php elseif ($showApplyWizard): ?>
                                    <button type="button" class="pd-btn-primary" data-bs-toggle="modal" data-bs-target="#pdApplyModal" id="pdHeroApply">
                                        <i class="fas fa-paper-plane me-2"></i>Apply now
                                    </button>
                                <?php else: ?>
                                    <?= Html::a('<i class="fas fa-user-pen me-2"></i>Complete profile', ['profile/edit-profile'], ['class' => 'pd-btn-primary']) ?>
                                <?php endif; ?>

                                <button type="button" class="pd-btn-ghost" data-pd-bookmark aria-pressed="false" title="Save to your device">
                                    <i class="fas fa-bookmark"></i><span class="pd-bookmark-label ms-1">Save</span>
                                </button>
                                <button type="button" class="pd-btn-ghost" data-bs-toggle="modal" data-bs-target="#pdShareModal">
                                    <i class="fas fa-share-nodes"></i><span class="ms-1 d-none d-sm-inline">Share</span>
                                </button>
                                <?= Html::a('<i class="fas fa-flag"></i><span class="ms-1 d-none d-sm-inline">Report</span>', ['site/contact'], ['class' => 'pd-btn-ghost']) ?>
                            </div>
                            <?php if ($applyBlockedStudent): ?>
                                <p id="pd-restrict-help" class="small text-white-50 mt-2 mb-0">Eligibility requirements are not met. Open “Why?” for details.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($showMatchRing): ?>
                        <div class="pd-hero-aside">
                            <div class="pd-match-ring" role="img" aria-label="Match score <?= (int) $matchScore ?> out of 100">
                                <svg width="96" height="96" viewBox="0 0 96 96" aria-hidden="true">
                                    <defs>
                                        <linearGradient id="pdMatchGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#38bdf8"/>
                                            <stop offset="100%" stop-color="#6366f1"/>
                                        </linearGradient>
                                    </defs>
                                    <circle class="pd-match-ring__bg" cx="48" cy="48" r="40"/>
                                    <circle class="pd-match-ring__progress" cx="48" cy="48" r="40" data-score="<?= (int) $matchScore ?>"/>
                                </svg>
                                <div class="pd-match-ring__label">
                                    <span><?= (int) $matchScore ?>%</span>
                                    <span class="pd-match-ring__sub">Match</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <?php if ($isStudent && $student && $eligibility): ?>
                    <div class="pd-elig pd-reveal <?= $eligibility->eligible ? 'pd-elig--ok' : 'pd-elig--warn' ?>" id="section-overview">
                        <div class="pd-elig-title">
                            <?php if ($eligibility->eligible): ?>
                                <i class="fas fa-circle-check"></i> You are eligible to apply
                            <?php else: ?>
                                <i class="fas fa-triangle-exclamation"></i> You are not eligible yet
                            <?php endif; ?>
                        </div>
                        <p class="mb-0 small text-secondary"><?= Html::encode($eligibility->getPrimaryMessage()) ?></p>
                        <?php if ($profileCompletion !== null): ?>
                            <div class="pd-stat-grid">
                                <div class="pd-stat"><strong><?= (int) $profileCompletion ?>%</strong><span>Profile</span></div>
                                <div class="pd-stat"><strong><?= (int) $skillsOverlap['percent'] ?>%</strong><span>Skill overlap</span></div>
                                <div class="pd-stat"><strong><?= (int) $matchScore ?></strong><span>Match score</span></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($eligibility->requirements)): ?>
                            <ul class="pd-req-list">
                                <?php foreach ($eligibility->requirements as $req): ?>
                                    <li class="<?= !empty($req['met']) ? '' : 'is-miss' ?>">
                                        <i class="fas <?= !empty($req['met']) ? 'fa-check' : 'fa-minus' ?>"></i>
                                        <span><?= Html::encode($req['label']) ?><?= !empty($req['met']) ? '' : ' — action needed' ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!$eligibility->eligible): ?>
                            <div class="mt-3 d-flex flex-wrap gap-2">
                                <?= Html::a('<i class="fas fa-user-pen me-2"></i>Improve profile', ['profile/edit-profile'], ['class' => 'pd-btn-primary']) ?>
                                <button type="button" class="pd-btn-ghost" data-bs-toggle="modal" data-bs-target="#pdRestrictedModal">View all reasons</button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($isStudent && !$student): ?>
                    <div class="pd-elig pd-elig--warn pd-reveal" id="section-overview">
                        <div class="pd-elig-title"><i class="fas fa-user-plus"></i> Complete your student profile</div>
                        <p class="small text-secondary mb-0">We need your academic record and CV before you can apply or see match insights.</p>
                        <div class="mt-3"><?= Html::a('Go to profile', ['profile/edit-profile'], ['class' => 'pd-btn-primary']) ?></div>
                    </div>
                <?php else: ?>
                    <div class="pd-glass pd-section pd-reveal" id="section-overview">
                        <h2><i class="fas fa-compass"></i>Overview</h2>
                        <p class="pd-prose mb-0">Sign in with your student account to see personalized match scores, eligibility checks, and one-click apply. This listing is validated by the Field Training Platform.</p>
                    </div>
                <?php endif; ?>

                <section class="pd-glass pd-section pd-reveal" id="section-role">
                    <h2><i class="fas fa-bullseye"></i>About this opportunity</h2>
                    <div class="pd-prose"><?= nl2br(Html::encode((string) $model->description)) ?: '<p class="text-muted mb-0">The host organization has not added a long description yet.</p>' ?></div>
                    <h3>Internship objectives & outcomes</h3>
                    <p class="pd-prose small">Gain practical experience aligned with your program, contribute to real projects, and document measurable learning outcomes for your faculty supervisor.</p>
                    <h3>Responsibilities</h3>
                    <div class="pd-prose"><?= $model->criteria ? nl2br(Html::encode($model->criteria)) : '<p class="text-muted small mb-0">See the description above and skills tags for day-to-day expectations.</p>' ?></div>
                </section>

                <section class="pd-glass pd-section pd-reveal" id="section-requirements">
                    <h2><i class="fas fa-list-check"></i>Requirements & skills</h2>
                    <h3>Allowed fields of study</h3>
                    <?php if (!empty($allowedFieldNames)): ?>
                        <div class="pd-tag-row mb-3">
                            <?php foreach ($allowedFieldNames as $fname): ?>
                                <span class="pd-tag"><?= Html::encode($fname) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="small text-muted">No taxonomy fields linked — organization should update allowed programs.</p>
                    <?php endif; ?>

                    <h3>Academic level & GPA</h3>
                    <ul class="small text-secondary">
                        <li><strong>Academic level:</strong> <?= $model->academic_level_required ? Html::encode($model->academic_level_required) : 'Any (not specified)' ?></li>
                        <li><strong>Minimum GPA:</strong> <?= Html::encode((string) $minGpaDisplay) ?></li>
                        <li><strong>Duration:</strong> <?= $model->duration ? Html::encode($model->duration) : 'Not specified' ?></li>
                    </ul>

                    <h3>Skills & technologies</h3>
                    <?php if (!empty($skillTags)): ?>
                        <div class="pd-tag-row">
                            <?php foreach ($skillTags as $tag): ?>
                                <span class="pd-tag"><?= Html::encode($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="small text-muted">No explicit skill tags — review the description and criteria.</p>
                    <?php endif; ?>

                    <h3>Benefits & experience</h3>
                    <p class="pd-prose small mb-0">Stipend, certificate, mentorship, and conversion to full-time roles depend on the host organization — confirm details during interview. Platform enforces academic eligibility only; compensation is between you and the employer.</p>
                </section>

                <section class="pd-glass pd-section pd-reveal" id="section-org">
                    <h2><i class="fas fa-building"></i>Organization</h2>
                    <?php if ($org): ?>
                        <div class="pd-prose"><?= nl2br(Html::encode((string) $org->description)) ?: '<span class="text-muted">No company bio yet.</span>' ?></div>
                        <div class="pd-tag-row mb-2">
                            <?php if ($model->category): ?>
                                <span class="pd-tag"><?= Html::encode($model->category) ?></span>
                            <?php endif; ?>
                            <span class="pd-tag">Active listings: <?= (int) $orgActiveCount ?></span>
                            <?php if ($orgHireRate !== null): ?>
                                <span class="pd-tag">Positive outcomes: ~<?= (int) $orgHireRate ?>%</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($org->website): ?>
                                <?= Html::a('<i class="fas fa-globe me-1"></i>Website', $org->website, ['class' => 'btn btn-sm btn-outline-primary', 'target' => '_blank', 'rel' => 'noopener noreferrer']) ?>
                            <?php endif; ?>
                            <?php if ($org->location): ?>
                                <span class="btn btn-sm btn-outline-secondary disabled"><?= Html::encode($org->location) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">Organization profile missing.</p>
                    <?php endif; ?>
                </section>

                <section class="pd-glass pd-section pd-reveal" id="section-timeline">
                    <h2><i class="fas fa-route"></i>Internship timeline</h2>
                    <ul class="pd-timeline">
                        <li><strong>Applications open</strong> — posted <?= Yii::$app->formatter->asDate($model->created_at, 'medium') ?>.</li>
                        <li><strong>Application deadline</strong> — <?= $deadlineTs ? Yii::$app->formatter->asDate($deadlineTs, 'medium') : 'Rolling / contact organization.' ?></li>
                        <li><strong>Review & shortlist</strong> — typically 1–2 weeks after deadline.</li>
                        <li><strong>Interviews</strong> — scheduled by the organization.</li>
                        <li><strong>Internship period</strong> — <?= $model->duration ? Html::encode($model->duration) : 'Confirm with supervisor.' ?></li>
                    </ul>
                </section>

                <section class="pd-glass pd-section pd-reveal" id="section-supervisor">
                    <h2><i class="fas fa-user-tie"></i>Supervisor & university</h2>
                    <p class="pd-prose small mb-0">After placement approval, your faculty supervisor is assigned through your department. Academic credit and university forms follow your institution’s field training regulations — check the Help Center or your coordinator for forms and signatures.</p>
                </section>
            </div>

            <div class="col-lg-4">
                <aside class="pd-sidebar pd-sidebar-sticky">
                    <div class="pd-glass pd-reveal">
                        <h3>Quick apply</h3>
                        <?php if ($hasApplied): ?>
                            <p class="small text-secondary">You already applied.</p>
                            <?= Html::a('Open application', ['application/view', 'id' => $application->id], ['class' => 'pd-btn-primary w-100 text-center']) ?>
                        <?php elseif ($canApplyStudent): ?>
                            <p class="small text-secondary">Applications use your saved CV and profile. Eligibility is re-checked on submit.</p>
                            <button type="button" class="pd-btn-primary w-100 justify-content-center" data-bs-toggle="modal" data-bs-target="#pdApplyModal">Start application</button>
                        <?php elseif ($applyBlockedStudent): ?>
                            <p class="small text-warning">Requirements not satisfied.</p>
                            <button type="button" class="pd-btn-primary w-100 justify-content-center" disabled>Apply</button>
                            <button type="button" class="pd-btn-ghost w-100 mt-2" data-bs-toggle="modal" data-bs-target="#pdRestrictedModal">Why am I blocked?</button>
                        <?php elseif ($isGuest): ?>
                            <?= Html::a('Sign in to apply', ['/site/login'], ['class' => 'pd-btn-primary w-100 text-center']) ?>
                        <?php elseif ($isOrganization): ?>
                            <p class="small text-muted">Students apply from this page.</p>
                        <?php else: ?>
                            <?= Html::a('Student profile', ['profile/edit-profile'], ['class' => 'pd-btn-primary w-100 text-center']) ?>
                        <?php endif; ?>

                        <hr class="my-3">
                        <h3>Quick facts</h3>
                        <ul class="list-unstyled small text-secondary mb-0">
                            <li class="mb-2"><i class="fas fa-laptop-house me-2 text-primary"></i><?= Html::encode($workTypeLabel) ?></li>
                            <li class="mb-2"><i class="fas fa-clock me-2 text-primary"></i><?= $model->duration ? Html::encode($model->duration) : 'Duration TBC' ?></li>
                            <li class="mb-2"><i class="fas fa-location-dot me-2 text-primary"></i><?= $model->location ? Html::encode($model->location) : 'Location TBC' ?></li>
                            <li class="mb-2"><i class="fas fa-graduation-cap me-2 text-primary"></i><?= $model->academic_level_required ? Html::encode($model->academic_level_required) : 'Level flexible' ?></li>
                            <li><i class="fas fa-users me-2 text-primary"></i><?= (int) $applicationCount ?> applicants</li>
                        </ul>
                    </div>

                    <div class="pd-glass pd-reveal">
                        <h3>Similar opportunities</h3>
                        <?php if (empty($similarPositions)): ?>
                            <p class="small text-muted mb-0">No related listings right now.</p>
                        <?php else: ?>
                            <?php foreach ($similarPositions as $sp): ?>
                                <?= Html::a('<strong>' . Html::encode($sp->title) . '</strong><span>' . Html::encode($sp->organization->name ?? '') . '</span>', ['position/view', 'id' => $sp->id], ['class' => 'pd-similar-card']) ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="pd-glass pd-reveal">
                        <h3>Insights</h3>
                        <p class="small text-secondary mb-0">Popularity and hiring metrics are derived from platform activity for this organization. They do not guarantee selection.</p>
                    </div>

                    <?= Html::a('<i class="fas fa-arrow-left me-2"></i>Back to opportunities', ['position/index'], ['class' => 'pd-btn-ghost w-100 text-center border']) ?>
                </aside>
            </div>
        </div>
    </div>
</div>

<?php /* ——— Modals ——— */ ?>
<div class="modal fade ft-modal-stack" id="pdShareModal" tabindex="-1" aria-labelledby="pdShareLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pd-modal-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="pdShareLabel">Share opportunity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <label class="form-label small" for="pdShareUrl">Link</label>
                <input type="text" class="form-control form-control-sm mb-2" id="pdShareUrl" readonly value="<?= Html::encode($shareUrl) ?>">
                <button type="button" class="btn btn-primary btn-sm" id="pdShareCopy">Copy link</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade ft-modal-stack" id="pdRestrictedModal" tabindex="-1" aria-labelledby="pdRestrictedLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content pd-modal-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="pdRestrictedLabel">Why you can’t apply yet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <?php if ($eligibility && !empty($eligibility->reasons)): ?>
                    <ul class="mb-0">
                        <?php foreach ($eligibility->reasons as $r): ?>
                            <li class="mb-2"><?= Html::encode($r['message'] ?? '') ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">No detailed reasons available. Update your profile or contact support.</p>
                <?php endif; ?>
                <p class="small text-muted mt-3 mb-0">The server always re-validates eligibility on submit — you cannot bypass these rules from the browser.</p>
            </div>
            <div class="modal-footer border-0">
                <?= Html::a('Edit profile', ['profile/edit-profile'], ['class' => 'btn btn-primary']) ?>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php if ($showApplyWizard && $profileReadiness !== null): ?>
    <?= $this->render('_apply_wizard_modal', [
        'model' => $model,
        'student' => $student,
        'eligibility' => $eligibility,
        'profileCompletion' => $profileCompletion,
        'profileReadiness' => $profileReadiness,
        'applicationQuestions' => $applicationQuestions,
        'deadlineMeta' => $deadlineMeta,
        'matchScore' => $matchScore,
        'allowedFieldNames' => $allowedFieldNames,
        'minGpaDisplay' => $minGpaDisplay,
    ]) ?>
<?php endif; ?>

<div class="pd-mobile-cta d-lg-none" role="region" aria-label="Apply actions">
    <?php if ($applicationsClosed): ?>
        <button type="button" class="pd-btn-primary" disabled style="flex:1">Applications closed</button>
    <?php elseif ($isGuest): ?>
        <?= Html::a('Sign in', ['/site/login'], ['class' => 'pd-btn-primary']) ?>
    <?php elseif ($hasApplied): ?>
        <?= Html::a('View application', ['application/view', 'id' => $application->id], ['class' => 'pd-btn-primary']) ?>
    <?php elseif ($applyBlockedStudent): ?>
        <button type="button" class="pd-btn-primary" disabled style="flex:1">Apply</button>
        <button type="button" class="pd-btn-ghost" data-bs-toggle="modal" data-bs-target="#pdRestrictedModal">Why?</button>
    <?php elseif ($canApplyStudent): ?>
        <button type="button" class="pd-btn-primary" style="flex:1" data-bs-toggle="modal" data-bs-target="#pdApplyModal">Apply now</button>
    <?php else: ?>
        <?= Html::a('Profile', ['profile/edit-profile'], ['class' => 'pd-btn-primary', 'style' => 'flex:1;text-align:center']) ?>
    <?php endif; ?>
    <button type="button" class="pd-btn-ghost" data-bs-toggle="modal" data-bs-target="#pdShareModal"><i class="fas fa-share-nodes"></i></button>
</div>
