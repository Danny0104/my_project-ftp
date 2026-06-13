<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Login';
?>
<div class="auth-bg auth-bg--login" data-auth-page="login">
    <div class="auth-overlay"></div>
    <div class="auth-ambient auth-ambient--one" aria-hidden="true"></div>
    <div class="auth-ambient auth-ambient--two" aria-hidden="true"></div>
    <div class="auth-ambient auth-ambient--three" aria-hidden="true"></div>
    <div class="auth-transition-overlay" aria-hidden="true"></div>
    <div class="auth-form-container">
        <div class="auth-logo auth-transition-chrome">
            <img src="https://upload.wikimedia.org/wikipedia/commons/6/61/HTML5_logo_and_wordmark.svg" alt="Logo">
        </div>

        <div class="auth-unified auth-transition-panel" data-auth-state="login">
            <div class="auth-unified__inner">
                <section class="auth-form-pane auth-form-pane--login is-active" data-auth-pane="login">
                    <div class="auth-panel-heading">
                        <h1>Sign In</h1>
                        <p>Welcome back. Please enter your details.</p>
                    </div>
                    <div class="auth-card auth-card--compact">
                        <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
                            <?= $form->field($model, 'username', [
                                'inputOptions' => ['placeholder' => 'Username', 'class' => 'form-control'],
                            ])->label(false) ?>
                            <?= $form->field($model, 'password', [
                                'inputOptions' => ['placeholder' => 'Password', 'class' => 'form-control'],
                            ])->passwordInput()->label(false) ?>
                            <?= $form->field($model, 'rememberMe')->checkbox(['class' => 'form-check-input'])->label('Keep Logged In', ['class' => 'form-check-label']) ?>
                            <?= Html::submitButton('GET STARTED', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                            <div class="links">
                                <?= Html::a('Create Account', ['site/signup'], [
                                    'class' => 'auth-switch-link',
                                    'data-auth-target' => 'signup',
                                ]) ?>
                                <?= Html::a('Forgot Password?', ['site/request-password-reset']) ?>
                            </div>
                        <?php ActiveForm::end(); ?>
                    </div>
                </section>

                <section class="auth-form-pane auth-form-pane--signup" data-auth-pane="signup" aria-hidden="true">
                    <div class="auth-form-pane__placeholder" aria-hidden="true">
                        <div class="auth-skeleton-line"></div>
                        <div class="auth-skeleton-line"></div>
                        <div class="auth-skeleton-line auth-skeleton-line--short"></div>
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

        <div class="auth-copyright auth-transition-chrome">
            <small>
                About Us &nbsp; | &nbsp; Privacy Policy &nbsp; | &nbsp; Terms Of Use<br>
                &copy; <?= date('Y') ?> Key. All Rights Reserved | Design By danny
            </small>
        </div>
    </div>
</div>
