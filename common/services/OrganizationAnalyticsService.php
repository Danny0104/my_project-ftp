<?php

namespace common\services;

use common\models\Application;
use common\models\OrgInterview;
use common\models\Position;
use yii\db\Expression;

class OrganizationAnalyticsService
{
    private OrganizationScopeService $scope;

    public function __construct(?OrganizationScopeService $scope = null)
    {
        $this->scope = $scope ?? new OrganizationScopeService();
    }

    /**
     * @param array{department?:string,category?:string,status?:string} $filters
     */
    public function getDashboardMetrics(int $organizationId, ?int $fromTs = null, ?int $toTs = null, array $filters = []): array
    {
        $toTs = $toTs ?? time();
        $fromTs = $fromTs ?? strtotime('first day of this month 00:00:00', $toTs);

        $prevToTs = $fromTs - 1;
        $prevFromTs = $prevToTs - ($toTs - $fromTs);

        $appQuery = $this->scopedApplications($organizationId, $fromTs, $toTs, $filters);
        $prevQuery = $this->scopedApplications($organizationId, $prevFromTs, $prevToTs, $filters);

        $totalApplications = (int) (clone $appQuery)->count();
        $prevTotal = (int) (clone $prevQuery)->count();

        $approved = (int) (clone $appQuery)->andWhere(['in', 'a.status', [
            Application::STATUS_APPROVED,
            Application::STATUS_ORG_APPROVED,
            Application::STATUS_UNIVERSITY_APPROVED,
            Application::STATUS_COMPLETED,
        ]])->count();
        $prevApproved = (int) (clone $prevQuery)->andWhere(['in', 'a.status', [
            Application::STATUS_APPROVED,
            Application::STATUS_ORG_APPROVED,
            Application::STATUS_UNIVERSITY_APPROVED,
            Application::STATUS_COMPLETED,
        ]])->count();

        $rejected = (int) (clone $appQuery)->andWhere(['a.status' => Application::STATUS_REJECTED])->count();
        $prevRejected = (int) (clone $prevQuery)->andWhere(['a.status' => Application::STATUS_REJECTED])->count();
        $pending = (int) (clone $appQuery)->andWhere(['in', 'a.status', [
            Application::STATUS_PENDING,
            Application::STATUS_UNDER_REVIEW,
        ]])->count();
        $prevPending = (int) (clone $prevQuery)->andWhere(['in', 'a.status', [
            Application::STATUS_PENDING,
            Application::STATUS_UNDER_REVIEW,
        ]])->count();

        $completed = (int) (clone $appQuery)->andWhere(['a.status' => Application::STATUS_COMPLETED])->count();
        $prevCompleted = (int) (clone $prevQuery)->andWhere(['a.status' => Application::STATUS_COMPLETED])->count();
        $offersApproved = (int) (clone $appQuery)->andWhere(['in', 'a.status', [
            Application::STATUS_APPROVED,
            Application::STATUS_COMPLETED,
        ]])->count();
        $prevOffersApproved = (int) (clone $prevQuery)->andWhere(['in', 'a.status', [
            Application::STATUS_APPROVED,
            Application::STATUS_COMPLETED,
        ]])->count();
        $offerDenom = max(1, $offersApproved + $rejected);
        $offerAcceptance = (int) round(100 * $offersApproved / $offerDenom);
        $prevOfferDenom = max(1, $prevOffersApproved + $prevRejected);
        $prevOfferAcceptance = (int) round(100 * $prevOffersApproved / $prevOfferDenom);

        $completionBase = max(1, $offersApproved);
        $completionRate = (int) round(100 * $completed / $completionBase);
        $prevCompletionBase = max(1, $prevOffersApproved);
        $prevCompletionRate = (int) round(100 * $prevCompleted / $prevCompletionBase);

        $shortlisted = (int) (clone $appQuery)->andWhere(['a.status' => Application::STATUS_ORG_APPROVED])->count();
        $interviewed = (int) (clone $appQuery)->andWhere(['a.status' => Application::STATUS_UNIVERSITY_APPROVED])->count();
        $prevShortlisted = (int) (clone $prevQuery)->andWhere(['a.status' => Application::STATUS_ORG_APPROVED])->count();
        $prevInterviewed = (int) (clone $prevQuery)->andWhere(['a.status' => Application::STATUS_UNIVERSITY_APPROVED])->count();

        $activePositions = (int) Position::find()
            ->where(['organization_id' => $organizationId, 'status' => 'Active'])
            ->count();
        $prevActivePositions = $activePositions;

        $interviewsScheduled = (int) OrgInterview::find()
            ->where(['organization_id' => $organizationId, 'status' => OrgInterview::STATUS_SCHEDULED])
            ->andWhere(['>=', 'scheduled_at', time()])
            ->count();

        $interviewDenom = max(1, $shortlisted + $interviewed);
        $interviewConversion = (int) round(100 * $interviewed / $interviewDenom);
        $prevInterviewDenom = max(1, $prevShortlisted + $prevInterviewed);
        $prevInterviewConversion = (int) round(100 * $prevInterviewed / $prevInterviewDenom);

        $successRate = $totalApplications > 0
            ? (int) round(100 * $approved / $totalApplications)
            : 0;

        $byField = $this->applicationsByField($organizationId, $fromTs, $toTs, $filters);
        $pipeline = $this->pipelineBreakdown($organizationId, $fromTs, $toTs, $filters);
        $daily = $this->dailyApplicationTrends($organizationId, $fromTs, $toTs, $filters);
        $spark = $this->sparkline($organizationId, $fromTs, $toTs, $filters);

        $payload = [
            'total_applications' => $totalApplications,
            'approved' => $approved,
            'rejected' => $rejected,
            'pending' => $pending,
            'active_positions' => $activePositions,
            'interviews_scheduled' => $interviewsScheduled,
            'success_rate' => $successRate,
            'interview_conversion_rate' => $interviewConversion,
            'offer_acceptance_rate' => $offerAcceptance,
            'completion_rate' => $completionRate,
            'trends' => $this->monthlyApplicationTrends($organizationId, $fromTs, $toTs, $filters),
            'daily' => $daily,
            'pipeline' => $pipeline,
            'by_field' => $byField,
            'kpi' => [
                'total_applications' => $this->kpiPayload($totalApplications, $prevTotal, $spark),
                'approved_students' => $this->kpiPayload($approved, $prevApproved, $spark),
                'active_internships' => $this->kpiPayload($activePositions, $prevActivePositions, $spark),
                'interview_conversion' => $this->kpiPayload($interviewConversion, $prevInterviewConversion, $spark, true),
                'pending_reviews' => $this->kpiPayload($pending, $prevPending, $spark),
                'rejected_applications' => $this->kpiPayload($rejected, $prevRejected, $spark),
                'offer_acceptance' => $this->kpiPayload($offerAcceptance, $prevOfferAcceptance, $spark, true),
                'completion_rate' => $this->kpiPayload($completionRate, $prevCompletionRate, $spark, true),
            ],
            'period' => [
                'from' => $fromTs,
                'to' => $toTs,
                'prev_from' => $prevFromTs,
                'prev_to' => $prevToTs,
                'prev_label' => date('M j', $prevFromTs) . ' – ' . date('M j', $prevToTs),
            ],
            'generated_at' => time(),
        ];

        $payload['insights'] = (new OrganizationInsightsService())->build($payload);

        return $payload;
    }

    public function getFilterOptions(int $organizationId): array
    {
        $departments = Position::find()
            ->select('field_of_study')
            ->distinct()
            ->where(['organization_id' => $organizationId])
            ->andWhere(['not', ['field_of_study' => null]])
            ->andWhere(['<>', 'field_of_study', ''])
            ->orderBy(['field_of_study' => SORT_ASC])
            ->column();

        $categories = Position::find()
            ->select('category')
            ->distinct()
            ->where(['organization_id' => $organizationId])
            ->andWhere(['not', ['category' => null]])
            ->andWhere(['<>', 'category', ''])
            ->orderBy(['category' => SORT_ASC])
            ->column();

        return [
            'departments' => $departments,
            'categories' => $categories,
            'statuses' => [
                Application::STATUS_PENDING => 'New',
                Application::STATUS_UNDER_REVIEW => 'Under review',
                Application::STATUS_ORG_APPROVED => 'Shortlisted',
                Application::STATUS_UNIVERSITY_APPROVED => 'Interview',
                Application::STATUS_APPROVED => 'Approved',
                Application::STATUS_REJECTED => 'Rejected',
                Application::STATUS_COMPLETED => 'Completed',
            ],
        ];
    }

    public function exportCsv(int $organizationId, int $fromTs, int $toTs, array $filters = []): string
    {
        $apps = $this->scopedApplications($organizationId, $fromTs, $toTs, $filters)
            ->with(['student.user', 'position'])
            ->orderBy(['a.created_at' => SORT_DESC])
            ->all();

        $lines = ['Application ID,Student,Position,Status,Created'];
        foreach ($apps as $app) {
            $lines[] = implode(',', [
                $app->id,
                '"' . str_replace('"', '""', $app->student->user->username ?? '') . '"',
                '"' . str_replace('"', '""', $app->position->title ?? '') . '"',
                $app->status,
                date('Y-m-d', (int) $app->created_at),
            ]);
        }
        return implode("\n", $lines);
    }

    private function scopedApplications(int $organizationId, int $fromTs, int $toTs, array $filters)
    {
        $query = $this->scope->applicationQuery($organizationId)
            ->andWhere(['between', 'a.created_at', $fromTs, $toTs]);
        $this->applyFilters($query, $filters);
        return $query;
    }

    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['department'])) {
            $query->andWhere(['p.field_of_study' => $filters['department']]);
        }
        if (!empty($filters['category'])) {
            $query->andWhere(['p.category' => $filters['category']]);
        }
        if (!empty($filters['status'])) {
            $query->andWhere(['a.status' => $filters['status']]);
        }
    }

    private function kpiPayload(int $value, int $previous, array $sparkline, bool $isPercent = false): array
    {
        $delta = $this->deltaPct($value, $previous);
        return [
            'value' => $value,
            'previous' => $previous,
            'delta_pct' => $delta,
            'delta_up' => $delta === null ? null : $delta >= 0,
            'sparkline' => $sparkline,
            'is_percent' => $isPercent,
        ];
    }

    private function deltaPct(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : ($current === 0 ? 0.0 : null);
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function sparkline(int $organizationId, int $fromTs, int $toTs, array $filters, int $points = 12): array
    {
        $span = max(1, $toTs - $fromTs);
        $bucket = (int) max(86400, floor($span / $points));
        $values = [];
        for ($i = 0; $i < $points; $i++) {
            $start = $fromTs + ($i * $bucket);
            $end = min($toTs, $start + $bucket - 1);
            if ($start > $toTs) {
                $values[] = 0;
                continue;
            }
            $values[] = (int) (clone $this->scopedApplications($organizationId, $start, $end, $filters))->count();
        }
        return $values;
    }

    private function monthlyApplicationTrends(int $organizationId, int $fromTs, int $toTs, array $filters = []): array
    {
        $rows = $this->scopedApplications($organizationId, $fromTs, $toTs, $filters)
            ->select([new Expression('FROM_UNIXTIME(a.created_at, "%Y-%m") as month'), 'COUNT(*) as cnt'])
            ->groupBy(['month'])
            ->orderBy(['month' => SORT_ASC])
            ->asArray()
            ->all();

        return [
            'labels' => array_column($rows, 'month'),
            'values' => array_map('intval', array_column($rows, 'cnt')),
        ];
    }

    private function dailyApplicationTrends(int $organizationId, int $fromTs, int $toTs, array $filters = []): array
    {
        $rows = $this->scopedApplications($organizationId, $fromTs, $toTs, $filters)
            ->select([
                new Expression('FROM_UNIXTIME(a.created_at, "%Y-%m-%d") as day'),
                'COUNT(*) as cnt',
            ])
            ->groupBy(['day'])
            ->orderBy(['day' => SORT_ASC])
            ->asArray()
            ->all();

        $labels = [];
        foreach (array_column($rows, 'day') as $day) {
            $labels[] = date('M j', strtotime($day));
        }

        return [
            'labels' => $labels,
            'values' => array_map('intval', array_column($rows, 'cnt')),
        ];
    }

    private function pipelineBreakdown(int $organizationId, int $fromTs, int $toTs, array $filters = []): array
    {
        $statuses = [
            Application::STATUS_PENDING => 'New',
            Application::STATUS_UNDER_REVIEW => 'Review',
            Application::STATUS_ORG_APPROVED => 'Shortlisted',
            Application::STATUS_UNIVERSITY_APPROVED => 'Interview',
            Application::STATUS_APPROVED => 'Approved',
            Application::STATUS_REJECTED => 'Rejected',
            Application::STATUS_COMPLETED => 'Hired',
        ];
        $labels = [];
        $values = [];
        $colors = ['#2f76ff', '#6d5cff', '#22c55e', '#f59e0b', '#38bdf8', '#fb7185', '#94a3b8'];
        $colorSlice = [];
        $i = 0;
        foreach ($statuses as $status => $label) {
            $count = (int) (clone $this->scopedApplications($organizationId, $fromTs, $toTs, $filters))
                ->andWhere(['a.status' => $status])
                ->count();
            if ($count === 0) {
                continue;
            }
            $labels[] = $label;
            $values[] = $count;
            $colorSlice[] = $colors[$i % count($colors)];
            $i++;
        }

        $total = array_sum($values);
        $legend = [];
        foreach ($labels as $idx => $label) {
            $count = $values[$idx];
            $legend[] = [
                'label' => $label,
                'count' => $count,
                'pct' => $total > 0 ? round(100 * $count / $total, 1) : 0,
                'color' => $colorSlice[$idx] ?? '#2f76ff',
            ];
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'colors' => $colorSlice,
            'total' => $total,
            'legend' => $legend,
        ];
    }

    private function applicationsByField(int $organizationId, int $fromTs, int $toTs, array $filters = []): array
    {
        $rows = $this->scopedApplications($organizationId, $fromTs, $toTs, $filters)
            ->select([new Expression('COALESCE(NULLIF(p.field_of_study, ""), "Unspecified") as field_name'), 'COUNT(*) as cnt'])
            ->groupBy(['field_name'])
            ->orderBy(['cnt' => SORT_DESC])
            ->limit(6)
            ->asArray()
            ->all();

        return [
            'labels' => array_column($rows, 'field_name'),
            'values' => array_map('intval', array_column($rows, 'cnt')),
        ];
    }
}
