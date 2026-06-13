<?php

/**
 * Public internship discovery marketplace.
 *
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array<string, mixed> $searchParams
 * @var string $sort
 * @var common\models\Organization[] $organizations
 * @var array<int, int> $applicantCounts
 * @var int $totalActive
 * @var int $totalOrgs
 */

use common\models\Application;
use common\models\Position;
use frontend\assets\PositionsMarketplaceAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

require_once __DIR__ . '/_public_helpers.php';

PositionsMarketplaceAsset::register($this);

$this->title = 'Discover Internships';

$publicService = $publicService ?? new \common\services\PublicPositionService();

$totalFields = (int) $publicService->openListingQuery()
    ->andWhere(['not', ['p.field_of_study' => null]])
    ->andWhere(['<>', 'p.field_of_study', ''])
    ->select('p.field_of_study')
    ->distinct()
    ->count();

$totalApplications = (int) Application::find()
    ->where(['not in', 'status', [Application::STATUS_WITHDRAWN]])
    ->count();

$models = $dataProvider->getModels();
$totalCount = (int) $dataProvider->getTotalCount();
$pagination = $dataProvider->getPagination();
$hasFilters = array_filter([
    $searchParams['title'] ?? '',
    $searchParams['location'] ?? '',
    $searchParams['field'] ?? '',
    (int) ($searchParams['organization_id'] ?? 0),
    $searchParams['duration'] ?? '',
]);

$resetUrl = Url::to(['position/index']);
$sortOptions = [
    'newest' => 'Newest',
    'deadline' => 'Deadline',
    'applicants' => 'Most Applied',
    'organization' => 'Organization Name',
];

?>

<div class="pm-page" id="pmPage">
    <section class="pm-hero" aria-labelledby="pm-hero-title">
        <div class="pm-hero__bg" style="background-image: url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=2000&q=80');" role="presentation"></div>
        <div class="pm-hero__overlay" aria-hidden="true"></div>
        <div class="pm-hero__mesh" aria-hidden="true"></div>
        <span class="pm-hero__orb pm-hero__orb--1" aria-hidden="true"></span>
        <span class="pm-hero__orb pm-hero__orb--2" aria-hidden="true"></span>
        <span class="pm-hero__float-icon pm-hero__float-icon--1" aria-hidden="true"><i class="fas fa-briefcase"></i></span>
        <span class="pm-hero__float-icon pm-hero__float-icon--2" aria-hidden="true"><i class="fas fa-building"></i></span>

        <div class="pm-hero__inner">
            <div class="pm-hero__content">
                <p class="pm-hero__eyebrow"><i class="fas fa-compass" aria-hidden="true"></i> Field Training Platform</p>
                <h1 id="pm-hero-title" class="pm-hero__title">Discover Your Perfect Internship</h1>
                <p class="pm-hero__lead">
                    Browse verified internships and field training opportunities from partner organizations. Find placements that match your skills and career goals.
                </p>
                <div class="pm-hero__cta">
                    <a href="#pm-filters-heading" class="pm-btn pm-btn--primary pm-btn--hero">
                        <i class="fas fa-search" aria-hidden="true"></i> Browse Internships
                    </a>
                    <a href="<?= Html::encode(Url::to(['/site/signup'])) ?>" class="pm-btn pm-btn--ghost pm-btn--hero">
                        <i class="fas fa-user-plus" aria-hidden="true"></i> Create Account
                    </a>
                </div>
            </div>

            <div class="pm-hero__stats" role="list">
                <div class="pm-hero__stat pm-hero__stat--glass" role="listitem">
                    <strong data-pm-count="<?= (int) $totalActive ?>"><?= (int) $totalActive ?></strong>
                    <span>Open Internships</span>
                </div>
                <div class="pm-hero__stat pm-hero__stat--glass" role="listitem">
                    <strong data-pm-count="<?= (int) $totalOrgs ?>"><?= (int) $totalOrgs ?></strong>
                    <span>Partner Organizations</span>
                </div>
                <div class="pm-hero__stat pm-hero__stat--glass" role="listitem">
                    <strong data-pm-count="<?= (int) $totalFields ?>"><?= (int) $totalFields ?></strong>
                    <span>Available Fields</span>
                </div>
                <div class="pm-hero__stat pm-hero__stat--glass" role="listitem">
                    <strong data-pm-count="<?= (int) $totalApplications ?>"><?= (int) $totalApplications ?></strong>
                    <span>Applications Submitted</span>
                </div>
            </div>
        </div>
    </section>

    <div class="pm-shell">
        <section class="pm-filters-card pm-reveal" aria-labelledby="pm-filters-heading">
            <h2 id="pm-filters-heading" class="pm-filters-card__title"><i class="fas fa-sliders" aria-hidden="true"></i> Search internships</h2>
            <form class="pm-filters-form" method="get" action="<?= Html::encode(Url::to(['position/index'])) ?>" id="pmFiltersForm">
                <input type="hidden" name="sort" value="<?= Html::encode($sort) ?>">
                <div class="pm-filters-grid">
                    <div class="pm-field pm-field--icon">
                        <label for="pm-title">Internship title</label>
                        <span class="pm-field__icon" aria-hidden="true"><i class="fas fa-briefcase"></i></span>
                        <input type="search" id="pm-title" name="title" value="<?= Html::encode($searchParams['title'] ?? '') ?>" placeholder="e.g. Software Engineering" autocomplete="off">
                    </div>
                    <div class="pm-field pm-field--icon">
                        <label for="pm-location">Location</label>
                        <span class="pm-field__icon" aria-hidden="true"><i class="fas fa-location-dot"></i></span>
                        <input type="search" id="pm-location" name="location" value="<?= Html::encode($searchParams['location'] ?? '') ?>" placeholder="City or remote" autocomplete="off">
                    </div>
                    <div class="pm-field pm-field--icon">
                        <label for="pm-field">Field of study</label>
                        <span class="pm-field__icon" aria-hidden="true"><i class="fas fa-graduation-cap"></i></span>
                        <input type="search" id="pm-field" name="field" value="<?= Html::encode($searchParams['field'] ?? '') ?>" placeholder="e.g. Computer Science" autocomplete="off">
                    </div>
                    <div class="pm-field pm-field--icon">
                        <label for="pm-organization">Organization</label>
                        <span class="pm-field__icon" aria-hidden="true"><i class="fas fa-building"></i></span>
                        <select id="pm-organization" name="organization_id">
                            <option value="">All organizations</option>
                            <?php foreach ($organizations as $org): ?>
                                <option value="<?= (int) $org->id ?>"<?= (int) ($searchParams['organization_id'] ?? 0) === (int) $org->id ? ' selected' : '' ?>>
                                    <?= Html::encode($org->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="pm-field pm-field--icon">
                        <label for="pm-duration">Duration</label>
                        <span class="pm-field__icon" aria-hidden="true"><i class="fas fa-clock"></i></span>
                        <input type="search" id="pm-duration" name="duration" value="<?= Html::encode($searchParams['duration'] ?? '') ?>" placeholder="e.g. 3 months" autocomplete="off">
                    </div>
                    <div class="pm-field pm-field--submit">
                        <span class="pm-field__label-visually-hidden" id="pm-search-btn-label">Search</span>
                        <button type="submit" class="pm-btn pm-btn--primary pm-btn--block" aria-labelledby="pm-search-btn-label">
                            <i class="fas fa-magnifying-glass" aria-hidden="true"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <div class="pm-toolbar">
            <p class="pm-toolbar__count" aria-live="polite">
                <?php if ($totalCount > 0): ?>
                    Showing <strong><?= count($models) ?></strong> of <strong><?= $totalCount ?></strong> internships
                    <?php if ($pagination && $pagination->page > 0): ?>
                        <span class="pm-toolbar__page">(page <?= (int) ($pagination->page + 1) ?>)</span>
                    <?php endif; ?>
                <?php else: ?>
                    No internships match your filters
                <?php endif; ?>
            </p>
            <div class="pm-sort">
                <label for="pm-sort">Sort by</label>
                <select id="pm-sort" name="sort" class="pm-sort__select" data-pm-sort>
                    <?php foreach ($sortOptions as $key => $label): ?>
                        <option value="<?= Html::encode($key) ?>"<?= $sort === $key ? ' selected' : '' ?>><?= Html::encode($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="pm-results" id="pmResults" aria-busy="false">
            <div class="pm-skeleton-grid" id="pmSkeletonGrid" aria-hidden="true">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="pm-skeleton-card">
                        <div class="pm-skeleton pm-skeleton--logo"></div>
                        <div class="pm-skeleton pm-skeleton--line pm-skeleton--lg"></div>
                        <div class="pm-skeleton pm-skeleton--line"></div>
                        <div class="pm-skeleton pm-skeleton--line pm-skeleton--sm"></div>
                        <div class="pm-skeleton pm-skeleton--tags"></div>
                        <div class="pm-skeleton pm-skeleton--btn"></div>
                    </div>
                <?php endfor; ?>
            </div>

            <?php if ($totalCount === 0): ?>
                <div class="pm-empty" role="status">
                    <div class="pm-empty__icon" aria-hidden="true"><i class="fas fa-briefcase"></i></div>
                    <h2>No internships found</h2>
                    <p>Try broadening your search or clearing filters to see all open internships.</p>
                    <?php if ($hasFilters): ?>
                        <a href="<?= Html::encode($resetUrl) ?>" class="pm-btn pm-btn--primary">Reset filters</a>
                    <?php else: ?>
                        <a href="<?= Html::encode(Url::to(['site/index'])) ?>" class="pm-btn pm-btn--ghost">Back to home</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="pm-grid" id="pmGrid">
                    <?php foreach ($models as $position): ?>
                        <?= $this->render('_public_opp_card', [
                            'position' => $position,
                            'applicantCount' => $applicantCounts[(int) $position->id] ?? 0,
                        ]) ?>
                    <?php endforeach; ?>
                </div>

                <?php if ($pagination && $pagination->pageCount > 1): ?>
                    <nav class="pm-pagination" aria-label="Internship results pages">
                        <?= LinkPager::widget([
                            'pagination' => $pagination,
                            'options' => ['class' => 'pagination pm-pagination__list'],
                            'linkContainerOptions' => ['class' => 'page-item'],
                            'linkOptions' => ['class' => 'page-link'],
                            'disabledListItemSubTagOptions' => ['class' => 'page-link'],
                            'prevPageLabel' => '<i class="fas fa-chevron-left" aria-hidden="true"></i> Previous',
                            'nextPageLabel' => 'Next <i class="fas fa-chevron-right" aria-hidden="true"></i>',
                            'maxButtonCount' => 7,
                        ]) ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
window.pmMarketplaceConfig = <?= json_encode([
    'sort' => $sort,
    'baseUrl' => Url::to(['position/index']),
    'searchParams' => $searchParams,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
