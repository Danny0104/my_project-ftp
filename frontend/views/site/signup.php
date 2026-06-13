<?php

/** @var yii\web\View $this */
/** @var string $step role|student|organization */
/** @var \frontend\models\StudentSignupForm $studentModel */
/** @var \frontend\models\OrganizationSignupForm $orgModel */

use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->title = 'Signup';
?>
<div class="auth-bg auth-bg--signup" data-auth-page="signup">
    <div class="auth-overlay"></div>
    <div class="auth-ambient auth-ambient--one" aria-hidden="true"></div>
    <div class="auth-ambient auth-ambient--two" aria-hidden="true"></div>
    <div class="auth-ambient auth-ambient--three" aria-hidden="true"></div>
    <div class="auth-transition-overlay" aria-hidden="true"></div>
    <div class="auth-form-container">
        <div class="auth-logo auth-transition-chrome">
            <img src="https://upload.wikimedia.org/wikipedia/commons/6/61/HTML5_logo_and_wordmark.svg" alt="Logo">
        </div>
        <div class="auth-unified auth-transition-panel auth-unified--register" data-auth-state="signup">
            <div class="auth-unified__inner">
                <section class="auth-form-pane auth-form-pane--login" data-auth-pane="login" aria-hidden="true">
                    <div class="auth-form-pane__placeholder" aria-hidden="true">
                        <div class="auth-skeleton-line"></div>
                        <div class="auth-skeleton-line"></div>
                        <div class="auth-skeleton-line auth-skeleton-line--short"></div>
                    </div>
                </section>

                <section class="auth-form-pane auth-form-pane--signup is-active" data-auth-pane="signup">
                    <div class="auth-register-body">
                        <div class="auth-card auth-card--wide auth-card--register">
                            <?php if ($step === 'student'): ?>
                                <?= $this->render('signup/_student_form', ['model' => $studentModel]) ?>
                            <?php elseif ($step === 'organization'): ?>
                                <?= $this->render('signup/_organization_form', ['model' => $orgModel]) ?>
                            <?php else: ?>
                                <?= $this->render('signup/_role_select') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <aside class="auth-overlay-panel">
                    <div class="auth-overlay-panel__content auth-overlay-panel__content--login">
                        <p class="auth-kicker">New here?</p>
                        <h2>Hello Friend</h2>
                        <p>Create your account and unlock your personalized dashboard experience.</p>
                        <?= Html::a('Sign Up', ['site/signup'], [
                            'class' => 'btn auth-ghost-btn auth-switch-link',
                            'data-auth-target' => 'signup',
                        ]) ?>
                    </div>
                    <div class="auth-overlay-panel__content auth-overlay-panel__content--signup">
                        <p class="auth-kicker">Already registered?</p>
                        <h2>Welcome Back</h2>
                        <p>Sign in to continue managing your opportunities and account activities.</p>
                        <?= Html::a('Sign In', ['site/login'], [
                            'class' => 'btn auth-ghost-btn auth-switch-link',
                            'data-auth-target' => 'login',
                        ]) ?>
                    </div>
                </aside>
            </div>
        </div>

        <div class="auth-bottom-links auth-transition-chrome">
            <?= Html::a('About Us', ['site/about']) ?>
            <?= Html::a('Privacy Policy', ['site/privacy']) ?>
            <?= Html::a('Terms of Service', ['site/terms']) ?>
            <?= Html::a('Contact Support', ['site/contact']) ?>
        </div>

        <div class="auth-copyright auth-transition-chrome">
            <small>&copy; <?= date('Y') ?> Key. All Rights Reserved | Designed with ❤️ by danny</small>
        </div>
    </div>
</div>
