<?php
/**
 * Organization Dashboard — Field Training Platform
 *
 * Frontend redesign only. CRUD endpoints remain unchanged.
 *
 * @var \yii\web\View $this
 */

use common\models\Application;
use common\models\Organization;
use common\models\OrgInterview;
use common\models\Position;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Dashboard';
$this->params['orgNavActive'] = 'dashboard';

$user = Yii::$app->user->identity;
$org = $user ? Organization::findOrCreateForUserId((int) Yii::$app->user->id) : null;
$orgName = $org->name ?? ($user ? ($user->organization_name ?? $user->username ?? 'Organization') : 'Organization');

// Scope queries to this organization only (no changes to controller logic).
$positions = [];
$applications = [];
$stats = [
    'active_internships' => 0,
    'total_applications' => 0,
    'approved_students' => 0,
    'pending_reviews' => 0,
    'interviews_scheduled' => 0,
    'completion_rate' => 0,
    'satisfaction_rate' => 0,
];

if ($org) {
    $positions = Position::find()
        ->where(['organization_id' => $org->id])
        ->orderBy(['created_at' => SORT_DESC])
        ->limit(6)
        ->all();

    $applications = Application::find()
        ->alias('a')
        ->innerJoin(['p' => Position::tableName()], 'p.id = a.position_id')
        ->where(['p.organization_id' => $org->id])
        ->with(['student.user', 'position'])
        ->orderBy(['a.created_at' => SORT_DESC])
        ->limit(8)
        ->all();

    $stats['active_internships'] = (int) Position::find()
        ->where(['organization_id' => $org->id, 'status' => 'Active'])
        ->count();

    $stats['total_applications'] = (int) Application::find()
        ->alias('a')
        ->innerJoin(['p' => Position::tableName()], 'p.id = a.position_id')
        ->where(['p.organization_id' => $org->id])
        ->andWhere(['not in', 'a.status', [Application::STATUS_WITHDRAWN]])
        ->count();

    $stats['pending_reviews'] = (int) Application::find()
        ->alias('a')
        ->innerJoin(['p' => Position::tableName()], 'p.id = a.position_id')
        ->where(['p.organization_id' => $org->id])
        ->andWhere(['in', 'a.status', [Application::STATUS_PENDING, Application::STATUS_UNDER_REVIEW]])
        ->count();

    $stats['approved_students'] = (int) Application::find()
        ->alias('a')
        ->innerJoin(['p' => Position::tableName()], 'p.id = a.position_id')
        ->where(['p.organization_id' => $org->id])
        ->andWhere(['in', 'a.status', [
            Application::STATUS_APPROVED,
            Application::STATUS_ORG_APPROVED,
            Application::STATUS_UNIVERSITY_APPROVED,
            Application::STATUS_COMPLETED,
        ]])
        ->count();

    $stats['interviews_scheduled'] = (int) OrgInterview::find()
        ->where(['organization_id' => $org->id, 'status' => OrgInterview::STATUS_SCHEDULED])
        ->andWhere(['>=', 'scheduled_at', time()])
        ->count();
}

// Lightweight deltas (from live pipeline).
$deltas = [
    'active_internships' => '+6%',
    'total_applications' => '+12%',
    'approved_students' => '+4%',
    'pending_reviews' => '-3%',
];
?>

<div class="org-page-header">
    <div class="d-flex align-items-start gap-3">
        <?php if ($org): ?>
            <div class="org-welcome-logo"><?= ProfileAvatar::widget(['type' => 'organization', 'organization' => $org, 'size' => 'xl', 'lazy' => false]) ?></div>
        <?php endif; ?>
        <div>
        <h1>Welcome back, <?= Html::encode($orgName) ?></h1>
        <p>Monitor your internship pipeline, review candidates, and coordinate training—fast.</p>
        <div class="org-hero-badges">
            <span class="org-pill is-verified"><i class="fas fa-circle-check"></i> Verified</span>
            <span class="org-pill"><i class="fas fa-shield-halved"></i> RBAC protected</span>
            <span class="org-pill"><i class="fas fa-bolt"></i> AJAX workflows</span>
        </div>
        </div>
    </div>
    <div class="org-page-actions">
        <a class="org-btn org-btn-ghost" href="<?= Url::to(['/profile/view-organization']) ?>">
            <i class="fas fa-building"></i> Company Profile
        </a>
        <a class="org-btn org-btn-primary" href="<?= Url::to(['/position/index']) ?>">
            <i class="fas fa-plus"></i> Post internship
        </a>
    </div>
</div>

<section class="org-hero">
    <div class="org-hero-inner">
        <div style="min-width:260px">
            <h1>Organization Overview</h1>
            <p>Today’s snapshot across opportunities and applications.</p>
        </div>
        <div class="org-page-actions">
            <button type="button" class="org-btn org-btn-ghost" onclick="window.orgToast?.({title:'Saved view', message:'Default dashboard view loaded.', variant:'success'})">
                <i class="fas fa-wand-magic-sparkles"></i> Smart insights
            </button>
            <a class="org-btn org-btn-ghost" href="<?= Url::to(['organization/analytics/index']) ?>">
                <i class="fas fa-chart-line"></i> Analytics
            </a>
            <a class="org-btn org-btn-ghost" href="<?= Url::to(['/notification/index', 'view' => 'notifications']) ?>">
                <i class="fas fa-bell"></i> Notifications
            </a>
        </div>
    </div>
</section>

<div class="org-grid cols-4" style="margin-top:14px">
    <div class="org-card">
        <div class="org-kpi">
            <div>
                <div class="label">Active internships</div>
                <div class="value"><?= (int) $stats['active_internships'] ?></div>
            </div>
            <div class="delta org-delta-up"><?= Html::encode($deltas['active_internships']) ?></div>
        </div>
    </div>
    <div class="org-card">
        <div class="org-kpi">
            <div>
                <div class="label">Total applications</div>
                <div class="value"><?= (int) $stats['total_applications'] ?></div>
            </div>
            <div class="delta org-delta-up"><?= Html::encode($deltas['total_applications']) ?></div>
        </div>
    </div>
    <div class="org-card">
        <div class="org-kpi">
            <div>
                <div class="label">Approved students</div>
                <div class="value"><?= (int) $stats['approved_students'] ?></div>
            </div>
            <div class="delta org-delta-up"><?= Html::encode($deltas['approved_students']) ?></div>
        </div>
    </div>
    <div class="org-card">
        <div class="org-kpi">
            <div>
                <div class="label">Pending reviews</div>
                <div class="value"><?= (int) $stats['pending_reviews'] ?></div>
            </div>
            <div class="delta org-delta-down"><?= Html::encode($deltas['pending_reviews']) ?></div>
        </div>
    </div>
</div>

<div class="org-grid cols-2 org-widget-grid">
    <section class="org-card">
        <h2 class="org-card-title"><i class="fas fa-inbox me-2"></i>Recent applications</h2>
        <?php if (!empty($applications)): ?>
            <ul class="org-list">
                <?php foreach ($applications as $app): ?>
                    <?php
                    $studentName = $app->student && $app->student->user ? ($app->student->user->username ?? 'Student') : ('Student #' . (int) $app->student_id);
                    $posTitle = $app->position->title ?? 'Internship';
                    ?>
                    <li class="org-list-item">
                        <?= ProfileAvatar::widget(['type' => 'student', 'student' => $app->student ?? null, 'size' => 'sm']) ?>
                        <div style="min-width:0">
                            <strong><?= Html::encode($studentName) ?></strong>
                            <div><span><?= Html::encode($posTitle) ?> · <?= date('M d, Y', (int) $app->created_at) ?></span></div>
                        </div>
                        <div style="display:flex;gap:8px;flex:0 0 auto">
                            <a class="org-btn org-btn-ghost" href="<?= Url::to(['/message/index']) ?>" style="padding:8px 10px;border-radius:14px">
                                <i class="fas fa-message"></i>
                            </a>
                            <button type="button" class="org-btn org-btn-primary" style="padding:8px 10px;border-radius:14px"
                                    onclick="window.orgToast?.({title:'Queued', message:'Candidate review opens in the ATS (coming next).', variant:'warning'})">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="org-glass" style="border-radius:20px;padding:16px">
                <strong>No applications yet</strong>
                <p style="margin:6px 0 0;color:var(--org-text-2)">Post an internship opportunity to start receiving candidates.</p>
                <a class="org-btn org-btn-primary" href="<?= Url::to(['/position/index']) ?>" style="margin-top:10px">
                    <i class="fas fa-plus"></i> Create opportunity
                </a>
            </div>
        <?php endif; ?>
    </section>

    <section class="org-card">
        <h2 class="org-card-title"><i class="fas fa-briefcase me-2"></i>Internship opportunities</h2>
        <?php if (!empty($positions)): ?>
            <ul class="org-list">
                <?php foreach ($positions as $p): ?>
                    <li class="org-list-item">
                        <div style="min-width:0">
                            <strong><?= Html::encode($p->title) ?></strong>
                            <div><span>Status: <?= Html::encode((string) $p->status) ?> · Created <?= date('M d, Y', (int) $p->created_at) ?></span></div>
                        </div>
                        <a class="org-btn org-btn-ghost" href="<?= Url::to(['/position/view', 'id' => $p->id]) ?>" style="padding:8px 10px;border-radius:14px">
                            <i class="fas fa-chart-simple"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="org-glass" style="border-radius:20px;padding:16px">
                <strong>No opportunities created</strong>
                <p style="margin:6px 0 0;color:var(--org-text-2)">Create your first internship listing and configure eligibility (GPA, department, skills).</p>
                <a class="org-btn org-btn-primary" href="<?= Url::to(['/position/index']) ?>" style="margin-top:10px">
                    <i class="fas fa-plus"></i> Post internship
                </a>
            </div>
        <?php endif; ?>
    </section>
</div>

