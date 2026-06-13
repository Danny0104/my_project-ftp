<?php
/** @var \common\models\OrgInterview[] $interviews */
/** @var \common\models\OrgInterview[] $upcoming */
/** @var string $viewMode */
/** @var \common\models\Student[] $students */

use common\models\OrgInterview;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

$this->title = 'Interviews';
$csrf = Yii::$app->request->csrfParam;
$token = Yii::$app->request->getCsrfToken();
?>

<?= $this->render('@frontend/views/organization/_page_header', [
    'title' => 'Interviews',
    'subtitle' => 'Schedule, track, and evaluate candidate interviews.',
    'actions' => [
        Html::button('<i class="fas fa-plus"></i> Schedule', ['class' => 'org-btn org-btn-primary', 'data-org-open-modal' => 'interviewForm']),
    ],
]) ?>

<div class="org-view-tabs">
    <?= Html::a('List', ['index', 'view' => 'list'], ['class' => $viewMode === 'list' ? 'is-active' : '']) ?>
    <?= Html::a('Calendar', ['index', 'view' => 'calendar'], ['class' => $viewMode === 'calendar' ? 'is-active' : '']) ?>
    <?= Html::a('Kanban', ['index', 'view' => 'kanban'], ['class' => $viewMode === 'kanban' ? 'is-active' : '']) ?>
</div>

<?php if ($viewMode === 'kanban'): ?>
    <div class="org-kanban-row">
        <?php foreach (OrgInterview::statusOptions() as $status => $label): ?>
            <div class="org-kanban-col">
                <h4><?= Html::encode($label) ?></h4>
                <?php foreach ($interviews as $iv): ?>
                    <?php if ($iv->status !== $status) continue; ?>
                    <div class="org-kanban-card">
                        <strong><?= Html::encode($iv->title) ?></strong>
                        <div style="font-size:12px;color:var(--org-text-3)"><?= Yii::$app->formatter->asDatetime($iv->scheduled_at) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php elseif ($viewMode === 'calendar'): ?>
    <div class="org-chart-card">
        <div class="org-calendar-grid">
            <?php for ($d = 1; $d <= 28; $d++): ?>
                <div class="org-cal-day">
                    <strong><?= $d ?></strong>
                    <?php foreach ($interviews as $iv): ?>
                        <?php if ((int) date('j', $iv->scheduled_at) === $d): ?>
                            <div class="org-cal-event"><?= Html::encode(StringHelper::truncate($iv->title, 20)) ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>
<?php else: ?>
    <?php if (empty($interviews)): ?>
        <div class="org-empty-state"><div><i class="fas fa-video"></i></div><h3>No interviews scheduled</h3><p>Create your first interview from a student profile or the schedule button.</p></div>
    <?php else: ?>
        <table class="org-data-table">
            <thead><tr><th>Title</th><th>Candidate</th><th>When</th><th>Status</th><th>Score</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($interviews as $iv): ?>
                <tr>
                    <td><?= Html::encode($iv->title) ?></td>
                    <td>
                        <div class="org-candidate-card">
                            <?= ProfileAvatar::widget(['type' => 'student', 'student' => $iv->student ?? null, 'size' => 'sm']) ?>
                            <span><?= Html::encode($iv->student->user->username ?? 'Student') ?></span>
                        </div>
                    </td>
                    <td><?= Yii::$app->formatter->asDatetime($iv->scheduled_at) ?></td>
                    <td><span class="org-chip"><?= Html::encode(OrgInterview::statusOptions()[$iv->status] ?? $iv->status) ?></span></td>
                    <td><?= $iv->evaluation_score !== null ? (int) $iv->evaluation_score . '%' : '—' ?></td>
                    <td class="org-opp-actions org-opp-actions--inline">
                        <?php if ($iv->status === OrgInterview::STATUS_SCHEDULED): ?>
                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-org-eval="<?= (int) $iv->id ?>">Evaluate</button>
                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-org-interview-edit="<?= (int) $iv->id ?>"
                                    data-title="<?= Html::encode($iv->title) ?>"
                                    data-scheduled="<?= Html::encode(date('Y-m-d\TH:i', (int) $iv->scheduled_at)) ?>"
                                    data-link="<?= Html::encode((string) $iv->meeting_link) ?>"
                                    data-interviewer="<?= Html::encode((string) $iv->interviewer_name) ?>">Reschedule</button>
                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-org-interview-cancel="<?= (int) $iv->id ?>">Cancel</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>

<div class="org-modal-backdrop" id="interviewFormModal" data-org-modal="interviewForm">
    <div class="org-modal">
        <h2>Schedule interview</h2>
        <form class="org-form-grid" data-org-ajax-form="<?= Url::to(['schedule']) ?>">
            <input type="hidden" name="<?= $csrf ?>" value="<?= $token ?>">
            <input type="hidden" name="OrgInterview[interview_stage]" value="interview">
            <div><label>Title</label><input name="OrgInterview[title]" required placeholder="Technical interview"></div>
            <div><label>Student</label>
                <select name="OrgInterview[student_id]" required>
                    <option value="">Select…</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= (int) $s->id ?>"><?= Html::encode($s->user->username ?? 'Student') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Date & time</label><input type="datetime-local" name="scheduled_at" required></div>
            <div><label>Meeting link</label><input name="OrgInterview[meeting_link]" placeholder="https://…"></div>
            <div><label>Interviewer</label><input name="OrgInterview[interviewer_name]"></div>
            <button type="submit" class="org-btn org-btn-primary">Save</button>
        </form>
    </div>
</div>

<?php
$evalUrl = Url::to(['evaluate']);
$updateUrl = Url::to(['update']);
$cancelUrl = Url::to(['update-status']);
$this->registerJs(<<<JS
(function(){
  var csrf = '{$csrf}', token = '{$token}';
  document.querySelectorAll('[data-org-eval]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var score = window.prompt('Interview score (0-100):', '85');
      if (score === null) return;
      var notes = window.prompt('Evaluation notes:', '') || '';
      var fd = new FormData();
      fd.append('id', btn.getAttribute('data-org-eval'));
      fd.append('evaluation_score', score);
      fd.append('evaluation_notes', notes);
      fd.append(csrf, token);
      btn.disabled = true;
      fetch('{$evalUrl}', {method:'POST', body:fd}).then(r=>r.json()).then(function(res){
        if(res.success) location.reload();
        else btn.disabled = false;
      });
    });
  });
  document.querySelectorAll('[data-org-interview-cancel]').forEach(function(btn){
    btn.addEventListener('click', function(){
      if (!window.confirm('Cancel this interview? The candidate will be notified.')) return;
      var fd = new FormData();
      fd.append('id', btn.getAttribute('data-org-interview-cancel'));
      fd.append('status', 'cancelled');
      fd.append(csrf, token);
      btn.disabled = true;
      fetch('{$cancelUrl}', {method:'POST', body:fd}).then(r=>r.json()).then(function(res){
        if(res.success) location.reload();
        else btn.disabled = false;
      });
    });
  });
  document.querySelectorAll('[data-org-interview-edit]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var when = window.prompt('New date & time (YYYY-MM-DDTHH:MM):', btn.getAttribute('data-scheduled'));
      if (!when) return;
      var fd = new FormData();
      fd.append('id', btn.getAttribute('data-org-interview-edit'));
      fd.append('OrgInterview[title]', btn.getAttribute('data-title') || '');
      fd.append('OrgInterview[meeting_link]', btn.getAttribute('data-link') || '');
      fd.append('OrgInterview[interviewer_name]', btn.getAttribute('data-interviewer') || '');
      fd.append('scheduled_at', when);
      fd.append(csrf, token);
      btn.disabled = true;
      fetch('{$updateUrl}', {method:'POST', body:fd}).then(r=>r.json()).then(function(res){
        if(res.success) location.reload();
        else btn.disabled = false;
      });
    });
  });
})();
JS
);
?>
