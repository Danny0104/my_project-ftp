<?php

use common\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var array $counts */
/** @var array $pipeline */
/** @var array $monthlyStats */
/** @var array $dailyApps */
/** @var array $byField */
/** @var array $insights */
/** @var array $health */
/** @var \common\models\Application[] $recentApplications */
/** @var \common\models\User[] $recentUsers */

$this->title = 'Dashboard';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'dashboard';

$c = $counts;
$adminName = Yii::$app->user->identity->username ?? 'Admin';

$monthLabels = array_keys($monthlyStats);
$monthApps = array_column($monthlyStats, 'applications');
$monthUsers = array_column($monthlyStats, 'users');
$monthOrgs = array_column($monthlyStats, 'organizations');

$kpiCards = [
    ['label' => 'Total students', 'value' => $c['total_students'], 'icon' => 'fa-user-graduate', 'accent' => 'blue', 'trend' => 'Active'],
    ['label' => 'Organizations', 'value' => $c['total_organizations'], 'icon' => 'fa-building', 'accent' => 'purple'],
    ['label' => 'Applications today', 'value' => $c['applications_today'], 'icon' => 'fa-bolt', 'accent' => 'orange'],
    ['label' => 'Pending approvals', 'value' => $c['pending_applications'], 'icon' => 'fa-inbox', 'accent' => 'amber'],
    ['label' => 'Active internships', 'value' => $c['active_positions'], 'icon' => 'fa-briefcase', 'accent' => 'teal'],
    ['label' => 'Placement rate', 'value' => $c['placement_rate'], 'icon' => 'fa-chart-line', 'accent' => 'green', 'suffix' => '%'],
    ['label' => 'Interviews scheduled', 'value' => $c['interviews_scheduled'], 'icon' => 'fa-calendar-check', 'accent' => 'blue'],
    ['label' => 'Platform users', 'value' => $c['total_users'], 'icon' => 'fa-users', 'accent' => 'purple'],
];

$funnelSteps = [
    ['key' => 'pending', 'label' => 'New'],
    ['key' => 'review', 'label' => 'Review'],
    ['key' => 'org_approved', 'label' => 'Shortlisted'],
    ['key' => 'university_approved', 'label' => 'Interview'],
    ['key' => 'approved', 'label' => 'Approved'],
    ['key' => 'rejected', 'label' => 'Rejected'],
    ['key' => 'completed', 'label' => 'Hired'],
];
$funnelMax = max(1, max($pipeline));
?>

<div class="ap-module ap-exec-dashboard" id="apDashRoot">
    <div class="ap-exec-hero">
        <div>
            <h1>Welcome back, <?= Html::encode($adminName) ?></h1>
            <p>Executive control center · <?= date('l, F j, Y') ?></p>
        </div>
        <div class="ap-exec-hero__actions">
            <span class="ap-health-pill"><span class="ap-health-dot"></span> <?= Html::encode($health['label']) ?></span>
            <?= Html::a('<i class="fas fa-chart-line"></i> Analytics', ['site/analytics'], ['class' => 'ap-btn ap-btn-ghost']) ?>
            <?= Html::a('<i class="fas fa-plus"></i> Announcement', ['site/send-announcement'], ['class' => 'ap-btn ap-btn-primary']) ?>
        </div>
    </div>

    <?= $this->render('../layouts/partials/_kpi_grid', ['cards' => $kpiCards]) ?>

    <?= $this->render('../layouts/partials/_insights_strip', ['insights' => $insights]) ?>

    <div class="ap-panel ap-glass ap-module-panel">
        <div class="ap-panel-head">
            <h3><i class="fas fa-diagram-project"></i> Hiring pipeline</h3>
            <?= Html::a('Approval center', ['site/approvals'], ['class' => 'ap-btn ap-btn-ghost ap-btn-sm']) ?>
        </div>
        <div class="ap-funnel">
            <?php foreach ($funnelSteps as $step):
                $count = (int) ($pipeline[$step['key']] ?? 0);
                $pct = round(100 * $count / $funnelMax);
                ?>
                <div class="ap-funnel-step">
                    <h4><?= Html::encode($step['label']) ?></h4>
                    <strong><?= $count ?></strong>
                    <div class="ap-kanban-bar" style="margin-top:8px"><span style="width:<?= $pct ?>%"></span></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="ap-dash-grid-2">
        <div class="ap-panel ap-glass ap-module-panel">
            <div class="ap-panel-head">
                <h3><i class="fas fa-chart-area"></i> Platform growth</h3>
            </div>
            <div class="ap-chart-box">
                <canvas id="apChartApplications"
                        data-chart='<?= Html::encode(json_encode(['labels' => $monthLabels, 'apps' => $monthApps, 'users' => $monthUsers, 'orgs' => $monthOrgs])) ?>'></canvas>
            </div>
        </div>
        <div class="ap-panel ap-glass ap-module-panel">
            <div class="ap-panel-head">
                <h3><i class="fas fa-bolt"></i> Quick actions</h3>
            </div>
            <div class="ap-quick-grid">
                <a href="<?= Url::to(['site/approvals']) ?>" class="ap-quick-card">
                    <i class="fas fa-inbox"></i>
                    <div><strong>Review approvals</strong><br><small class="text-muted"><?= (int) $c['pending_applications'] ?> pending</small></div>
                </a>
                <a href="<?= Url::to(['user/index']) ?>" class="ap-quick-card">
                    <i class="fas fa-user-clock"></i>
                    <div><strong>Pending users</strong><br><small class="text-muted"><?= (int) $c['pending_users'] ?> awaiting</small></div>
                </a>
                <a href="<?= Url::to(['application/index']) ?>" class="ap-quick-card">
                    <i class="fas fa-file-lines"></i>
                    <div><strong>Applications</strong><br><small class="text-muted"><?= (int) $c['total_applications'] ?> total</small></div>
                </a>
                <a href="<?= Url::to(['site/audit-logs']) ?>" class="ap-quick-card">
                    <i class="fas fa-shield-halved"></i>
                    <div><strong>Audit logs</strong><br><small class="text-muted">Security events</small></div>
                </a>
            </div>
        </div>
    </div>

    <div class="ap-dash-grid-2">
        <div class="ap-panel ap-glass ap-module-panel">
            <div class="ap-panel-head">
                <h3><i class="fas fa-chart-bar"></i> Demand by field</h3>
            </div>
            <div class="ap-chart-box" style="height:240px">
                <canvas id="apChartField"
                        data-chart='<?= Html::encode(json_encode($byField)) ?>'></canvas>
            </div>
        </div>
        <div class="ap-panel ap-glass ap-module-panel">
            <div class="ap-panel-head">
                <h3><i class="fas fa-calendar-day"></i> Applications (14 days)</h3>
            </div>
            <div class="ap-chart-box" style="height:240px">
                <canvas id="apChartDaily"
                        data-chart='<?= Html::encode(json_encode([
                            'labels' => array_column($dailyApps, 'label'),
                            'values' => array_column($dailyApps, 'count'),
                        ])) ?>'></canvas>
            </div>
        </div>
    </div>

    <div class="ap-dash-grid-2">
        <div class="ap-panel ap-glass ap-module-panel">
            <div class="ap-panel-head">
                <h3><i class="fas fa-clock-rotate-left"></i> Recent applications</h3>
                <?= Html::a('View all', ['application/index'], ['class' => 'ap-btn ap-btn-ghost ap-btn-sm']) ?>
            </div>
            <div class="ap-timeline">
                <?php foreach ($recentApplications as $app): ?>
                    <div class="ap-timeline-item">
                        <span class="ap-timeline-dot"></span>
                        <div class="ap-timeline-body">
                            <strong><?= Html::encode($app->student->user->username ?? 'Student') ?></strong>
                            <span><?= Html::encode($app->position->title ?? '—') ?> · <?= Html::encode(ucfirst(str_replace('_', ' ', $app->status))) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($recentApplications)): ?>
                    <p class="text-muted mb-0">No recent activity.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="ap-panel ap-glass ap-module-panel">
            <div class="ap-panel-head">
                <h3><i class="fas fa-user-plus"></i> Recent registrations</h3>
                <?= Html::a('View all', ['user/index'], ['class' => 'ap-btn ap-btn-ghost ap-btn-sm']) ?>
            </div>
            <div class="ap-table-wrap">
                <table class="ap-table">
                    <thead><tr><th>User</th><th>Role</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td><?= Html::encode($user->username) ?></td>
                            <td><?= Html::encode(ucfirst($user->role)) ?></td>
                            <td>
                                <span class="ap-tag <?= $user->status == User::STATUS_ACTIVE ? 'ap-tag--success' : 'ap-tag--warning' ?>">
                                    <?= $user->status == User::STATUS_ACTIVE ? 'Active' : 'Pending' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
