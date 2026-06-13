<?php
use common\widgets\ProfileAvatar;
use frontend\assets\OrganizationModulesAsset;
use yii\helpers\Html;

OrganizationModulesAsset::register($this);

$this->title = 'Company Profile';
$positionCount = (int) \common\models\Position::find()->where(['organization_id' => $model->id])->count();
$applicationCount = (int) \common\models\Application::find()
    ->alias('a')
    ->innerJoin(['p' => \common\models\Position::tableName()], 'p.id = a.position_id')
    ->where(['p.organization_id' => $model->id])
    ->count();
$profileStrength = (int) round(100 * count(array_filter([
    !empty($model->name),
    !empty($model->description),
    !empty($model->location),
    !empty($model->website),
])) / 4);
?>

<?= $this->render('@frontend/views/organization/_page_header', [
    'title' => 'Company Profile',
    'subtitle' => 'Public-facing organization identity and recruitment brand details.',
    'actions' => [
        Html::a('<i class="fas fa-pen me-1"></i> Edit Profile', ['/profile/organization'], ['class' => 'org-btn org-btn-primary']),
        Html::a('<i class="fas fa-gauge-high me-1"></i> Dashboard', ['/dashboard/index'], ['class' => 'org-btn org-btn-ghost']),
    ],
]) ?>

<div class="org-kpi-grid">
    <div class="org-kpi-card"><div class="kpi-label">Profile Strength</div><div class="kpi-value" data-org-counter="<?= $profileStrength ?>">0</div><div class="kpi-trend">verification-ready</div></div>
    <div class="org-kpi-card"><div class="kpi-label">Positions Posted</div><div class="kpi-value" data-org-counter="<?= $positionCount ?>">0</div></div>
    <div class="org-kpi-card"><div class="kpi-label">Applications Received</div><div class="kpi-value" data-org-counter="<?= $applicationCount ?>">0</div></div>
    <div class="org-kpi-card"><div class="kpi-label">Member Since</div><div class="kpi-value" style="font-size:1.05rem"><?= Yii::$app->formatter->asDate((int) ($model->user->created_at ?? time())) ?></div></div>
</div>

<div class="org-chart-grid">
    <section class="org-chart-card" style="grid-column:span 7">
        <h3>Organization Identity</h3>
        <div class="mb-3">
            <?= ProfileAvatar::widget(['type' => 'organization', 'organization' => $model, 'size' => 'xl', 'lazy' => false]) ?>
        </div>
        <table class="org-data-table">
            <tbody>
                <tr><td style="width:220px">Organization Name</td><td><?= Html::encode($model->name ?: 'Not provided') ?></td></tr>
                <tr><td>Email</td><td><?= Html::encode($model->user->email ?? 'Not provided') ?></td></tr>
                <tr><td>Location</td><td><?= Html::encode($model->location ?: 'Not provided') ?></td></tr>
                <tr><td>Website</td><td><?= $model->website ? Html::a(Html::encode($model->website), $model->website, ['target' => '_blank']) : 'Not provided' ?></td></tr>
            </tbody>
        </table>
    </section>

    <aside class="org-chart-card" style="grid-column:span 5">
        <h3>Branding & Trust</h3>
        <?php
        $verifyLabels = \common\models\Organization::verificationOptions();
        $verifyStatus = $model->verification_status ?? \common\models\Organization::VERIFICATION_PENDING;
        ?>
        <div class="org-kanban-card"><strong>Verification</strong><div style="color:var(--org-text-2)"><?= Html::encode($verifyLabels[$verifyStatus] ?? $verifyStatus) ?></div></div>
        <div class="org-kanban-card"><strong>Industry Focus</strong><div style="color:var(--org-text-2)">Derived from internship categories</div></div>
        <div class="org-kanban-card"><strong>Brand Completeness</strong><div style="color:var(--org-text-2)"><?= $profileStrength ?>% complete</div></div>
    </aside>

    <section class="org-chart-card" style="grid-column:span 12">
        <h3>Organization Description</h3>
        <p style="margin:0;color:var(--org-text-2);line-height:1.75">
            <?= $model->description ? nl2br(Html::encode($model->description)) : 'No organization description provided yet. Add mission, values, and internship culture details to improve applicant conversion.' ?>
        </p>
    </section>
</div>
