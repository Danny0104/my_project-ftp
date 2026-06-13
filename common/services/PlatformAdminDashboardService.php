<?php

namespace common\services;

use common\models\Application;
use common\models\OrgInterview;
use common\models\Organization;
use common\models\Position;
use common\models\Student;
use common\models\User;
use yii\db\Expression;

/**
 * Executive dashboard metrics for the admin control center.
 */
class PlatformAdminDashboardService
{
    public function getExecutiveDashboard(): array
    {
        $todayStart = strtotime('today 00:00:00');
        $todayEnd = strtotime('today 23:59:59');

        $totalStudents = (int) Student::find()->count();
        $totalOrgs = (int) Organization::find()->count();
        $activePositions = (int) Position::find()
            ->where(['or', ['status' => 'Active'], ['status' => 'active']])
            ->count();
        $totalApps = (int) Application::find()->count();
        $appsToday = (int) Application::find()
            ->andWhere(['between', 'created_at', $todayStart, $todayEnd])
            ->count();

        $pendingApps = (int) Application::find()
            ->where(['status' => [Application::STATUS_PENDING, Application::STATUS_UNDER_REVIEW]])
            ->count();
        $approvedApps = (int) Application::find()
            ->where(['status' => [
                Application::STATUS_APPROVED,
                Application::STATUS_ORG_APPROVED,
                Application::STATUS_UNIVERSITY_APPROVED,
                Application::STATUS_COMPLETED,
            ]])
            ->count();
        $rejectedApps = (int) Application::find()
            ->where(['status' => Application::STATUS_REJECTED])
            ->count();
        $pendingUsers = (int) User::find()->where(['status' => User::STATUS_PENDING])->count();

        $placementRate = $totalApps > 0 ? (int) round(100 * $approvedApps / $totalApps) : 0;

        $interviewsUpcoming = (int) OrgInterview::find()
            ->where(['status' => OrgInterview::STATUS_SCHEDULED])
            ->andWhere(['>=', 'scheduled_at', time()])
            ->count();

        $pipeline = [
            'pending' => (int) Application::find()->where(['status' => Application::STATUS_PENDING])->count(),
            'review' => (int) Application::find()->where(['status' => Application::STATUS_UNDER_REVIEW])->count(),
            'org_approved' => (int) Application::find()->where(['status' => Application::STATUS_ORG_APPROVED])->count(),
            'university_approved' => (int) Application::find()->where(['status' => Application::STATUS_UNIVERSITY_APPROVED])->count(),
            'approved' => (int) Application::find()->where(['status' => Application::STATUS_APPROVED])->count(),
            'rejected' => (int) Application::find()->where(['status' => Application::STATUS_REJECTED])->count(),
            'completed' => (int) Application::find()->where(['status' => Application::STATUS_COMPLETED])->count(),
        ];

        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthStart = strtotime($month . '-01');
            $monthEnd = strtotime($month . '-01 +1 month');
            $monthlyStats[$month] = [
                'applications' => (int) Application::find()
                    ->andWhere(['>=', 'created_at', $monthStart])
                    ->andWhere(['<', 'created_at', $monthEnd])
                    ->count(),
                'users' => (int) User::find()
                    ->andWhere(['>=', 'created_at', $monthStart])
                    ->andWhere(['<', 'created_at', $monthEnd])
                    ->count(),
                'organizations' => $this->countOrganizationsRegisteredBetween($monthStart, $monthEnd),
            ];
        }

        $dailyApps = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $start = strtotime($day . ' 00:00:00');
            $end = strtotime($day . ' 23:59:59');
            $dailyApps[] = [
                'label' => date('M j', $start),
                'count' => (int) Application::find()->andWhere(['between', 'created_at', $start, $end])->count(),
            ];
        }

        $byField = Application::find()
            ->alias('a')
            ->innerJoin(['p' => Position::tableName()], 'p.id = a.position_id')
            ->select([new Expression('COALESCE(NULLIF(p.field_of_study, ""), "Unspecified") as field_name'), 'COUNT(*) as cnt'])
            ->groupBy(['field_name'])
            ->orderBy(['cnt' => SORT_DESC])
            ->limit(6)
            ->asArray()
            ->all();

        $counts = [
            'total_students' => $totalStudents,
            'total_organizations' => $totalOrgs,
            'total_users' => (int) User::find()->count(),
            'total_applications' => $totalApps,
            'applications_today' => $appsToday,
            'active_positions' => $activePositions,
            'pending_applications' => $pendingApps,
            'approved_applications' => $approvedApps,
            'rejected_applications' => $rejectedApps,
            'pending_users' => $pendingUsers,
            'placement_rate' => $placementRate,
            'interviews_scheduled' => $interviewsUpcoming,
        ];

        $insights = $this->buildInsights($counts, $monthlyStats, $byField);

        return [
            'counts' => $counts,
            'pipeline' => $pipeline,
            'monthlyStats' => $monthlyStats,
            'dailyApps' => $dailyApps,
            'byField' => [
                'labels' => array_column($byField, 'field_name'),
                'values' => array_map('intval', array_column($byField, 'cnt')),
            ],
            'insights' => $insights,
            'health' => [
                'status' => 'operational',
                'label' => 'All systems operational',
            ],
            'recentApplications' => Application::find()
                ->with(['student.user', 'position.organization'])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(6)
                ->all(),
            'recentUsers' => User::find()
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(6)
                ->all(),
        ];
    }

    /**
     * Organization rows have no created_at; use linked user registration time.
     */
    private function countOrganizationsRegisteredBetween(int $from, int $to): int
    {
        return (int) Organization::find()
            ->alias('o')
            ->innerJoin(['u' => User::tableName()], 'u.id = o.user_id')
            ->andWhere(['>=', 'u.created_at', $from])
            ->andWhere(['<', 'u.created_at', $to])
            ->count();
    }

    private function buildInsights(array $counts, array $monthlyStats, array $byField): array
    {
        $insights = [];
        $months = array_values($monthlyStats);
        if (count($months) >= 2) {
            $cur = (int) ($months[count($months) - 1]['applications'] ?? 0);
            $prev = (int) ($months[count($months) - 2]['applications'] ?? 0);
            if ($prev > 0 && $cur > $prev * 1.1) {
                $pct = round(100 * ($cur - $prev) / $prev);
                $insights[] = ['type' => 'positive', 'icon' => 'fa-arrow-trend-up', 'text' => "Applications increased by {$pct}% vs last month."];
            }
        }

        if ($counts['applications_today'] > 0) {
            $insights[] = ['type' => 'neutral', 'icon' => 'fa-bolt', 'text' => $counts['applications_today'] . ' new application(s) submitted today.'];
        }

        if ($counts['pending_applications'] > 5) {
            $insights[] = ['type' => 'warning', 'icon' => 'fa-clock', 'text' => $counts['pending_applications'] . ' applications need review in the approval queue.'];
        }

        if (!empty($byField[0]['field_name'])) {
            $insights[] = ['type' => 'positive', 'icon' => 'fa-fire', 'text' => 'Highest demand field: ' . $byField[0]['field_name'] . '.'];
        }

        if ($counts['placement_rate'] >= 50) {
            $insights[] = ['type' => 'positive', 'icon' => 'fa-chart-line', 'text' => 'Placement success rate is ' . $counts['placement_rate'] . '% platform-wide.'];
        }

        if (empty($insights)) {
            $insights[] = ['type' => 'neutral', 'icon' => 'fa-gauge-high', 'text' => 'Platform metrics are stable. Open Reports & Analytics for deeper trends.'];
        }

        return array_slice($insights, 0, 5);
    }
}
