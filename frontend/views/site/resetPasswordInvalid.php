<?php

/** @var yii\web\View $this */

use yii\bootstrap5\Html;

$this->title = 'Reset link expired';
?>
<div class="reset-bg">
    <div class="reset-overlay"></div>
    <div class="reset-form-container">
        <div class="reset-logo">
            <img src="https://upload.wikimedia.org/wikipedia/commons/6/61/HTML5_logo_and_wordmark.svg" alt="Logo">
        </div>
        <div class="reset-card">
            <h1 class="h5 mb-3">This reset link is no longer valid</h1>
            <p class="small mb-4" style="opacity:0.9">
                Password reset links expire after a limited time and can only be used once.
                Request a new link to choose a new password.
            </p>
            <?= Html::a('Request a new reset link', ['site/request-password-reset'], [
                'class' => 'btn btn-primary w-100',
            ]) ?>
            <div class="links mt-3">
                <?= Html::a('Back to login', ['site/login']) ?>
                <?= Html::a('Need help?', ['site/contact']) ?>
            </div>
        </div>
    </div>
</div>
<style>
.reset-bg {
    background: url('https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=crop&w=1500&q=80') no-repeat center center fixed;
    background-size: cover;
    min-height: 100vh;
    position: relative;
}
.reset-overlay {
    position: absolute;
    inset: 0;
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
    padding: 1rem;
}
.reset-card {
    background: rgba(255,255,255,0.08);
    border-radius: 20px;
    padding: 40px 30px;
    box-shadow: 0 8px 32px 0 rgba(31,38,135,0.37);
    backdrop-filter: blur(6px);
    color: #fff;
    width: min(420px, 100%);
}
.reset-card .btn-primary {
    background: #ff5e62;
    border: none;
    border-radius: 25px;
    font-weight: bold;
}
.reset-card .links {
    display: flex;
    justify-content: space-between;
    font-size: 0.95em;
}
.reset-card a { color: #fff; }
.reset-logo img { width: 48px; height: 48px; margin-bottom: 20px; }
</style>
