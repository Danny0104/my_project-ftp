<?php

use yii\helpers\Html;

$this->title = 'System Settings';
$this->params['breadcrumbs'][] = $this->title;
$this->params['apNavActive'] = 'settings';
?>

<div class="ap-module">
    <?= $this->render('../layouts/_page_header', [
        'title' => 'Configuration center',
        'subtitle' => 'Platform, security, communications, and maintenance controls',
    ]) ?>

    <div class="ap-settings-layout">
        <nav class="ap-settings-nav ap-glass" aria-label="Settings sections">
            <a href="#general" class="is-active">General</a>
            <a href="#security">Security</a>
            <a href="#email">Email & notifications</a>
            <a href="#integrations">Integrations</a>
            <a href="#appearance">Appearance</a>
        </nav>

        <div class="ap-settings-content">
            <section id="general" class="ap-panel ap-glass ap-module-panel mb-4">
                <h3 style="margin:0 0 12px;font-size:1rem"><i class="fas fa-sliders me-2"></i>General platform</h3>
                <p class="text-muted">Training period dates, application limits, and profile requirements are managed under Regulations.</p>
                <?= Html::a('Open regulations', ['site/regulations'], ['class' => 'ap-btn ap-btn-primary ap-btn-sm']) ?>
                <?= Html::a('Faculties & fields', ['site/faculties'], ['class' => 'ap-btn ap-btn-ghost ap-btn-sm']) ?>
            </section>

            <section id="security" class="ap-panel ap-glass ap-module-panel mb-4">
                <h3 style="margin:0 0 12px;font-size:1rem"><i class="fas fa-shield-halved me-2"></i>Security</h3>
                <p class="text-muted">Review audit logs, eligibility enforcement, and admin access.</p>
                <div class="d-flex flex-wrap gap-2">
                    <?= Html::a('Audit logs', ['site/audit-logs'], ['class' => 'ap-btn ap-btn-ghost ap-btn-sm']) ?>
                    <?= Html::a('Manage admins', ['admin/index'], ['class' => 'ap-btn ap-btn-ghost ap-btn-sm']) ?>
                </div>
                <div class="ap-health-pill mt-3"><span class="ap-health-dot"></span> Security health: Good</div>
            </section>

            <section id="email" class="ap-panel ap-glass ap-module-panel mb-4">
                <h3 style="margin:0 0 12px;font-size:1rem"><i class="fas fa-envelope me-2"></i>Email & notifications</h3>
                <p class="text-muted">Broadcast announcements and manage the notification center.</p>
                <div class="d-flex flex-wrap gap-2">
                    <?= Html::a('Send announcement', ['send-announcement'], ['class' => 'ap-btn ap-btn-primary ap-btn-sm']) ?>
                    <?= Html::a('Notifications', ['notification/index'], ['class' => 'ap-btn ap-btn-ghost ap-btn-sm']) ?>
                </div>
            </section>

            <section id="integrations" class="ap-panel ap-glass ap-module-panel mb-4">
                <h3 style="margin:0 0 12px;font-size:1rem"><i class="fas fa-plug me-2"></i>API & integrations</h3>
                <p class="text-muted mb-0">External API keys and webhooks can be configured here when enabled for your deployment.</p>
            </section>

            <section id="appearance" class="ap-panel ap-glass ap-module-panel">
                <h3 style="margin:0 0 12px;font-size:1rem"><i class="fas fa-palette me-2"></i>Theme</h3>
                <p class="text-muted">Choose your preferred admin appearance. This is saved to your account and browser.</p>
                <?php
                $admin = Yii::$app->user->identity;
                $savedTheme = 'light';
                if ($admin && !empty($admin->preferences)) {
                    $prefs = json_decode($admin->preferences, true);
                    if (is_array($prefs) && !empty($prefs['theme'])) {
                        $savedTheme = $prefs['theme'];
                    }
                }
                ?>
                <form class="d-flex flex-wrap gap-2 align-items-end" method="post" action="<?= \yii\helpers\Url::to(['site/save-theme-preference']) ?>" data-ap-theme-form>
                    <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                    <div>
                        <label class="form-label">Theme</label>
                        <select class="form-select" name="theme">
                            <option value="light"<?= $savedTheme === 'light' ? ' selected' : '' ?>>Light</option>
                            <option value="dark"<?= $savedTheme === 'dark' ? ' selected' : '' ?>>Dark</option>
                            <option value="system"<?= $savedTheme === 'system' ? ' selected' : '' ?>>System</option>
                        </select>
                    </div>
                    <button type="submit" class="ap-btn ap-btn-primary ap-btn-sm">Update theme</button>
                    <button type="button" class="ap-btn ap-btn-ghost ap-btn-sm" id="apSettingsThemeToggle">Toggle now</button>
                </form>
            </section>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<'JS'
document.querySelectorAll('.ap-settings-nav a').forEach(function (link) {
  link.addEventListener('click', function (e) {
    document.querySelectorAll('.ap-settings-nav a').forEach(function (a) { a.classList.remove('is-active'); });
    link.classList.add('is-active');
  });
});
document.getElementById('apSettingsThemeToggle')?.addEventListener('click', function () {
  document.getElementById('apThemeToggle')?.click();
});
JS
);
?>
