<?php
/** @var \common\models\Student $student */
/** @var \common\models\Application[] $applications */
/** @var \common\models\OrgCandidateNote[] $notes */
/** @var int $matchScore */

use common\models\Application;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\Url;

$name = $student->user->username ?? 'Student';
$csrf = Yii::$app->request->csrfParam;
$token = Yii::$app->request->getCsrfToken();
?>

<?= $this->render('@frontend/views/organization/_page_header', [
    'title' => $name,
    'titleAvatar' => ProfileAvatar::widget(['type' => 'student', 'student' => $student, 'size' => 'lg', 'lazy' => false]),
    'subtitle' => $student->university . ' · ' . ($student->field_of_study ?: 'Field not set'),
    'actions' => [
        Html::a('<i class="fas fa-arrow-left"></i> Back', ['index'], ['class' => 'org-btn org-btn-ghost']),
        Html::a('<i class="fas fa-envelope"></i> Message', ['/notification/index', 'view' => 'messages'], ['class' => 'org-btn org-btn-primary']),
    ],
]) ?>

<div class="org-kpi-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr))">
    <div class="org-kpi-card"><div class="kpi-label">Match score</div><div class="kpi-value"><span class="org-match-pill"><i class="fas fa-sparkles"></i> <?= (int) $matchScore ?>%</span></div></div>
    <div class="org-kpi-card"><div class="kpi-label">GPA</div><div class="kpi-value"><?= $student->gpa !== null ? Html::encode($student->gpa) : '—' ?></div></div>
    <div class="org-kpi-card"><div class="kpi-label">Applications</div><div class="kpi-value"><?= count($applications) ?></div></div>
</div>

<div class="org-chart-grid">
    <div class="org-chart-card" style="grid-column:span 7">
        <h3>Profile</h3>
        <p style="color:var(--org-text-2);font-size:14px"><?= Html::encode($student->personal_statement ?: 'No personal statement provided.') ?></p>
        <?php if ($student->cv): ?>
            <p><?= Html::a('<i class="fas fa-file-pdf"></i> Download CV', ['download-cv', 'id' => $student->id], ['class' => 'org-btn org-btn-ghost', 'data-pjax' => 0]) ?></p>
        <?php endif; ?>
        <h3 class="mt-4">Application timeline</h3>
        <?php foreach ($applications as $app): ?>
            <div class="org-kanban-card mb-2">
                <strong><?= Html::encode($app->position->title ?? 'Position') ?></strong>
                <span class="org-chip ms-2"><?= Html::encode($app->status) ?></span>
                <div style="font-size:12px;color:var(--org-text-3);margin-top:6px"><?= Yii::$app->formatter->asDatetime($app->created_at) ?></div>
                <div class="org-sticky-actions">
                    <?= Html::button('Shortlist', ['class' => 'org-btn org-btn-ghost org-btn-sm', 'data-org-app-action' => 'org_approved', 'data-app-id' => $app->id, 'data-status-url' => Url::to(['update-status'])]) ?>
                    <?= Html::button('Reject', ['class' => 'org-btn org-btn-ghost org-btn-sm', 'data-org-app-action' => 'rejected', 'data-app-id' => $app->id, 'data-status-url' => Url::to(['update-status'])]) ?>
                    <?= Html::button('Schedule interview', [
                        'class' => 'org-btn org-btn-primary org-btn-sm',
                        'data-org-schedule-interview' => $app->id,
                        'data-schedule-url' => Url::to(['schedule-interview']),
                    ]) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="org-chart-card" style="grid-column:span 5">
        <h3>Recruiter notes</h3>
        <form data-org-add-note="<?= (int) $student->id ?>">
            <textarea name="note" rows="3" placeholder="Add a private note…" class="w-100 mb-2" style="background:var(--org-surface);border:1px solid var(--org-border);color:var(--org-text);border-radius:10px;padding:10px"></textarea>
            <button type="submit" class="org-btn org-btn-primary org-btn-sm">Save note</button>
        </form>
        <div class="mt-3">
            <?php foreach ($notes as $note): ?>
                <div class="org-kanban-card">
                    <p style="margin:0;font-size:13px"><?= Html::encode($note->note) ?></p>
                    <small style="color:var(--org-text-3)"><?= Yii::$app->formatter->asRelativeTime($note->created_at) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$noteUrl = Url::to(['add-note']);
$this->registerJs(<<<JS
(function(){
  var csrf = '{$csrf}', token = '{$token}';
  document.querySelector('[data-org-add-note]')?.addEventListener('submit', function(e){
    e.preventDefault();
    var fd = new FormData(e.target);
    fd.append('student_id', e.target.getAttribute('data-org-add-note'));
    fd.append(csrf, token);
    fetch('{$noteUrl}', {method:'POST', body:fd}).then(r=>r.json()).then(function(res){
      if(res.success) location.reload();
    });
  });
})();
JS
);
?>
