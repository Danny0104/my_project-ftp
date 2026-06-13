<?php
use common\widgets\Alert;
use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Login';
?>
<style>
.login-bg {
    background: url('https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=crop&w=1500&q=80') no-repeat center center fixed;
    background-size: cover;
    min-height: 100vh;
    position: relative;
}
.login-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.6);
    z-index: 1;
}
.login-form-container {
    position: relative;
    z-index: 2;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}
.login-card {
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
.login-card .form-control {
    background: rgba(255,255,255,0.15);
    border: none;
    color: #fff;
}
.login-card .form-control:focus {
    background: rgba(255,255,255,0.25);
    color: #fff;
}
.login-card .btn-primary, .login-card .btn-danger, .login-card .btn-success {
    background: #ff5e62;
    border: none;
    border-radius: 25px;
    font-weight: bold;
    width: 100%;
    margin-top: 10px;
}
.login-card .btn-primary:hover {
    background: #ff3c41;
}
.login-card .form-check-label, .login-card a {
    color: #fff;
}
.login-card .form-check-input {
    background: #fff;
}
.login-card .links {
    display: flex;
    justify-content: space-between;
    font-size: 0.95em;
    margin-top: 10px;
}
.login-logo {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
}
.login-logo img {
    width: 48px;
    height: 48px;
}
</style>
<div class="login-bg">
    <div class="login-overlay"></div>
    <div class="login-form-container">
        <div class="login-logo">
            <img src="https://upload.wikimedia.org/wikipedia/commons/6/61/HTML5_logo_and_wordmark.svg" alt="Logo">
        </div>
        <div class="login-card">
            <?= Alert::widget() ?>
            <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>
                <?= $form->field($model, 'username', [
                    'inputOptions' => ['placeholder' => 'Username', 'class' => 'form-control'],
                ])->label(false) ?>
                <?= $form->field($model, 'password', [
                    'inputOptions' => ['placeholder' => 'Password', 'class' => 'form-control'],
                ])->passwordInput()->label(false) ?>
                <?= $form->field($model, 'rememberMe')->checkbox([ 'class' => 'form-check-input' ])->label('Keep Logged In', ['class' => 'form-check-label']) ?>
                <?= Html::submitButton('GET STARTED', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
                <div class="links">
                    <?= Html::a('Need Help?', ['site/contact']) ?>
                </div>
            <?php ActiveForm::end(); ?>
            <?php if ($model->hasErrors()): ?>
                <div class="alert alert-danger mt-3">
                    <?php foreach ($model->getErrors() as $errors): ?>
                        <?php foreach ($errors as $error): ?>
                            <?= Html::encode($error) ?><br>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="mt-4 text-center" style="color:#fff; opacity:0.8;">
            <small>
                Admin Login &nbsp; | &nbsp; Privacy Policy &nbsp; | &nbsp; Terms Of Use<br>
                &copy; <?= date('Y') ?> Key. All Rights Reserved | Design By W3layouts
            </small>
        </div>
    </div>
</div>
