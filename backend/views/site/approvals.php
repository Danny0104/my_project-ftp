<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\User;
use common\models\Organization;

/** @var \common\models\Application[] $pendingApplications */
/** @var \common\models\User[] $pendingUsers */
/** @var \common\models\Organization[] $pendingOrganizations */

$this->title = 'Approval Center';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'approvals';

$appCount = count($pendingApplications);
$userCount = count($pendingUsers);
$orgPending = count($pendingOrganizations);
$csrf = Yii::$app->request->csrfParam;
$token = Yii::$app->request->getCsrfToken();
$approveOrgUrl = Url::to(['approve-organization']);
$rejectOrgUrl = Url::to(['reject-organization']);
$approveUserUrl = Url::to(['approve-user']);
$rejectUserUrl = Url::to(['reject-user']);
$approveAppUrl = Url::to(['approve-application']);
$rejectAppUrl = Url::to(['reject-application']);
$hasQueue = $appCount + $userCount + $orgPending > 0;
?>

<div class="ap-module ap-approval-queue">
    <?= $this->render('../layouts/_page_header', [
        'title' => 'Approval center',
        'subtitle' => 'Centralized workflow for applications, registrations, and escalations',
        'actions' => [
            Html::a('<i class="fas fa-file-lines"></i> All applications', ['application/index'], ['class' => 'ap-btn ap-btn-ghost']),
            Html::a('<i class="fas fa-users"></i> All users', ['user/index'], ['class' => 'ap-btn ap-btn-primary']),
        ],
    ]) ?>

    <?= $this->render('../layouts/partials/_kpi_grid', [
        'cards' => [
            ['label' => 'Application queue', 'value' => $appCount, 'icon' => 'fa-inbox', 'accent' => 'amber'],
            ['label' => 'User registrations', 'value' => $userCount, 'icon' => 'fa-user-clock', 'accent' => 'blue'],
            ['label' => 'Org pending', 'value' => $orgPending, 'icon' => 'fa-building', 'accent' => 'purple'],
            ['label' => 'Total pending', 'value' => $appCount + $userCount + $orgPending, 'icon' => 'fa-layer-group', 'accent' => 'red'],
        ],
    ]) ?>

    <div class="ap-dash-grid-2">
        <div class="ap-panel ap-glass ap-module-panel">
            <div class="ap-panel-head">
                <h3><i class="fas fa-inbox"></i> Pending applications</h3>
                <span class="ap-sla-badge">SLA · Review within 48h</span>
            </div>
            <?php if (empty($pendingApplications)): ?>
                <div class="ap-empty" style="padding:24px">
                    <i class="fas fa-circle-check"></i>
                    <p class="mb-0">No applications awaiting review.</p>
                </div>
            <?php else: ?>
                <div class="ap-widget-list">
                    <?php foreach ($pendingApplications as $app): ?>
                        <div class="ap-widget-item" data-app-row="<?= (int) $app->id ?>">
                            <div class="ap-widget-icon"><i class="fas fa-file"></i></div>
                            <div class="ap-widget-body">
                                <strong><?= Html::encode($app->student->user->username ?? 'Student') ?></strong>
                                <span><?= Html::encode($app->position->title ?? 'Position') ?> · <?= Html::encode(ucfirst(str_replace('_', ' ', $app->status))) ?></span>
                            </div>
                            <button type="button" class="ap-btn ap-btn-primary ap-btn-sm" data-app-approve="<?= (int) $app->id ?>">Advance</button>
                            <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" data-app-reject="<?= (int) $app->id ?>">Reject</button>
                            <?= Html::a('Review', ['application/view', 'id' => $app->id], ['class' => 'ap-btn ap-btn-ghost ap-btn-sm']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="ap-panel ap-glass ap-module-panel">
            <div class="ap-panel-head">
                <h3><i class="fas fa-user-clock"></i> Pending registrations</h3>
                <span class="ap-sla-badge">Priority · Identity</span>
            </div>
            <?php if (empty($pendingUsers)): ?>
                <div class="ap-empty" style="padding:24px">
                    <i class="fas fa-circle-check"></i>
                    <p class="mb-0">No users awaiting approval.</p>
                </div>
            <?php else: ?>
                <div class="ap-widget-list">
                    <?php foreach ($pendingUsers as $user): ?>
                        <div class="ap-widget-item" data-user-row="<?= (int) $user->id ?>">
                            <div class="ap-widget-icon"><i class="fas fa-user"></i></div>
                            <div class="ap-widget-body">
                                <strong><?= Html::encode($user->username) ?></strong>
                                <span><?= Html::encode($user->email) ?> · <?= Html::encode(ucfirst($user->role)) ?></span>
                            </div>
                            <button type="button" class="ap-btn ap-btn-primary ap-btn-sm" data-user-approve="<?= (int) $user->id ?>">Approve</button>
                            <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" data-user-reject="<?= (int) $user->id ?>">Reject</button>
                            <?= Html::a('Review', ['user/view', 'id' => $user->id], ['class' => 'ap-btn ap-btn-ghost ap-btn-sm']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="ap-panel ap-glass ap-module-panel mt-4">
        <div class="ap-panel-head">
            <h3><i class="fas fa-building"></i> Organization verification</h3>
            <span class="ap-sla-badge">Verify before onboarding</span>
        </div>
        <?php if (empty($pendingOrganizations)): ?>
            <div class="ap-empty" style="padding:24px">
                <i class="fas fa-circle-check"></i>
                <p class="mb-0">No organizations awaiting verification.</p>
            </div>
        <?php else: ?>
            <div class="ap-widget-list">
                <?php foreach ($pendingOrganizations as $org): ?>
                    <div class="ap-widget-item" data-org-verify-row="<?= (int) $org->id ?>">
                        <div class="ap-widget-icon"><i class="fas fa-building"></i></div>
                        <div class="ap-widget-body">
                            <strong><?= Html::encode($org->name) ?></strong>
                            <span><?= Html::encode($org->user->email ?? '') ?> · <?= Html::encode($org->location ?: 'Location not set') ?></span>
                        </div>
                        <button type="button" class="ap-btn ap-btn-primary ap-btn-sm" data-org-approve="<?= (int) $org->id ?>">Approve</button>
                        <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" data-org-reject="<?= (int) $org->id ?>">Reject</button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasQueue): ?>
<?php
$this->registerJs(<<<JS
(function(){
  var csrf = '{$csrf}', token = '{$token}';
  function post(url, data) {
    var fd = new FormData();
    Object.keys(data).forEach(function(k){ fd.append(k, data[k]); });
    fd.append(csrf, token);
    return fetch(url, {method:'POST', body:fd, credentials:'same-origin'}).then(function(r){ return r.json(); });
  }
  function bind(selector, url, extra) {
    document.querySelectorAll(selector).forEach(function(btn){
      btn.addEventListener('click', function(){
        if (extra && extra.prompt) {
          var val = window.prompt(extra.prompt);
          if (val === null) return;
          extra.reason = val;
        }
        btn.disabled = true;
        var data = {id: btn.getAttribute(extra.attr)};
        if (extra && extra.reason !== undefined) data.reason = extra.reason;
        post(url, data).then(function(res){
          if(res.success) location.reload();
          else { btn.disabled = false; alert(res.message || 'Action failed'); }
        }).catch(function(){ btn.disabled = false; });
      });
    });
  }
  bind('[data-org-approve]', '{$approveOrgUrl}', {attr:'data-org-approve'});
  bind('[data-org-reject]', '{$rejectOrgUrl}', {attr:'data-org-reject', prompt:'Optional rejection reason:'});
  bind('[data-user-approve]', '{$approveUserUrl}', {attr:'data-user-approve'});
  bind('[data-user-reject]', '{$rejectUserUrl}', {attr:'data-user-reject', prompt:'Optional rejection reason:'});
  bind('[data-app-approve]', '{$approveAppUrl}', {attr:'data-app-approve'});
  bind('[data-app-reject]', '{$rejectAppUrl}', {attr:'data-app-reject', prompt:'Optional rejection reason:'});
})();
JS
);
?>
<?php endif; ?>
