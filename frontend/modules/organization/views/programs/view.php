<?php
/** @var \common\models\OrgInternshipProgram $program */
/** @var \common\models\OrgProgramStudent[] $enrollments */
/** @var \common\models\Student[] $students */

use yii\helpers\Html;
use yii\helpers\Url;

$csrf = Yii::$app->request->csrfParam;
$token = Yii::$app->request->getCsrfToken();
?>

<?= $this->render('@frontend/views/organization/_page_header', [
    'title' => $program->title,
    'subtitle' => ($program->start_date ? $program->start_date . ' → ' . $program->end_date : 'No dates set'),
    'actions' => [
        Html::a('Back', ['index'], ['class' => 'org-btn org-btn-ghost']),
    ],
]) ?>

<div class="org-kpi-grid">
    <div class="org-kpi-card"><div class="kpi-label">Completion</div><div class="kpi-value" data-org-counter="<?= (int) $program->completion_percent ?>">0</div><div class="kpi-trend">%</div></div>
    <div class="org-kpi-card"><div class="kpi-label">Enrolled</div><div class="kpi-value"><?= count($enrollments) ?></div></div>
    <div class="org-kpi-card"><div class="kpi-label">Capacity</div><div class="kpi-value"><?= (int) $program->capacity ?></div></div>
</div>

<p style="color:var(--org-text-2)"><?= Html::encode($program->description) ?></p>

<h3 class="mt-4 mb-3" style="color:var(--org-text)">Cohort</h3>
<form class="org-filter-bar" data-org-ajax-form="<?= Url::to(['enroll']) ?>">
    <input type="hidden" name="<?= $csrf ?>" value="<?= $token ?>">
    <input type="hidden" name="program_id" value="<?= (int) $program->id ?>">
    <div style="flex:1"><label>Assign student</label>
        <select name="student_id" required>
            <option value="">Select student…</option>
            <?php foreach ($students as $s): ?>
                <option value="<?= (int) $s->id ?>"><?= Html::encode($s->user->username ?? 'Student') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="org-btn org-btn-primary">Enroll</button>
</form>

<table class="org-data-table mt-3">
    <thead><tr><th>Student</th><th>Progress</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($enrollments as $row): ?>
        <tr>
            <td><?= Html::encode($row->student->user->username ?? 'Student') ?></td>
            <td><?= (int) $row->progress_percent ?>%</td>
            <td><?= Html::encode($row->status) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
