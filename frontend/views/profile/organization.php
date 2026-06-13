<?php
use common\widgets\ProfileAvatar;
use frontend\assets\OrganizationModulesAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

OrganizationModulesAsset::register($this);

$this->title = 'Settings & Security';
$profileStrength = 0;
$checks = [
    !empty($model->name),
    !empty($model->description),
    !empty($model->location),
    !empty($model->website),
    !empty($model->logo),
];
$profileStrength = (int) round(100 * count(array_filter($checks)) / count($checks));

$this->registerJs(<<<'JS'
document.getElementById('orgLogoInput')?.addEventListener('change', function () {
    var file = this.files && this.files[0];
    if (!file) return;
    var preview = document.getElementById('orgLogoPreview');
    if (!preview) return;
    var reader = new FileReader();
    reader.onload = function (e) {
        preview.innerHTML = '<span class="ft-avatar ft-avatar--xl ft-avatar--org ft-avatar--has-image" style="width:200px;height:200px;"><img class="ft-avatar__img" src="' + e.target.result + '" alt="Logo preview" width="200" height="200"></span>';
    };
    reader.readAsDataURL(file);
});
JS
);
?>

<?= $this->render('@frontend/views/organization/_page_header', [
    'title' => 'Settings & Security',
    'subtitle' => 'Manage organization profile, security posture, preferences, and integrations.',
    'actions' => [
        Html::a('<i class="fas fa-building me-1"></i> Company Profile', ['/profile/view-organization'], ['class' => 'org-btn org-btn-ghost']),
    ],
]) ?>

<div class="org-kpi-grid">
    <div class="org-kpi-card">
        <div class="kpi-label">Profile strength</div>
        <div class="kpi-value" data-org-counter="<?= $profileStrength ?>">0</div>
        <div class="kpi-trend">Target: 100%</div>
    </div>
    <div class="org-kpi-card">
        <div class="kpi-label">Security score</div>
        <div class="kpi-value" data-org-counter="78">0</div>
        <div class="kpi-trend">Good · enable 2FA to improve</div>
    </div>
    <div class="org-kpi-card">
        <div class="kpi-label">Last login</div>
        <div class="kpi-value" style="font-size:1.05rem"><?= Yii::$app->formatter->asRelativeTime(time()) ?></div>
        <div class="kpi-trend">Current session active</div>
    </div>
</div>

<div class="org-view-tabs" data-org-tabs>
    <a href="#" class="is-active" data-tab-target="general">General</a>
    <a href="#" data-tab-target="profile">Organization Profile</a>
    <a href="#" data-tab-target="security">Security</a>
    <a href="#" data-tab-target="notifications">Notifications</a>
    <a href="#" data-tab-target="appearance">Appearance</a>
    <a href="#" data-tab-target="integrations">Integrations</a>
    <a href="#" data-tab-target="api">API Access</a>
    <a href="#" data-tab-target="audit">Audit Logs</a>
</div>

<?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="alert alert-success"><?= Yii::$app->session->getFlash('success') ?></div>
<?php endif; ?>

<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data', 'data-org-settings-form' => '1']]); ?>
<div class="org-chart-grid">
    <section class="org-chart-card org-tab-pane is-active" data-tab-pane="general" style="grid-column:span 8">
        <h3>General Settings</h3>
        <div class="org-form-grid">
            <div><?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?></div>
            <div><?= $form->field($model, 'location')->textInput(['maxlength' => true]) ?></div>
            <div><?= $form->field($model, 'website')->textInput(['maxlength' => true, 'placeholder' => 'https://example.com']) ?></div>
            <div><?= $form->field($model, 'description')->textarea(['rows' => 5]) ?></div>
        </div>
    </section>

    <aside class="org-chart-card org-tab-pane is-active" data-tab-pane="general" style="grid-column:span 4">
        <h3>Save State</h3>
        <div class="org-kanban-card">
            <strong>Auto-save feel</strong>
            <p class="mb-0" style="color:var(--org-text-2)">Changes are tracked in real-time. Click save when ready to publish.</p>
        </div>
        <div class="org-kanban-card">
            <strong>Unsaved changes warning</strong>
            <p class="mb-0" style="color:var(--org-text-2)">You will be prompted before leaving with unsaved updates.</p>
        </div>
    </aside>

    <section class="org-chart-card org-tab-pane" data-tab-pane="profile" style="grid-column:span 12">
        <h3>Organization Profile & Branding</h3>
        <div class="org-form-grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
            <div>
                <label class="form-label">Organization logo</label>
                <div class="mb-2" id="orgLogoPreview">
                    <?= ProfileAvatar::widget(['type' => 'organization', 'organization' => $model, 'size' => 'xl', 'lazy' => false]) ?>
                </div>
                <input type="file" name="logo" id="orgLogoInput" class="form-control" accept="image/jpeg,image/png,image/webp">
                <?php if ($model->hasLogo()): ?>
                    <?= Html::a('Remove logo', ['remove-logo'], [
                        'class' => 'btn btn-sm btn-outline-danger mt-2',
                        'data' => ['method' => 'post', 'confirm' => 'Remove organization logo?'],
                    ]) ?>
                <?php endif; ?>
                <small class="text-muted d-block mt-1">JPG, JPEG, PNG, or WEBP · max 5 MB · square recommended</small>
            </div>
            <div>
                <label class="form-label text-muted">Banner upload</label>
                <input type="file" class="form-control" accept="image/*" disabled>
                <small class="text-muted">Coming in a future release</small>
            </div>
            <div>
                <label>LinkedIn URL</label>
                <input type="url" name="meta[linkedin]" placeholder="https://linkedin.com/company/...">
            </div>
            <div>
                <label>Internship categories</label>
                <input type="text" name="meta[categories]" placeholder="Engineering, Finance, Operations">
            </div>
        </div>
    </section>

    <section class="org-chart-card org-tab-pane" data-tab-pane="security" style="grid-column:span 8">
        <h3>Security Center</h3>
        <div class="org-form-grid">
            <div>
                <label>Change password</label>
                <input type="password" id="orgPasswordInput" name="meta[new_password]" placeholder="Enter new password">
                <small id="orgPasswordStrength" style="color:var(--org-text-2)">Strength: —</small>
            </div>
            <div>
                <label>Two-factor authentication</label>
                <select name="meta[two_factor]">
                    <option value="disabled">Disabled</option>
                    <option value="app">Authenticator App</option>
                    <option value="sms">SMS</option>
                </select>
            </div>
            <div>
                <label>Active sessions policy</label>
                <select name="meta[sessions]">
                    <option value="all">Allow multiple devices</option>
                    <option value="single">Single active session only</option>
                </select>
            </div>
        </div>
    </section>
    <aside class="org-chart-card org-tab-pane" data-tab-pane="security" style="grid-column:span 4">
        <h3>Security Health</h3>
        <div class="org-kanban-card"><strong>Last login</strong><div><?= Yii::$app->formatter->asDatetime(time()) ?></div></div>
        <div class="org-kanban-card"><strong>Failed attempts (7d)</strong><div>0</div></div>
        <div class="org-kanban-card"><strong>Recent activity</strong><div>Password unchanged · 21 days</div></div>
    </aside>

    <section class="org-chart-card org-tab-pane" data-tab-pane="notifications" style="grid-column:span 12">
        <h3>Notification Preferences</h3>
        <div class="org-form-grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
            <label><input type="checkbox" name="meta[notif_applications]" checked> Application alerts</label>
            <label><input type="checkbox" name="meta[notif_interviews]" checked> Interview reminders</label>
            <label><input type="checkbox" name="meta[notif_reports]" checked> Weekly reports</label>
            <label><input type="checkbox" name="meta[notif_security]" checked> Security alerts</label>
        </div>
    </section>

    <section class="org-chart-card org-tab-pane" data-tab-pane="appearance" style="grid-column:span 12">
        <h3>Appearance</h3>
        <div class="org-form-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
            <div><label>Theme</label><select name="meta[theme]"><option>System</option><option>Dark</option><option>Light</option></select></div>
            <div><label>Density</label><select name="meta[density]"><option>Comfortable</option><option>Compact</option></select></div>
            <div><label>Animations</label><select name="meta[motion]"><option>Enabled</option><option>Reduced</option></select></div>
        </div>
    </section>

    <section class="org-chart-card org-tab-pane" data-tab-pane="integrations" style="grid-column:span 12">
        <h3>Integrations</h3>
        <div class="org-kanban-row">
            <div class="org-kanban-card"><strong>Google Calendar</strong><div style="color:var(--org-text-2)">Disconnected</div></div>
            <div class="org-kanban-card"><strong>Microsoft Teams</strong><div style="color:var(--org-text-2)">Disconnected</div></div>
            <div class="org-kanban-card"><strong>Slack</strong><div style="color:var(--org-text-2)">Disconnected</div></div>
        </div>
    </section>

    <section class="org-chart-card org-tab-pane" data-tab-pane="api" style="grid-column:span 12">
        <h3>API Access</h3>
        <div class="org-form-grid" style="grid-template-columns:2fr 1fr">
            <div><label>API Key</label><input type="text" value="sk_live_••••••••••••••••" readonly></div>
            <div><label>&nbsp;</label><button type="button" class="org-btn org-btn-ghost">Rotate key</button></div>
        </div>
    </section>

    <section class="org-chart-card org-tab-pane" data-tab-pane="audit" style="grid-column:span 12">
        <h3>Audit Logs</h3>
        <div class="org-kanban-card">Profile updated · <?= Yii::$app->formatter->asRelativeTime(time() - 3600) ?></div>
        <div class="org-kanban-card">Security settings viewed · <?= Yii::$app->formatter->asRelativeTime(time() - 7200) ?></div>
    </section>
</div>

<div class="org-sticky-savebar" id="orgStickySaveBar">
    <div><strong>Unsaved changes</strong> · review and apply updates</div>
    <div style="display:flex;gap:10px">
        <button type="button" class="org-btn org-btn-ghost" id="orgResetSettings">Discard</button>
        <?= Html::submitButton('Save changes', ['class' => 'org-btn org-btn-primary']) ?>
    </div>
    </div>
<?php ActiveForm::end(); ?> 