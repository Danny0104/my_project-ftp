<?php
/** @var \common\models\OrgCoordination[] $records */
/** @var \common\models\Student[] $students */

use common\models\OrgCoordination;
use yii\helpers\Html;
use yii\helpers\Url;

$csrf = Yii::$app->request->csrfParam;
$token = Yii::$app->request->getCsrfToken();
?>

<?= $this->render('@frontend/views/organization/_page_header', [
    'title' => 'University Coordination',
    'subtitle' => 'Supervisor workflows, approvals, and academic reporting.',
    'actions' => [
        Html::button('<i class="fas fa-plus"></i> New case', ['class' => 'org-btn org-btn-primary', 'data-org-open-modal' => 'coordForm']),
    ],
]) ?>

<?php if (empty($records)): ?>
    <div class="org-empty-state"><div><i class="fas fa-building-columns"></i></div><h3>No coordination cases</h3><p>Start a university coordination workflow for a placed student.</p></div>
<?php else: ?>
    <table class="org-data-table">
        <thead><tr><th>Student</th><th>University</th><th>Supervisor</th><th>Workflow</th><th>Approval</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($records as $row): ?>
            <tr>
                <td><?= Html::encode($row->student->user->username ?? 'Student') ?></td>
                <td><?= Html::encode($row->university_name ?: '—') ?></td>
                <td><?= Html::encode($row->supervisor_name ?: '—') ?></td>
                <td><?= Html::encode(OrgCoordination::workflowOptions()[$row->workflow_status] ?? $row->workflow_status) ?></td>
                <td><span class="org-chip"><?= Html::encode(OrgCoordination::approvalOptions()[$row->approval_status] ?? $row->approval_status) ?></span></td>
                <td><?= Html::a('Open', ['view', 'id' => $row->id], ['class' => 'org-btn org-btn-ghost org-btn-sm']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="org-modal-backdrop" id="coordFormModal" data-org-modal="coordForm">
    <div class="org-modal">
        <h2>University coordination</h2>
        <form class="org-form-grid" data-org-ajax-form="<?= Url::to(['save']) ?>" enctype="multipart/form-data">
            <input type="hidden" name="<?= $csrf ?>" value="<?= $token ?>">
            <div><label>Student</label>
                <select name="OrgCoordination[student_id]" required>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int) $s->id ?>"><?= Html::encode($s->user->username ?? 'Student') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>University</label><input name="OrgCoordination[university_name]"></div>
            <div><label>Supervisor name</label><input name="OrgCoordination[supervisor_name]"></div>
            <div><label>Supervisor email</label><input type="email" name="OrgCoordination[supervisor_email]"></div>
            <div><label>Notes</label><textarea name="OrgCoordination[progress_notes]" rows="3"></textarea></div>
            <button type="submit" class="org-btn org-btn-primary">Create</button>
        </form>
    </div>
</div>
