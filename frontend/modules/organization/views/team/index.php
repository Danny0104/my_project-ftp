<?php
/** @var \common\models\OrgTeamMember[] $members */
/** @var \common\models\OrgTeamActivity[] $activity */
/** @var array $roleOptions */

use common\models\OrgTeamMember;
use yii\helpers\Html;
use yii\helpers\Url;

$csrf = Yii::$app->request->csrfParam;
$token = Yii::$app->request->getCsrfToken();
?>

<?= $this->render('@frontend/views/organization/_page_header', [
    'title' => 'Team Management',
    'subtitle' => 'Invite recruiters, assign roles, and audit team activity.',
    'actions' => [
        Html::button('<i class="fas fa-user-plus"></i> Invite', ['class' => 'org-btn org-btn-primary', 'data-org-open-modal' => 'teamForm']),
    ],
]) ?>

<div class="org-kpi-grid">
    <div class="org-kpi-card">
        <div class="kpi-label">Team members</div>
        <div class="kpi-value" data-org-counter="<?= count($members) ?>">0</div>
    </div>
    <div class="org-kpi-card">
        <div class="kpi-label">Active members</div>
        <div class="kpi-value" data-org-counter="<?= count(array_filter($members, fn($m) => $m->status === \common\models\OrgTeamMember::STATUS_ACTIVE)) ?>">0</div>
    </div>
    <div class="org-kpi-card">
        <div class="kpi-label">Pending invites</div>
        <div class="kpi-value" data-org-counter="<?= count(array_filter($members, fn($m) => $m->status === \common\models\OrgTeamMember::STATUS_INVITED)) ?>">0</div>
    </div>
    <div class="org-kpi-card">
        <div class="kpi-label">Permission coverage</div>
        <div class="kpi-value">RBAC</div>
        <div class="kpi-trend">Role matrix active</div>
    </div>
</div>

<div class="org-chart-grid">
    <div class="org-chart-card" style="grid-column:span 7">
        <h3>Team members</h3>
        <table class="org-data-table">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Permissions</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($members as $member): ?>
                <tr>
                    <td><?= Html::encode($member->name) ?></td>
                    <td><?= Html::encode($member->email) ?></td>
                    <td>
                        <select class="org-team-role" data-member-id="<?= (int) $member->id ?>" style="background:var(--org-surface);border:1px solid var(--org-border);color:var(--org-text);border-radius:8px;padding:4px 8px">
                            <?php foreach ($roleOptions as $k => $label): ?>
                                <option value="<?= Html::encode($k) ?>" <?= $member->role === $k ? 'selected' : '' ?>><?= Html::encode($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <span class="org-chip"><?= Html::encode($member->status) ?></span>
                    </td>
                    <td style="font-size:12px;color:var(--org-text-2)">
                        <?= Html::encode(implode(', ', array_slice($member->getPermissions(), 0, 3))) ?>
                        <?= count($member->getPermissions()) > 3 ? '…' : '' ?>
                    </td>
                    <td>
                        <?php if (!$member->user_id || (int) $member->user_id !== (int) Yii::$app->user->id): ?>
                            <button type="button" class="org-btn org-btn-ghost org-btn-sm" data-org-remove-member="<?= (int) $member->id ?>">Remove</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="org-chart-card" style="grid-column:span 5">
        <h3>Activity log</h3>
        <div style="max-height:400px;overflow-y:auto">
            <?php foreach ($activity as $log): ?>
                <div class="org-kanban-card">
                    <strong style="font-size:12px"><?= Html::encode($log->action) ?></strong>
                    <div style="font-size:11px;color:var(--org-text-3)"><?= Yii::$app->formatter->asRelativeTime($log->created_at) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="org-chart-card" style="grid-column:span 12">
        <h3>Permission Matrix</h3>
        <table class="org-data-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Analytics</th>
                    <th>Students</th>
                    <th>Interviews</th>
                    <th>Programs</th>
                    <th>Coordination</th>
                    <th>Reviews</th>
                    <th>Team</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roleOptions as $roleKey => $roleLabel): ?>
                    <?php $perms = OrgTeamMember::defaultPermissionsForRole($roleKey); ?>
                    <tr>
                        <td><strong><?= Html::encode($roleLabel) ?></strong></td>
                        <td><?= in_array('analytics', $perms, true) ? '✓' : '—' ?></td>
                        <td><?= in_array('students', $perms, true) ? '✓' : '—' ?></td>
                        <td><?= in_array('interviews', $perms, true) ? '✓' : '—' ?></td>
                        <td><?= in_array('programs', $perms, true) ? '✓' : '—' ?></td>
                        <td><?= in_array('coordination', $perms, true) ? '✓' : '—' ?></td>
                        <td><?= in_array('reviews', $perms, true) ? '✓' : '—' ?></td>
                        <td><?= in_array('team', $perms, true) ? '✓' : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="org-modal-backdrop" id="teamFormModal" data-org-modal="teamForm">
    <div class="org-modal">
        <h2>Invite team member</h2>
        <form class="org-form-grid" data-org-ajax-form="<?= Url::to(['invite']) ?>">
            <input type="hidden" name="<?= $csrf ?>" value="<?= $token ?>">
            <div><label>Name</label><input name="name" required></div>
            <div><label>Email</label><input type="email" name="email" required></div>
            <div><label>Role</label>
                <select name="role">
                    <?php foreach ($roleOptions as $k => $label): ?>
                        <option value="<?= Html::encode($k) ?>"><?= Html::encode($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="org-btn org-btn-primary">Send invite</button>
        </form>
    </div>
</div>

<?php
$roleUrl = Url::to(['update-role']);
$deleteUrl = Url::to(['delete']);
$this->registerJs(<<<JS
document.querySelectorAll('.org-team-role').forEach(function(sel){
  sel.addEventListener('change', function(){
    var fd = new FormData();
    fd.append('id', sel.getAttribute('data-member-id'));
    fd.append('role', sel.value);
    fd.append('{$csrf}', '{$token}');
    fetch('{$roleUrl}', {method:'POST', body:fd});
  });
});
document.querySelectorAll('[data-org-remove-member]').forEach(function(btn){
  btn.addEventListener('click', function(){
    if(!confirm('Remove this team member?')) return;
    var fd = new FormData();
    fd.append('id', btn.getAttribute('data-org-remove-member'));
    fd.append('{$csrf}', '{$token}');
    fetch('{$deleteUrl}', {method:'POST', body:fd}).then(r=>r.json()).then(function(res){ if(res.success) location.reload(); });
  });
});
JS
);
?>
