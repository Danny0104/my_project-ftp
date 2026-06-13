<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\ResetPasswordForm $model */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Reset password';
?>
<style>
.reset-bg {
    background: url('https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=crop&w=1500&q=80') no-repeat center center fixed;
    background-size: cover;
    min-height: 100vh;
    position: relative;
}
.reset-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 1;
}
.reset-form-container {
    position: relative;
    z-index: 2;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}
.reset-card {
    background: rgba(255,255,255,0.08);
    border-radius: 20px;
    padding: 40px 30px 30px 30px;
    box-shadow: 0 8px 32px 0 rgba(31,38,135,0.37);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    color: #fff;
    width: 350px;
    max-width: 90vw;
}
.reset-card .form-control {
    background: rgba(255,255,255,0.15);
    border: none;
    color: #fff;
}
.reset-card .form-control:focus {
    background: rgba(255,255,255,0.25);
    color: #fff;
}
.reset-card .btn-primary {
    background: #ff5e62;
    border: none;
    border-radius: 25px;
    font-weight: bold;
    width: 100%;
    margin-top: 10px;
}
.reset-card .btn-primary:hover {
    background: #ff3c41;
}
.reset-card .form-check-label, .reset-card a {
    color: #fff;
}
.reset-card .links {
    display: flex;
    justify-content: space-between;
    font-size: 0.95em;
    margin-top: 10px;
}
.reset-logo {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
}
.reset-logo img {
    width: 48px;
    height: 48px;
}
</style>
<div class="reset-bg">
    <div class="reset-overlay"></div>
    <div class="reset-form-container">
        <div class="reset-logo">
            <img src="https://upload.wikimedia.org/wikipedia/commons/6/61/HTML5_logo_and_wordmark.svg" alt="Logo">
        </div>
        <div class="reset-card">
            <h1 class="h5 mb-2">Choose a new password</h1>
            <p class="small mb-3" style="opacity:0.9">Enter a strong password for your account.</p>
            <?php $form = ActiveForm::begin(['id' => 'reset-password-form']); ?>
                <?= $form->field($model, 'password', [
                    'inputOptions' => ['placeholder' => 'New Password', 'class' => 'form-control'],
                ])->passwordInput()->label(false) ?>
                <?= Html::submitButton('SAVE', ['class' => 'btn btn-primary']) ?>
                <div class="links">
                    <?= Html::a('Login', ['site/login']) ?>
                    <?= Html::a('Need Help?', ['site/contact']) ?>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
        <div class="mt-4 text-center" style="color:#fff; opacity:0.8;">
            <small>
                About Us &nbsp; | &nbsp; Privacy Policy &nbsp; | &nbsp; Terms Of Use<br>
                &copy; <?= date('Y') ?> Key. All Rights Reserved | Design By danny
            </small>
        </div>
    </div>
</div>
