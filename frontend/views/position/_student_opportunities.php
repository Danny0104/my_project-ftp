<?php

use common\models\Application;
use common\models\Organization;
use common\models\Position;
use common\models\Student;
use frontend\assets\StudentOpportunitiesAsset;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\widgets\ActiveForm;
use yii\widgets\LinkPager;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var Student|null $student */
/** @var Application[] $applicationsByPosition */
/** @var Position[] $forYou */
/** @var Position[] $trending */
/** @var array $closingSoonItems */
/** @var array<string, string> $categories */

require_once __DIR__ . '/../dashboard/_student_helpers.php';

StudentOpportunitiesAsset::register($this);

$this->title = 'Opportunities';

$searchModel = new Position();
$searchModel->load(Yii::$app->request->queryParams);

$publicPositionService = new \common\services\PublicPositionService();
$totalPositions = $publicPositionService->countOpenPositions();
$totalOrganizations = (int) Organization::find()->count();
$myApplications = (int) Application::find()->where(['user_id' => Yii::$app->user->id])->count();

$student = $student ?? Student::findOne(['user_id' => Yii::$app->user->id]);
$studentField = $student->field_of_study ?? null;
$studentSkills = array_filter(array_map('trim', explode(',', strtolower((string) ($student->skills ?? '')))));

$models = $dataProvider->getModels();
$applicationsByPosition = $applicationsByPosition ?? Application::find()
    ->where(['user_id' => Yii::$app->user->id])
    ->andWhere(['not in', 'status', [Application::STATUS_WITHDRAWN]])
    ->indexBy('position_id')
    ->all();

$recoService = new \common\services\OpportunityRecommendationService();
$forYou = $forYou ?? ($student ? $recoService->forYou($student, array_map('intval', array_keys($applicationsByPosition))) : []);
$trending = $trending ?? $recoService->trending(5);
$closingSoonItems = $closingSoonItems ?? $recoService->closingSoon(3);
$categories = $categories ?? $recoService->distinctCategories();

$matchScores = [];
$bestMatches = [];
foreach ($models as $pos) {
    if ($student) {
        $ev = Yii::$app->eligibility->evaluateBrowse($student, $pos);
        $matchScores[] = (int) $ev->matchScore;
        if ($ev->matchScore >= 75) {
            $bestMatches[] = $pos;
        }
    }
}
$avgMatch = $matchScores ? (int) round(array_sum($matchScores) / count($matchScores)) : 0;
$profilePct = $student ? (new \common\services\ProfileCompletionService())->dashboardPercent($student) : 0;

$recentPosted = array_slice($models, 0, 6);

$skillGaps = [];
if ($studentSkills && !empty($models)) {
    $top = $models[0];
    if ($student) {
        $top = $bestMatches[0] ?? $models[0];
    }
    $required = array_filter(array_map('trim', explode(',', strtolower((string) ($top->skills_required ?? '')))));
    foreach (array_slice($required, 0, 3) as $req) {
        if ($req && !in_array($req, $studentSkills, true)) {
            $skillGaps[] = ucfirst($req);
        }
    }
}

$viewMode = Yii::$app->request->get('view', 'grid') === 'list' ? 'list' : 'grid';
?>

<div class="sp-om" id="spOpportunitiesMarketplace" data-bookmark-sync-url="<?= Html::encode(\yii\helpers\Url::to(['/position/bookmark-ids'])) ?>">
    <!-- Top workspace -->
    <header class="sp-om-hero">
        <div class="sp-om-hero-text">
            <h1>Internship marketplace</h1>
            <p>Discover field training roles matched to your profile. Filter instantly, save roles, and apply in one click.</p>
        </div>
        <div class="sp-om-hero-actions">
            <?= Html::a('<i class="fas fa-bookmark"></i> Saved <span id="spOmSavedCount">0</span>', '#spOmFeed', ['class' => 'sp-om-btn sp-om-btn--ghost', 'data-om-filter-saved' => '1']) ?>
            <?= Html::a('<i class="fas fa-file-lines"></i> Applications', ['application/index'], ['class' => 'sp-om-btn sp-om-btn--ghost']) ?>
        </div>
    </header>

    <div class="sp-om-kpi-row">
        <div class="sp-om-kpi">
            <span class="sp-om-kpi-label">Open roles</span>
            <strong data-count="<?= $totalPositions ?>">0</strong>
        </div>
        <div class="sp-om-kpi">
            <span class="sp-om-kpi-label">Avg. match</span>
            <strong data-count="<?= $avgMatch ?>">0</strong><span class="sp-om-kpi-suffix">%</span>
        </div>
        <div class="sp-om-kpi">
            <span class="sp-om-kpi-label">Profile ready</span>
            <strong data-count="<?= $profilePct ?>">0</strong><span class="sp-om-kpi-suffix">%</span>
        </div>
        <div class="sp-om-kpi">
            <span class="sp-om-kpi-label">Applications</span>
            <strong data-count="<?= $myApplications ?>">0</strong>
        </div>
        <div class="sp-om-readiness">
            <div class="sp-om-readiness-ring" style="--sp-ready: <?= $profilePct ?>">
                <span><?= $profilePct ?>%</span>
            </div>
            <div>
                <strong>Internship readiness</strong>
                <span class="text-muted">Complete profile & CV to boost matches</span>
                <?= Html::a('Improve profile →', ['profile/student'], ['class' => 'sp-om-link']) ?>
            </div>
        </div>
    </div>

    <?php $form = ActiveForm::begin([
        'method' => 'get',
        'action' => ['position/index'],
        'options' => ['class' => 'sp-om-search-form', 'id' => 'spOmSearchForm'],
    ]); ?>
    <div class="sp-om-search-wrap">
        <div class="sp-om-search" id="spOmSearchBox">
            <i class="fas fa-search sp-om-search-icon"></i>
            <?= $form->field($searchModel, 'title')->textInput([
                'class' => 'form-control sp-om-search-input',
                'placeholder' => 'Search internships, companies, technologies…',
                'id' => 'spOmSearchInput',
                'autocomplete' => 'off',
            ])->label(false) ?>
            <kbd class="sp-om-kbd" title="Focus search">/</kbd>
            <div class="sp-om-suggest" id="spOmSuggest" hidden></div>
        </div>
        <button type="button" class="sp-om-btn sp-om-btn--ghost" id="spOmFilterToggle" aria-expanded="false">
            <i class="fas fa-sliders"></i> Filters
        </button>
        <?= Html::submitButton('<i class="fas fa-magnifying-glass"></i>', ['class' => 'sp-om-btn sp-om-btn--primary sp-om-search-submit', 'title' => 'Search server']) ?>
    </div>
    <div class="sp-om-search-hidden">
        <?= $form->field($searchModel, 'location')->hiddenInput(['id' => 'spOmLocationInput'])->label(false) ?>
        <?= $form->field($searchModel, 'field_of_study')->hiddenInput(['id' => 'spOmFieldInput'])->label(false) ?>
    </div>
    <?php ActiveForm::end(); ?>

    <div class="sp-om-chips sp-om-quick-chips" id="spOmQuickChips">
        <button type="button" class="sp-om-chip is-active" data-filter="all">All</button>
        <button type="button" class="sp-om-chip" data-filter="recommended">Best match</button>
        <button type="button" class="sp-om-chip" data-filter="remote">Remote</button>
        <button type="button" class="sp-om-chip" data-filter="hybrid">Hybrid</button>
        <button type="button" class="sp-om-chip" data-filter="on-site">On-site</button>
        <button type="button" class="sp-om-chip" data-filter="closing">Closing soon</button>
        <?php if ($studentField): ?>
            <button type="button" class="sp-om-chip" data-filter="<?= Html::encode(strtolower($studentField)) ?>">My field</button>
        <?php endif; ?>
        <button type="button" class="sp-om-chip" data-om-saved-only="1"><i class="fas fa-bookmark"></i> Saved</button>
    </div>

    <?php if (!empty($recentPosted)): ?>
    <section class="sp-om-carousel-section">
        <div class="sp-om-section-head">
            <h2><i class="fas fa-bolt"></i> Recently posted</h2>
            <button type="button" class="sp-om-carousel-nav" data-carousel="spOmCarouselRecent" data-dir="-1" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
            <button type="button" class="sp-om-carousel-nav" data-carousel="spOmCarouselRecent" data-dir="1" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="sp-om-carousel" id="spOmCarouselRecent">
            <?php foreach ($recentPosted as $pos): ?>
                <a href="<?= Html::encode(\yii\helpers\Url::to(['position/view', 'id' => $pos->id])) ?>" class="sp-om-carousel-card">
                    <strong><?= Html::encode(StringHelper::truncate($pos->title, 42)) ?></strong>
                    <span><?= Html::encode($pos->organization->name ?? '') ?></span>
                    <em><?= ftpRelativeTime((int) $pos->created_at) ?></em>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="sp-om-workspace">
        <aside class="sp-om-filters sp-om-glass" id="spOmFiltersPanel" aria-label="Filters">
            <div class="sp-om-filters-head">
                <h3>Smart filters</h3>
                <button type="button" class="sp-om-link" id="spOmClearFilters">Clear all</button>
            </div>
            <div class="sp-om-filter-group">
                <span class="sp-om-filter-label">Work mode</span>
                <div class="sp-om-chips sp-om-filter-chips" data-filter-attr="data-work-mode">
                    <button type="button" class="sp-om-chip is-active" data-filter="all">Any</button>
                    <button type="button" class="sp-om-chip" data-filter="remote">Remote</button>
                    <button type="button" class="sp-om-chip" data-filter="hybrid">Hybrid</button>
                    <button type="button" class="sp-om-chip" data-filter="on-site">On-site</button>
                </div>
            </div>
            <div class="sp-om-filter-group">
                <span class="sp-om-filter-label">Match score</span>
                <div class="sp-om-chips sp-om-filter-chips" data-filter-attr="data-match-min">
                    <button type="button" class="sp-om-chip is-active" data-filter="0">Any</button>
                    <button type="button" class="sp-om-chip" data-filter="70">70%+</button>
                    <button type="button" class="sp-om-chip" data-filter="80">80%+</button>
                    <button type="button" class="sp-om-chip" data-filter="90">90%+</button>
                </div>
            </div>
            <?php if (!empty($categories)): ?>
            <div class="sp-om-filter-group">
                <span class="sp-om-filter-label">Category</span>
                <div class="sp-om-chips sp-om-filter-chips sp-om-filter-chips--wrap" data-filter-attr="data-category">
                    <button type="button" class="sp-om-chip is-active" data-filter="all">All</button>
                    <?php foreach (array_slice($categories, 0, 8) as $key => $label): ?>
                        <button type="button" class="sp-om-chip" data-filter="<?= Html::encode($key) ?>"><?= Html::encode(StringHelper::truncate($label, 18)) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <div class="sp-om-filter-group">
                <span class="sp-om-filter-label">Deadline</span>
                <div class="sp-om-chips sp-om-filter-chips" data-filter-attr="data-deadline-max">
                    <button type="button" class="sp-om-chip is-active" data-filter="999">Any time</button>
                    <button type="button" class="sp-om-chip" data-filter="7">≤ 7 days</button>
                    <button type="button" class="sp-om-chip" data-filter="14">≤ 14 days</button>
                    <button type="button" class="sp-om-chip" data-filter="30">≤ 30 days</button>
                </div>
            </div>
            <p class="sp-om-filter-hint"><i class="fas fa-bolt"></i> Filters apply instantly. Server search uses the bar above.</p>
        </aside>

        <div class="sp-om-main">
            <div class="sp-om-toolbar sp-om-glass">
                <span class="sp-om-results-count" id="spOmResultsCount"><?= count($models) ?> roles</span>
                <div class="sp-om-view-toggle" data-sp-opp-view="spOmFeed">
                    <button type="button" class="<?= $viewMode === 'grid' ? 'is-active' : '' ?>" data-view="grid" title="Grid"><i class="fas fa-grid-2"></i></button>
                    <button type="button" class="<?= $viewMode === 'list' ? 'is-active' : '' ?>" data-view="list" title="List"><i class="fas fa-list"></i></button>
                </div>
                <select class="form-select form-select-sm sp-om-sort" id="spOmSort" aria-label="Sort">
                    <option value="match">Best match</option>
                    <option value="deadline">Closing soon</option>
                    <option value="newest">Newest</option>
                </select>
            </div>

            <div class="sp-om-feed sp-om-view--<?= Html::encode($viewMode) ?>" id="spOmFeed">
                <div class="sp-om-skeleton-grid" id="spOmSkeleton">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <div class="sp-om-skeleton-card"></div>
                    <?php endfor; ?>
                </div>

                <div class="sp-om-cards" id="spOmCards">
                    <?php if (!empty($models)): ?>
                        <?php foreach ($models as $position): ?>
                            <?php
                            $eligibility = $student ? Yii::$app->eligibility->evaluateBrowse($student, $position) : null;
                            $application = $applicationsByPosition[$position->id] ?? null;
                            echo $this->render('_student_opp_card', [
                                'position' => $position,
                                'student' => $student,
                                'eligibility' => $eligibility,
                                'application' => $application,
                                'viewMode' => $viewMode,
                            ]);
                            ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="sp-om-empty" id="spOmEmpty" <?= !empty($models) ? 'hidden' : '' ?>>
                    <div class="sp-om-empty-icon"><i class="fas fa-compass"></i></div>
                    <h3>No internships found</h3>
                    <p>Try clearing filters or updating your profile for better matches.</p>
                    <button type="button" class="sp-om-btn sp-om-btn--primary" id="spOmEmptyReset">Reset filters</button>
                    <?= Html::a('Update profile', ['profile/student'], ['class' => 'sp-om-btn sp-om-btn--ghost']) ?>
                </div>

                <div class="sp-om-empty sp-om-empty--filter" id="spOmEmptyFilter" hidden>
                    <div class="sp-om-empty-icon"><i class="fas fa-filter-circle-xmark"></i></div>
                    <h3>No matches for current filters</h3>
                    <p>Adjust filters or search terms to see more roles.</p>
                    <button type="button" class="sp-om-btn sp-om-btn--primary" id="spOmFilterReset">Clear filters</button>
                </div>
            </div>

            <?php if ($dataProvider->pagination->pageCount > 1): ?>
                <div class="sp-om-pagination">
                    <?= LinkPager::widget([
                        'pagination' => $dataProvider->pagination,
                        'options' => ['class' => 'pagination pagination-sm'],
                        'linkOptions' => ['class' => 'page-link'],
                        'pageCssClass' => 'page-item',
                        'activePageCssClass' => 'active',
                        'disabledPageCssClass' => 'disabled',
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="sp-om-rail sp-om-glass">
            <h3 class="sp-om-rail-title"><i class="fas fa-sparkles"></i> For you</h3>
            <?php if (!empty($forYou)): ?>
                <?php foreach ($forYou as $pos): ?>
                    <?php $m = $student ? (int) Yii::$app->eligibility->computeFitScore($student, $pos) : 0; ?>
                    <div class="sp-om-rail-item">
                        <strong><?= Html::encode(StringHelper::truncate($pos->title, 36)) ?></strong>
                        <span><?= Html::encode($pos->organization->name ?? '') ?></span>
                        <em class="sp-om-match-text"><?= $m ?>% match</em>
                        <?= Html::a('View →', ['position/view', 'id' => $pos->id], ['class' => 'sp-om-link']) ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="small text-muted">Complete your profile or explore open roles to unlock personalized picks.</p>
            <?php endif; ?>

            <h3 class="sp-om-rail-title mt-3"><i class="fas fa-fire"></i> Trending</h3>
            <?php if (!empty($trending)): ?>
                <?php foreach ($trending as $pos): ?>
                    <div class="sp-om-rail-item sp-om-rail-item--compact">
                        <strong><?= Html::encode(StringHelper::truncate($pos->title, 32)) ?></strong>
                        <span><i class="fas fa-chart-line text-warning"></i> Most applications this week</span>
                        <?= Html::a('View →', ['position/view', 'id' => $pos->id], ['class' => 'sp-om-link']) ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="small text-muted">No trending roles yet.</p>
            <?php endif; ?>

            <?php if (!empty($closingSoonItems)): ?>
                <h3 class="sp-om-rail-title mt-3"><i class="fas fa-hourglass-half"></i> Closing soon</h3>
                <?php foreach ($closingSoonItems as $item): ?>
                    <?php /** @var Position $pos */ $pos = $item['position']; ?>
                    <div class="sp-om-rail-item sp-om-rail-item--compact">
                        <?= Html::a(Html::encode(StringHelper::truncate($pos->title, 30)), ['position/view', 'id' => $pos->id], ['class' => 'sp-om-link fw-semibold']) ?>
                        <span><?= Html::encode($item['label']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($skillGaps)): ?>
                <div class="sp-om-skill-gap mt-3">
                    <h3 class="sp-om-rail-title"><i class="fas fa-lightbulb"></i> Skill insights</h3>
                    <p class="small mb-2">Improve these skills to increase match rate:</p>
                    <ul class="sp-om-gap-list">
                        <?php foreach ($skillGaps as $gap): ?>
                            <li><?= Html::encode($gap) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<!-- Quick view -->
<div class="modal fade ft-modal-stack" id="spOmQuickModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content sp-om-glass">
            <div class="modal-header border-0 pb-0">
                <div class="sp-om-quick-head flex-grow-1 min-w-0">
                    <div class="sp-om-quick-logo" id="spOmQuickLogo" aria-hidden="true"></div>
                    <div class="min-w-0">
                        <h5 class="modal-title" id="spOmQuickTitle"></h5>
                        <p class="text-muted mb-0 small" id="spOmQuickOrg"></p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="spOmQuickDesc" class="small"></p>
                <p class="sp-om-insight mb-0" id="spOmQuickInsight"></p>
            </div>
            <div class="modal-footer border-0">
                <a href="#" class="sp-om-btn sp-om-btn--primary" id="spOmQuickApply">View & apply</a>
                <button type="button" class="sp-om-btn sp-om-btn--ghost" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Eligibility -->
<div class="modal fade ft-modal-stack" id="spEligibilityModal" tabindex="-1" aria-labelledby="spEligibilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content sp-om-glass">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="spEligibilityModalLabel"><i class="fas fa-shield-halved me-2 text-warning"></i> Application not permitted</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="spEligibilityModalMessage" class="mb-3"></p>
                <p class="small text-muted mb-0">Eligibility is enforced per university training regulations.</p>
            </div>
            <div class="modal-footer border-0">
                <?= Html::a('Update profile', ['profile/student'], ['class' => 'sp-om-btn sp-om-btn--primary']) ?>
                <button type="button" class="sp-om-btn sp-om-btn--ghost" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="sp-om-filter-backdrop" id="spOmFilterBackdrop" hidden></div>
