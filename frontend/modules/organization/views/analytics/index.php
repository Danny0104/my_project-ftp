<?php
/** @var array $metrics */
/** @var string $from */
/** @var string $to */
/** @var array $filters */
/** @var array $filterOptions */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Analytics & Reports';

$kpi = $metrics['kpi'];
$periodLabel = date('M j, Y', $metrics['period']['from']) . ' – ' . date('M j, Y', $metrics['period']['to']);
$prevLabel = $metrics['period']['prev_label'];
$exportBase = array_merge(['export', 'from' => $from, 'to' => $to], $filters);
$exportCsvUrl = Url::to(array_merge($exportBase, ['format' => 'csv']));
$exportExcelUrl = Url::to(array_merge($exportBase, ['format' => 'xlsx']));
$exportPdfUrl = Url::to(array_merge($exportBase, ['format' => 'pdf']));

$insights = $metrics['insights'] ?? [];

$kpiCards = [
    [
        'key' => 'total_applications',
        'label' => 'Total Applications',
        'icon' => 'fa-layer-group',
        'accent' => 'blue',
        'data' => $kpi['total_applications'],
        'suffix' => '',
    ],
    [
        'key' => 'approved_students',
        'label' => 'Approved Students',
        'icon' => 'fa-user-check',
        'accent' => 'green',
        'data' => $kpi['approved_students'],
        'suffix' => '',
    ],
    [
        'key' => 'active_internships',
        'label' => 'Active Internships',
        'icon' => 'fa-briefcase',
        'accent' => 'purple',
        'data' => $kpi['active_internships'],
        'suffix' => '',
    ],
    [
        'key' => 'interview_conversion',
        'label' => 'Interview Conversion',
        'icon' => 'fa-chart-line',
        'accent' => 'orange',
        'data' => $kpi['interview_conversion'],
        'suffix' => '%',
    ],
    [
        'key' => 'pending_reviews',
        'label' => 'Pending Reviews',
        'icon' => 'fa-clock',
        'accent' => 'amber',
        'data' => $kpi['pending_reviews'],
        'suffix' => '',
    ],
    [
        'key' => 'rejected_applications',
        'label' => 'Rejected Applications',
        'icon' => 'fa-ban',
        'accent' => 'red',
        'data' => $kpi['rejected_applications'],
        'suffix' => '',
    ],
    [
        'key' => 'offer_acceptance',
        'label' => 'Offer Acceptance',
        'icon' => 'fa-handshake',
        'accent' => 'teal',
        'data' => $kpi['offer_acceptance'],
        'suffix' => '%',
    ],
    [
        'key' => 'completion_rate',
        'label' => 'Internship Completion',
        'icon' => 'fa-graduation-cap',
        'accent' => 'indigo',
        'data' => $kpi['completion_rate'],
        'suffix' => '%',
    ],
];

function orgAnalyticsDelta(array $data): string
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

<div class="org-analytics org-page-enter" id="orgAnalyticsRoot" data-analytics-data-url="<?= Html::encode(Url::to(['data'])) ?>">

    <header class="org-analytics-hero">
        <div>
            <h1 class="org-analytics-hero__title">Analytics &amp; Reports</h1>
            <p class="org-analytics-hero__subtitle">Track performance, analyze trends and generate insights.</p>
        </div>
        <div class="org-analytics-live" aria-live="polite">
            <span class="org-analytics-live__dot"></span>
            <span class="org-analytics-live__text">Live</span>
        </div>
    </header>

    <form class="org-analytics-toolbar org-glass" method="get" action="<?= Url::to(['index']) ?>" data-org-analytics-filter>
        <div class="org-analytics-toolbar__dates">
            <label class="org-analytics-toolbar__label" for="orgAnalyticsFrom"><i class="far fa-calendar"></i> Date range</label>
            <div class="org-analytics-daterange">
                <input type="date" id="orgAnalyticsFrom" name="from" value="<?= Html::encode($from) ?>" aria-label="From date">
                <span class="org-analytics-daterange__sep">–</span>
                <input type="date" name="to" value="<?= Html::encode($to) ?>" aria-label="To date">
            </div>
            <span class="org-analytics-daterange__display"><?= Html::encode($periodLabel) ?></span>
        </div>

        <div class="org-analytics-toolbar__field">
            <label class="org-analytics-toolbar__label" for="orgFilterDepartment">Department</label>
            <select id="orgFilterDepartment" name="department">
                <option value="">All Departments</option>
                <?php foreach ($filterOptions['departments'] as $dept): ?>
                    <option value="<?= Html::encode($dept) ?>"<?= ($filters['department'] ?? '') === $dept ? ' selected' : '' ?>><?= Html::encode($dept) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="org-analytics-toolbar__field">
            <label class="org-analytics-toolbar__label" for="orgFilterCategory">Internship type</label>
            <select id="orgFilterCategory" name="category">
                <option value="">All Internship Types</option>
                <?php foreach ($filterOptions['categories'] as $cat): ?>
                    <option value="<?= Html::encode($cat) ?>"<?= ($filters['category'] ?? '') === $cat ? ' selected' : '' ?>><?= Html::encode($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="org-analytics-toolbar__field">
            <label class="org-analytics-toolbar__label" for="orgFilterStatus">Status</label>
            <select id="orgFilterStatus" name="status">
                <option value="">All Statuses</option>
                <?php foreach ($filterOptions['statuses'] as $val => $label): ?>
                    <option value="<?= Html::encode($val) ?>"<?= ($filters['status'] ?? '') === $val ? ' selected' : '' ?>><?= Html::encode($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="org-analytics-toolbar__actions">
            <button type="submit" class="org-btn org-btn-primary org-analytics-apply">Apply</button>
            <div class="org-analytics-export-wrap">
                <button type="button" class="org-btn org-btn-ghost org-analytics-export-toggle" aria-expanded="false" aria-haspopup="true">
                    <i class="fas fa-file-export"></i> Export Report
                    <i class="fas fa-chevron-down org-analytics-export-caret"></i>
                </button>
                <div class="org-analytics-export-menu" role="menu" hidden>
                    <a href="<?= Html::encode($exportCsvUrl) ?>" role="menuitem" data-export-format="csv" data-pjax="0"><i class="fas fa-file-csv"></i> CSV</a>
                    <a href="<?= Html::encode($exportExcelUrl) ?>" role="menuitem" data-export-format="xlsx" data-pjax="0"><i class="fas fa-file-excel"></i> Excel</a>
                    <a href="<?= Html::encode($exportPdfUrl) ?>" role="menuitem" data-export-format="pdf" data-pjax="0" target="_blank" rel="noopener"><i class="fas fa-file-pdf"></i> PDF / Print</a>
                </div>
            </div>
        </div>
    </form>

    <div class="org-analytics-kpi-grid" data-analytics-kpis>
        <?php foreach ($kpiCards as $card):
            $data = $card['data'];
            $deltaClass = !empty($data['delta_up']) ? 'is-up' : 'is-down';
            $counterVal = (int) $data['value'];
            $spark = $data['sparkline'] ?? [];
            ?>
            <article class="org-analytics-kpi org-analytics-kpi--<?= Html::encode($card['accent']) ?>">
                <div class="org-analytics-kpi__head">
                    <span class="org-analytics-kpi__icon"><i class="fas <?= Html::encode($card['icon']) ?>"></i></span>
                    <?php if ($data['delta_pct'] !== null): ?>
                        <span class="org-analytics-kpi__delta <?= $deltaClass ?>"><?= Html::encode(orgAnalyticsDelta($data)) ?></span>
                    <?php endif; ?>
                </div>
                <div class="org-analytics-kpi__value">
                    <span data-org-counter="<?= $counterVal ?>">0</span><?= $card['suffix'] ? '<span class="org-analytics-kpi__suffix">' . Html::encode($card['suffix']) . '</span>' : '' ?>
                </div>
                <div class="org-analytics-kpi__label"><?= Html::encode($card['label']) ?></div>
                <div class="org-analytics-kpi__compare">vs <?= Html::encode($prevLabel) ?></div>
                <div class="org-analytics-kpi__spark">
                    <canvas class="org-analytics-sparkline"
                            data-values='<?= Html::encode(json_encode($spark ?: [0, 0, 0, 0, 0, 0])) ?>'
                            data-accent="<?= Html::encode($card['accent']) ?>"
                            height="36"></canvas>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="org-analytics-charts">
        <section class="org-analytics-panel org-glass">
            <header class="org-analytics-panel__head">
                <h2>Applications by Field of Study</h2>
                <div class="org-analytics-panel__tools">
                    <span class="org-analytics-chip">This period</span>
                </div>
            </header>
            <div class="org-analytics-panel__body org-analytics-panel__body--bar">
                <canvas id="orgChartFields"
                        data-chart="bar"
                        data-labels='<?= Html::encode(json_encode($metrics['by_field']['labels'])) ?>'
                        data-values='<?= Html::encode(json_encode($metrics['by_field']['values'])) ?>'></canvas>
            </div>
        </section>

        <section class="org-analytics-panel org-glass org-analytics-panel--donut">
            <header class="org-analytics-panel__head">
                <h2>Applications by Status</h2>
                <div class="org-analytics-panel__tools">
                    <span class="org-analytics-chip">This period</span>
                </div>
            </header>
            <div class="org-analytics-panel__body org-analytics-panel__body--donut">
                <div class="org-analytics-donut-wrap">
                    <div class="org-analytics-donut-chart">
                        <canvas id="orgChartPipeline"
                                data-chart="doughnut"
                                data-labels='<?= Html::encode(json_encode($metrics['pipeline']['labels'])) ?>'
                                data-values='<?= Html::encode(json_encode($metrics['pipeline']['values'])) ?>'
                                data-colors='<?= Html::encode(json_encode($metrics['pipeline']['colors'] ?? [])) ?>'
                                data-total="<?= (int) ($metrics['pipeline']['total'] ?? $metrics['total_applications']) ?>"></canvas>
                        <div class="org-analytics-donut-center">
                            <strong data-analytics-donut-total><?= (int) ($metrics['pipeline']['total'] ?? $metrics['total_applications']) ?></strong>
                            <span>Total</span>
                        </div>
                    </div>
                    <ul class="org-analytics-legend" data-analytics-legend>
                        <?php foreach ($metrics['pipeline']['legend'] ?? [] as $item): ?>
                            <li>
                                <span class="org-analytics-legend__dot" style="background:<?= Html::encode($item['color']) ?>"></span>
                                <span class="org-analytics-legend__label"><?= Html::encode($item['label']) ?></span>
                                <span class="org-analytics-legend__pct"><?= Html::encode($item['pct']) ?>%</span>
                                <span class="org-analytics-legend__count">(<?= (int) $item['count'] ?>)</span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($metrics['pipeline']['legend'])): ?>
                            <li class="org-analytics-legend__empty">No applications in this period</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </section>

        <section class="org-analytics-panel org-glass org-analytics-panel--wide">
            <header class="org-analytics-panel__head">
                <h2>Applications Over Time</h2>
                <div class="org-analytics-panel__tools">
                    <span class="org-analytics-chip">Daily</span>
                </div>
            </header>
            <div class="org-analytics-panel__body org-analytics-panel__body--timeline">
                <canvas id="orgChartDaily"
                        data-chart="bar"
                        data-variant="gradient"
                        data-labels='<?= Html::encode(json_encode($metrics['daily']['labels'])) ?>'
                        data-values='<?= Html::encode(json_encode($metrics['daily']['values'])) ?>'></canvas>
            </div>
        </section>
    </div>

    <section class="org-analytics-insights org-glass">
        <header class="org-analytics-insights__head">
            <h2><i class="fas fa-wand-magic-sparkles"></i> Smart Insights</h2>
            <span class="org-analytics-chip">Auto-generated</span>
        </header>
        <div class="org-analytics-insights__grid" data-analytics-insights>
            <?php foreach ($insights as $insight): ?>
                <article class="org-analytics-insight org-analytics-insight--<?= Html::encode($insight['type']) ?>">
                    <span class="org-analytics-insight__icon"><i class="fas <?= Html::encode($insight['icon']) ?>"></i></span>
                    <p><?= Html::encode($insight['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
