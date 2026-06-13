<?php
/** @var \common\models\OrgCoordination $model */

use common\models\OrgCoordination;
use yii\helpers\Html;
use yii\helpers\Url;

$csrf = Yii::$app->request->csrfParam;
$token = Yii::$app->request->getCsrfToken();
?>

<?= $this->render('@frontend/views/organization/_page_header', [
    'title' => 'Coordination case',
    'subtitle' => $model->student->user->username ?? 'Student',
    'actions' => [Html::a('Back', ['index'], ['class' => 'org-btn org-btn-ghost'])],
]) ?>

<div class="org-chart-grid">
    <div class="org-chart-card" style="grid-column:span 6">
        <h3>Workflow timeline</h3>
        <ol style="color:var(--org-text-2);font-size:14px;padding-left:20px">
            <?php foreach (OrgCoordination::workflowOptions() as $key => $label): ?>
                <li style="margin-bottom:8px;<?= $model->workflow_status === $key ? 'color:var(--org-primary);font-weight:700' : '' ?>"><?= Html::encode($label) ?></li>
            <?php endforeach; ?>
        </ol>
    </div>
    <div class="org-chart-card" style="grid-column:span 6">
        <h3>Supervisor</h3>
        <p><strong><?= Html::encode($model->supervisor_name ?: 'Not assigned') ?></strong><br>
        <?= Html::encode($model->supervisor_email ?: '') ?></p>
        <p style="color:var(--org-text-2)"><?= Html::encode($model->progress_notes) ?></p>
        <?php if ($model->document_path): ?>
            <p><?= Html::a('Download document', ['/' . ltrim($model->document_path, '/')], ['class' => 'org-btn org-btn-ghost', 'target' => '_blank']) ?></p>
        <?php endif; ?>
        <form data-org-ajax-form="<?= Url::to(['approve']) ?>" class="org-sticky-actions">
            <input type="hidden" name="<?= $csrf ?>" value="<?= $token ?>">
            <input type="hidden" name="id" value="<?= (int) $model->id ?>">
            <input type="hidden" name="approval_status" value="approved">
            <button type="submit" class="org-btn org-btn-primary">Approve</button>
            <button type="button" class="org-btn org-btn-ghost" data-org-reject="<?= (int) $model->id ?>">Request revision</button>
        </form>
    </div>
</div>

<?php
$approveUrl = Url::to(['approve']);
$this->registerJs(<<<JS
document.querySelector('[data-org-reject]')?.addEventListener('click', function(){
  var fd = new FormData();
  fd.append('id', this.getAttribute('data-org-reject'));
  fd.append('approval_status', 'revision');
  fd.append('{$csrf}', '{$token}');
  fetch('{$approveUrl}', {method:'POST', body:fd}).then(r=>r.json()).then(function(res){ if(res.success) location.reload(); });
});
JS
);
?>
