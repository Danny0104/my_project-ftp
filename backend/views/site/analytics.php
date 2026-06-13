<?php
/** @var array $metrics */
/** @var string $from */
/** @var string $to */
/** @var array $filters */
/** @var array $filterOptions */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Reports & Analytics';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'analytics';

$kpi = $metrics['kpi'];
$periodLabel = date('M j, Y', $metrics['period']['from']) . ' – ' . date('M j, Y', $metrics['period']['to']);
$prevLabel = $metrics['period']['prev_label'];
$exportBase = array_merge(['site/analytics-export', 'from' => $from, 'to' => $to], $filters);
$exportCsvUrl = Url::to(array_merge($exportBase, ['format' => 'csv']));
$exportExcelUrl = Url::to(array_merge($exportBase, ['format' => 'xlsx']));
$exportPdfUrl = Url::to(array_merge($exportBase, ['format' => 'pdf']));
$insights = $metrics['insights'] ?? [];

$kpiCards = [
    ['label' => 'Total Applications', 'icon' => 'fa-layer-group', 'accent' => 'blue', 'data' => $kpi['total_applications'], 'suffix' => ''],
    ['label' => 'Approved Students', 'icon' => 'fa-user-check', 'accent' => 'green', 'data' => $kpi['approved_students'], 'suffix' => ''],
    ['label' => 'Active Internships', 'icon' => 'fa-briefcase', 'accent' => 'purple', 'data' => $kpi['active_internships'], 'suffix' => ''],
    ['label' => 'Interview Conversion', 'icon' => 'fa-chart-line', 'accent' => 'orange', 'data' => $kpi['interview_conversion'], 'suffix' => '%'],
    ['label' => 'Pending Reviews', 'icon' => 'fa-clock', 'accent' => 'amber', 'data' => $kpi['pending_reviews'], 'suffix' => ''],
    ['label' => 'Rejected Applications', 'icon' => 'fa-ban', 'accent' => 'red', 'data' => $kpi['rejected_applications'], 'suffix' => ''],
    ['label' => 'Offer Acceptance', 'icon' => 'fa-handshake', 'accent' => 'teal', 'data' => $kpi['offer_acceptance'], 'suffix' => '%'],
    ['label' => 'Internship Completion', 'icon' => 'fa-graduation-cap', 'accent' => 'indigo', 'data' => $kpi['completion_rate'], 'suffix' => '%'],
];

function apAnalyticsDelta(array $data): string
{
    $delta = $data['delta_pct'];
    if ($delta === null) {
        return '';
    }
    $up = !empty($data['delta_up']);
    $arrow = $up ? '↑' : '↓';
    $sign = $delta > 0 ? '+' : '';
    return $arrow . ' ' . $sign . abs($delta) . '%';
}
?>

<div class="ap-analytics ap-page-enter" id="apAnalyticsRoot" data-analytics-data-url="<?= Html::encode(Url::to(['site/analytics-data'])) ?>">

    <header class="ap-analytics-hero">
        <div>
            <h1 class="ap-analytics-hero__title">Reports &amp; Analytics</h1>
            <p class="ap-analytics-hero__subtitle">Track performance, analyze trends and generate insights across the platform.</p>
        </div>
        <div class="ap-analytics-live" aria-live="polite">
            <span class="ap-analytics-live__dot"></span>
            <span class="ap-analytics-live__text">Live</span>
        </div>
    </header>

    <form class="ap-analytics-toolbar ap-glass" method="get" action="<?= Url::to(['site/analytics']) ?>" data-ap-analytics-filter>
        <div class="ap-analytics-toolbar__dates">
            <label class="ap-analytics-toolbar__label" for="apAnalyticsFrom"><i class="far fa-calendar"></i> Date range</label>
            <div class="ap-analytics-daterange">
                <input type="date" id="apAnalyticsFrom" name="from" value="<?= Html::encode($from) ?>" aria-label="From date">
                <span class="ap-analytics-daterange__sep">–</span>
                <input type="date" name="to" value="<?= Html::encode($to) ?>" aria-label="To date">
            </div>
            <span class="ap-analytics-daterange__display"><?= Html::encode($periodLabel) ?></span>
        </div>

        <div class="ap-analytics-toolbar__field">
            <label class="ap-analytics-toolbar__label" for="apFilterOrg">Organization</label>
            <select id="apFilterOrg" name="organization_id">
                <option value="">All Organizations</option>
                <?php foreach ($filterOptions['organizations'] as $org): ?>
                    <option value="<?= (int) $org['id'] ?>"<?= (int) ($filters['organization_id'] ?? 0) === (int) $org['id'] ? ' selected' : '' ?>><?= Html::encode($org['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ap-analytics-toolbar__field">
            <label class="ap-analytics-toolbar__label" for="apFilterDepartment">Department</label>
            <select id="apFilterDepartment" name="department">
                <option value="">All Departments</option>
                <?php foreach ($filterOptions['departments'] as $dept): ?>
                    <option value="<?= Html::encode($dept) ?>"<?= ($filters['department'] ?? '') === $dept ? ' selected' : '' ?>><?= Html::encode($dept) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ap-analytics-toolbar__field">
            <label class="ap-analytics-toolbar__label" for="apFilterCategory">Internship type</label>
            <select id="apFilterCategory" name="category">
                <option value="">All Internship Types</option>
                <?php foreach ($filterOptions['categories'] as $cat): ?>
                    <option value="<?= Html::encode($cat) ?>"<?= ($filters['category'] ?? '') === $cat ? ' selected' : '' ?>><?= Html::encode($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ap-analytics-toolbar__field">
            <label class="ap-analytics-toolbar__label" for="apFilterStatus">Status</label>
            <select id="apFilterStatus" name="status">
                <option value="">All Statuses</option>
                <?php foreach ($filterOptions['statuses'] as $val => $label): ?>
                    <option value="<?= Html::encode($val) ?>"<?= ($filters['status'] ?? '') === $val ? ' selected' : '' ?>><?= Html::encode($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ap-analytics-toolbar__actions">
            <button type="submit" class="ap-btn ap-btn-primary">Apply</button>
            <div class="ap-analytics-export-wrap">
                <button type="button" class="ap-btn ap-btn-ghost ap-analytics-export-toggle" aria-expanded="false" aria-haspopup="true">
                    <i class="fas fa-file-export"></i> Export Report
                    <i class="fas fa-chevron-down ap-analytics-export-caret"></i>
                </button>
                <div class="ap-analytics-export-menu" role="menu" hidden>
                    <a href="<?= Html::encode($exportCsvUrl) ?>" role="menuitem" data-export-format="csv"><i class="fas fa-file-csv"></i> CSV</a>
                    <a href="<?= Html::encode($exportExcelUrl) ?>" role="menuitem" data-export-format="xlsx"><i class="fas fa-file-excel"></i> Excel</a>
                    <a href="<?= Html::encode($exportPdfUrl) ?>" role="menuitem" data-export-format="pdf" target="_blank" rel="noopener"><i class="fas fa-file-pdf"></i> PDF / Print</a>
                </div>
            </div>
        </div>
    </form>

    <div class="ap-analytics-kpi-grid" data-analytics-kpis>
        <?php foreach ($kpiCards as $card):
            $data = $card['data'];
            $deltaClass = !empty($data['delta_up']) ? 'is-up' : 'is-down';
            $counterVal = (int) $data['value'];
            $spark = $data['sparkline'] ?? [];
            ?>
            <article class="ap-analytics-kpi ap-analytics-kpi--<?= Html::encode($card['accent']) ?>">
                <div class="ap-analytics-kpi__head">
                    <span class="ap-analytics-kpi__icon"><i class="fas <?= Html::encode($card['icon']) ?>"></i></span>
                    <?php if ($data['delta_pct'] !== null): ?>
                        <span class="ap-analytics-kpi__delta <?= $deltaClass ?>"><?= Html::encode(apAnalyticsDelta($data)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="ap-analytics-kpi__value">
                    <span data-ap-count="<?= $counterVal ?>">0</span><?= $card['suffix'] ? '<span class="ap-analytics-kpi__suffix">' . Html::encode($card['suffix']) . '</span>' : '' ?>
                </div>
                <div class="ap-analytics-kpi__label"><?= Html::encode($card['label']) ?></div>
                <div class="ap-analytics-kpi__compare">vs <?= Html::encode($prevLabel) ?></div>
                <div class="ap-analytics-kpi__spark">
                    <canvas class="ap-analytics-sparkline"
                            data-values='<?= Html::encode(json_encode($spark ?: [0, 0, 0, 0, 0, 0])) ?>'
                            data-accent="<?= Html::encode($card['accent']) ?>"
                            height="36"></canvas>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="ap-analytics-charts">
        <section class="ap-analytics-panel ap-glass">
            <header class="ap-analytics-panel__head">
                <h2>Applications by Field of Study</h2>
                <span class="ap-analytics-chip">This period</span>
            </header>
            <div class="ap-analytics-panel__body ap-analytics-panel__body--bar">
                <canvas id="apChartFields"
                        data-chart="bar"
                        data-labels='<?= Html::encode(json_encode($metrics['by_field']['labels'])) ?>'
                        data-values='<?= Html::encode(json_encode($metrics['by_field']['values'])) ?>'></canvas>
            </div>
        </section>

        <section class="ap-analytics-panel ap-glass ap-analytics-panel--donut">
            <header class="ap-analytics-panel__head">
                <h2>Applications by Status</h2>
                <span class="ap-analytics-chip">This period</span>
            </header>
            <div class="ap-analytics-panel__body ap-analytics-panel__body--donut">
                <div class="ap-analytics-donut-wrap">
                    <div class="ap-analytics-donut-chart">
                        <canvas id="apChartPipeline"
                                data-chart="doughnut"
                                data-labels='<?= Html::encode(json_encode($metrics['pipeline']['labels'])) ?>'
                                data-values='<?= Html::encode(json_encode($metrics['pipeline']['values'])) ?>'
                                data-colors='<?= Html::encode(json_encode($metrics['pipeline']['colors'] ?? [])) ?>'
                                data-total="<?= (int) ($metrics['pipeline']['total'] ?? $metrics['total_applications']) ?>"></canvas>
                        <div class="ap-analytics-donut-center">
                            <strong data-analytics-donut-total><?= (int) ($metrics['pipeline']['total'] ?? $metrics['total_applications']) ?></strong>
                            <span>Total</span>
                        </div>
                    </div>
                    <ul class="ap-analytics-legend" data-analytics-legend>
                        <?php foreach ($metrics['pipeline']['legend'] ?? [] as $item): ?>
                            <li>
                                <span class="ap-analytics-legend__dot" style="background:<?= Html::encode($item['color']) ?>"></span>
                                <span class="ap-analytics-legend__label"><?= Html::encode($item['label']) ?></span>
                                <span class="ap-analytics-legend__pct"><?= Html::encode($item['pct']) ?>%</span>
                                <span class="ap-analytics-legend__count">(<?= (int) $item['count'] ?>)</span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($metrics['pipeline']['legend'])): ?>
                            <li class="ap-analytics-legend__empty">No applications in this period</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </section>

        <section class="ap-analytics-panel ap-glass ap-analytics-panel--wide">
            <header class="ap-analytics-panel__head">
                <h2>Applications Over Time</h2>
                <span class="ap-analytics-chip">Daily</span>
            </header>
            <div class="ap-analytics-panel__body ap-analytics-panel__body--timeline">
                <canvas id="apChartDaily"
                        data-chart="bar"
                        data-variant="gradient"
                        data-labels='<?= Html::encode(json_encode($metrics['daily']['labels'])) ?>'
                        data-values='<?= Html::encode(json_encode($metrics['daily']['values'])) ?>'></canvas>
            </div>
        </section>
    </div>

    <section class="ap-analytics-insights ap-glass">
        <header class="ap-analytics-insights__head">
            <h2><i class="fas fa-wand-magic-sparkles"></i> Smart Insights</h2>
            <span class="ap-analytics-chip">Auto-generated</span>
        </header>
        <div class="ap-analytics-insights__grid" data-analytics-insights>
            <?php foreach ($insights as $insight): ?>
                <article class="ap-analytics-insight ap-analytics-insight--<?= Html::encode($insight['type']) ?>">
                    <span class="ap-analytics-insight__icon"><i class="fas <?= Html::encode($insight['icon']) ?>"></i></span>
                    <p><?= Html::encode($insight['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
