<?php

use frontend\assets\StudentSettingsAsset;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var \common\models\User $user */

StudentSettingsAsset::register($this);

$this->title = 'Settings';

$email = $user->email ?? '';
$userAgent = Html::encode($_SERVER['HTTP_USER_AGENT'] ?? 'Web browser');
$platformVersion = Yii::$app->version ?? '2.0';
?>

<div class="sp-set sp-set--prefs-only">
    <header class="sp-set-header">
        <div>
            <h1>Settings</h1>
            <p>Preferences, security, and system configuration</p>
        </div>
        <div class="sp-set-header-links">
            <?= Html::a('<i class="fas fa-shield-halved"></i> Verification', ['verification'], ['class' => 'sp-set-btn sp-set-btn--ghost']) ?>
            <?= Html::a('<i class="fas fa-user"></i> View Profile', ['view-student'], ['class' => 'sp-set-btn sp-set-btn--ghost']) ?>
            <?= Html::a('<i class="fas fa-pen"></i> Edit Profile', ['edit-profile'], ['class' => 'sp-set-btn sp-set-btn--ghost']) ?>
        </div>
    </header>

    <div class="sp-set-workspace">
        <div class="sp-set-nav-wrap">
            <button type="button" class="sp-set-mobile-toggle" id="spSetMobileNav" aria-expanded="false">
                <span id="spSetMobileNavLabel">Appearance</span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <nav class="sp-set-nav" id="spSetNav" aria-label="Settings">
                <div class="sp-set-nav-group">
                    <span class="sp-set-nav-label">Preferences</span>
                    <button type="button" class="sp-set-nav-btn is-active" data-panel="appearance"><i class="fas fa-palette"></i> Appearance</button>
                    <button type="button" class="sp-set-nav-btn" data-panel="notifications"><i class="fas fa-bell"></i> Notifications</button>
                    <button type="button" class="sp-set-nav-btn" data-panel="privacy"><i class="fas fa-eye"></i> Privacy</button>
                </div>
                <div class="sp-set-nav-group">
                    <span class="sp-set-nav-label">Security</span>
                    <?= Html::a('<i class="fas fa-shield-halved"></i> Verification &amp; Security', ['verification'], ['class' => 'sp-set-nav-btn sp-set-nav-btn--link']) ?>
                    <button type="button" class="sp-set-nav-btn" data-panel="security"><i class="fas fa-key"></i> Password</button>
                    <button type="button" class="sp-set-nav-btn" data-panel="devices"><i class="fas fa-laptop"></i> Sessions &amp; Devices</button>
                </div>
                <div class="sp-set-nav-group">
                    <span class="sp-set-nav-label">System</span>
                    <button type="button" class="sp-set-nav-btn" data-panel="accessibility"><i class="fas fa-universal-access"></i> Accessibility</button>
                    <button type="button" class="sp-set-nav-btn" data-panel="connected"><i class="fas fa-plug"></i> Connected Accounts</button>
                    <button type="button" class="sp-set-nav-btn" data-panel="advanced"><i class="fas fa-sliders"></i> Advanced</button>
                </div>
            </nav>
        </div>

        <div class="sp-set-main">
            <!-- Appearance -->
            <div class="sp-set-panel is-active" data-panel="appearance" id="appearance">
                <div class="sp-set-panel-head">
                    <h2><i class="fas fa-palette"></i> Appearance</h2>
                    <p>Theme and display preferences (saved in your browser)</p>
                </div>
                <label class="sp-set-field-label">Theme</label>
                <div class="sp-set-theme-grid">
                    <button type="button" class="sp-set-theme-card is-active" data-theme="light"><i class="fas fa-sun"></i> Light</button>
                    <button type="button" class="sp-set-theme-card" data-theme="dark"><i class="fas fa-moon"></i> Dark</button>
                    <button type="button" class="sp-set-theme-card" data-theme="system"><i class="fas fa-desktop"></i> System default</button>
                </div>
            </div>

            <!-- Notifications -->
            <div class="sp-set-panel" data-panel="notifications" id="notifications">
                <div class="sp-set-panel-head">
                    <h2><i class="fas fa-bell"></i> Notifications</h2>
                    <p>Choose what you want to be notified about</p>
                </div>
                <div class="sp-set-toggles" id="spSetNotifications">
                    <?php foreach ([
                        ['application_updates', 'Application updates', 'Status changes on your applications'],
                        ['interview_invitations', 'Interview invitations', 'Scheduled interview notifications'],
                        ['org_messages', 'Organization messages', 'Messages from organizations and admins'],
                        ['internship_recommendations', 'Internship recommendations', 'Roles matching your profile'],
                        ['email_notifications', 'Email notifications', 'Receive alerts by email'],
                        ['push_notifications', 'Push notifications', 'Browser push alerts when supported'],
                    ] as [$key, $title, $desc]): ?>
                        <label class="sp-set-toggle">
                            <span class="sp-set-toggle-info"><strong><?= Html::encode($title) ?></strong><span><?= Html::encode($desc) ?></span></span>
                            <span class="sp-set-switch">
                                <input type="checkbox" data-pref="notif_<?= Html::encode($key) ?>" checked>
                                <span class="sp-set-switch-ui"></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Privacy -->
            <div class="sp-set-panel" data-panel="privacy" id="privacy">
                <div class="sp-set-panel-head">
                    <h2><i class="fas fa-eye"></i> Privacy</h2>
                    <p>Control who can see your profile information</p>
                </div>
                <div class="sp-set-toggles" id="spSetPrivacy">
                    <?php foreach ([
                        ['profile_visibility', 'Profile visibility', 'Allow organizations to view your profile when you apply'],
                        ['show_email', 'Show email to organizations', 'Display your email on applications'],
                        ['show_phone', 'Show phone number to organizations', 'Display your phone on applications'],
                        ['profile_discovery', 'Allow profile discovery', 'Let organizations find your profile in search'],
                        ['data_sharing', 'Data sharing preferences', 'Share anonymized usage data to improve the platform'],
                    ] as [$key, $title, $desc]): ?>
                        <label class="sp-set-toggle">
                            <span class="sp-set-toggle-info"><strong><?= Html::encode($title) ?></strong><span><?= Html::encode($desc) ?></span></span>
                            <span class="sp-set-switch">
                                <input type="checkbox" data-pref="privacy_<?= Html::encode($key) ?>" <?= $key !== 'data_sharing' ? 'checked' : '' ?>>
                                <span class="sp-set-switch-ui"></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Password -->
            <div class="sp-set-panel" data-panel="security" id="security">
                <div class="sp-set-panel-head">
                    <h2><i class="fas fa-key"></i> Password</h2>
                    <p>Manage your account password</p>
                </div>
                <div class="sp-set-security-score">
                    <div>
                        <strong>Password</strong>
                        <div class="small text-muted">Use a strong, unique password</div>
                    </div>
                    <div class="sp-set-password-meter" aria-hidden="true">
                        <span></span><span></span><span></span><span></span>
                    </div>
                </div>
                <div class="sp-set-device-card">
                    <i class="fas fa-key"></i>
                    <div style="flex:1">
                        <strong>Change password</strong><br>
                        <span class="text-muted">Last changed: unknown</span>
                    </div>
                    <?= Html::a('Change password', ['site/request-password-reset'], ['class' => 'sp-set-btn sp-set-btn--ghost']) ?>
                </div>
                <p class="sp-set-insight mb-0"><i class="fas fa-shield-halved me-1"></i> Password reset links are sent to <?= Html::encode($email ?: 'your email') ?>.</p>
            </div>

            <!-- Sessions & Devices -->
            <div class="sp-set-panel" data-panel="devices" id="devices">
                <div class="sp-set-panel-head">
                    <h2><i class="fas fa-laptop"></i> Sessions &amp; Devices</h2>
                    <p>Manage where you're signed in</p>
                </div>
                <div class="sp-set-device-card is-current">
                    <i class="fas fa-desktop"></i>
                    <div style="flex:1">
                        <strong>This device · Current session</strong><br>
                        <span class="text-muted"><?= $userAgent ?></span><br>
                        <span class="text-muted small">Signed in · <?= date('M d, Y · H:i') ?></span>
                    </div>
                    <span class="sp-set-badge sp-set-badge--ok">Active</span>
                </div>
                <div class="sp-set-insight"><i class="fas fa-clock me-1"></i> Login history and multi-device management will appear here as sessions are tracked.</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?= Html::a('<i class="fas fa-right-from-bracket"></i> Logout other devices', ['site/logout'], [
                        'class' => 'sp-set-btn sp-set-btn--ghost',
                        'data' => ['method' => 'post', 'confirm' => 'Sign out of this session?'],
                    ]) ?>
                </div>
            </div>

            <!-- Accessibility -->
            <div class="sp-set-panel" data-panel="accessibility" id="accessibility">
                <div class="sp-set-panel-head">
                    <h2><i class="fas fa-universal-access"></i> Accessibility</h2>
                    <p>Adjust display and interaction preferences</p>
                </div>
                <div class="sp-set-field">
                    <label class="sp-set-field-label" for="spSetFontSize">Font size</label>
                    <select class="form-control sp-input" id="spSetFontSize">
                        <option value="default">Default</option>
                        <option value="large">Large</option>
                        <option value="xlarge">Extra large</option>
                    </select>
                </div>
                <div class="sp-set-toggles">
                    <label class="sp-set-toggle">
                        <span class="sp-set-toggle-info"><strong>High contrast mode</strong><span>Stronger text and border contrast</span></span>
                        <span class="sp-set-switch"><input type="checkbox" id="spSetHighContrast"><span class="sp-set-switch-ui"></span></span>
                    </label>
                    <label class="sp-set-toggle">
                        <span class="sp-set-toggle-info"><strong>Reduced motion</strong><span>Minimize animations and transitions</span></span>
                        <span class="sp-set-switch"><input type="checkbox" id="spSetReduceMotion"><span class="sp-set-switch-ui"></span></span>
                    </label>
                    <label class="sp-set-toggle">
                        <span class="sp-set-toggle-info"><strong>Keyboard navigation support</strong><span>Enhanced focus indicators for keyboard users</span></span>
                        <span class="sp-set-switch"><input type="checkbox" id="spSetKeyboardNav"><span class="sp-set-switch-ui"></span></span>
                    </label>
                </div>
            </div>

            <!-- Connected Accounts -->
            <div class="sp-set-panel" data-panel="connected" id="connected">
                <div class="sp-set-panel-head">
                    <h2><i class="fas fa-plug"></i> Connected Accounts</h2>
                    <p>Link external sign-in providers and services</p>
                </div>
                <?php foreach ([
                    ['fab fa-google', 'Google', 'Sign in with Google'],
                    ['fab fa-microsoft', 'Microsoft', 'Sign in with Microsoft'],
                    ['fab fa-linkedin', 'LinkedIn', 'Coming soon'],
                    ['fab fa-github', 'GitHub', 'Coming soon'],
                ] as [$icon, $name, $status]): ?>
                    <div class="sp-set-connect-row">
                        <span><i class="<?= $icon ?>"></i> <?= Html::encode($name) ?></span>
                        <span class="sp-set-badge sp-set-badge--muted"><?= Html::encode($status) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Advanced -->
            <div class="sp-set-panel" data-panel="advanced" id="advanced">
                <div class="sp-set-panel-head">
                    <h2><i class="fas fa-sliders"></i> Advanced</h2>
                    <p>Account data, diagnostics, and platform information</p>
                </div>
                <div class="sp-set-advanced-grid">
                    <div class="sp-set-advanced-item">
                        <strong>Account export</strong>
                        <p class="text-muted small mb-2">Download a copy of your profile and application data.</p>
                        <button type="button" class="sp-set-btn sp-set-btn--ghost" id="spSetExportBtn" disabled title="Contact support to request an export">Request export</button>
                    </div>
                    <div class="sp-set-advanced-item">
                        <strong>Download personal data</strong>
                        <p class="text-muted small mb-2">Get a portable copy of information stored about you.</p>
                        <?= Html::a('Contact support', ['site/contact'], ['class' => 'sp-set-btn sp-set-btn--ghost']) ?>
                    </div>
                    <div class="sp-set-advanced-item">
                        <strong>Clear cache &amp; preferences</strong>
                        <p class="text-muted small mb-2">Reset theme, notification, and display settings stored in this browser.</p>
                        <button type="button" class="sp-set-btn sp-set-btn--ghost" id="spSetClearCacheBtn">Clear local preferences</button>
                    </div>
                    <div class="sp-set-advanced-item">
                        <strong>Delete account</strong>
                        <p class="text-muted small mb-2">Permanently remove your account. Managed by your university administrator.</p>
                        <?= Html::a('Request deletion', ['site/contact'], ['class' => 'sp-set-btn sp-set-btn--ghost text-danger']) ?>
                    </div>
                </div>
                <div class="sp-set-diagnostics mt-3">
                    <h3 class="sp-set-diagnostics__title">Account diagnostics</h3>
                    <dl class="sp-set-diagnostics__list">
                        <div><dt>Account email</dt><dd><?= Html::encode($email ?: '—') ?></dd></div>
                        <div><dt>Platform</dt><dd>Field Training Platform</dd></div>
                        <div><dt>Application version</dt><dd>Yii <?= Html::encode($platformVersion) ?></dd></div>
                        <div><dt>Session</dt><dd>Active · <?= date('M d, Y H:i') ?></dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
