<?php
/** @var \common\models\OrgInternshipProgram[] $programs */

use common\models\OrgInternshipProgram;
use yii\helpers\Html;
use yii\helpers\Url;

$csrf = Yii::$app->request->csrfParam;
$token = Yii::$app->request->getCsrfToken();
?>

<?= $this->render('@frontend/views/organization/_page_header', [
    'title' => 'Internship Programs',
    'subtitle' => 'Create cohorts, track milestones, and manage program completion.',
    'actions' => [
        Html::button('<i class="fas fa-plus"></i> New program', ['class' => 'org-btn org-btn-primary', 'data-org-open-modal' => 'programForm']),
    ],
]) ?>

<?php if (empty($programs)): ?>
    <div class="org-empty-state"><div><i class="fas fa-diagram-project"></i></div><h3>No programs yet</h3><p>Create your first internship program to assign students and track progress.</p></div>
<?php else: ?>
    <div class="org-kpi-grid">
        <?php foreach ($programs as $program): ?>
            <a href="<?= Url::to(['view', 'id' => $program->id]) ?>" class="org-kpi-card text-decoration-none" style="display:block">
                <div class="kpi-label"><?= Html::encode(OrgInternshipProgram::statusOptions()[$program->status] ?? $program->status) ?></div>
                <div class="kpi-value" style="font-size:1.1rem"><?= Html::encode($program->title) ?></div>
                <div class="kpi-trend"><?= (int) $program->completion_percent ?>% complete · <?= Html::encode($program->category ?: 'General') ?></div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="org-modal-backdrop" id="programFormModal" data-org-modal="programForm">
    <div class="org-modal">
        <h2>New internship program</h2>
        <form class="org-form-grid" data-org-ajax-form="<?= Url::to(['save']) ?>">
            <input type="hidden" name="<?= $csrf ?>" value="<?= $token ?>">
            <div><label>Title</label><input name="OrgInternshipProgram[title]" required></div>
            <div><label>Category</label><input name="OrgInternshipProgram[category]" placeholder="Engineering, Business…"></div>
            <div><label>Status</label>
                <select name="OrgInternshipProgram[status]">
                    <?php foreach (OrgInternshipProgram::statusOptions() as $k => $v): ?>
                        <option value="<?= Html::encode($k) ?>"><?= Html::encode($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Start</label><input type="date" name="OrgInternshipProgram[start_date]"></div>
            <div><label>End</label><input type="date" name="OrgInternshipProgram[end_date]"></div>
            <div><label>Capacity</label><input type="number" name="OrgInternshipProgram[capacity]" value="20"></div>
            <div><label>Description</label><textarea name="OrgInternshipProgram[description]" rows="3"></textarea></div>
            <button type="submit" class="org-btn org-btn-primary">Create program</button>
        </form>
    </div>
</div>
