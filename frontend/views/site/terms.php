<?php
/** @var yii\web\View $this */

use frontend\assets\PublicPagesAsset;
use yii\helpers\Html;

$this->title = 'Terms of Service';
PublicPagesAsset::register($this);
?>

<div class="pp-page">
    <section class="pp-hero pp-hero--compact">
        <div class="pp-hero__inner">
            <h1>Terms of Service</h1>
            <p class="text-muted mb-0">Last updated: <?= date('F j, Y') ?></p>
        </div>
    </section>
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="h5">1. Acceptance</h2>
                <p>By creating an account or using the Field Training Management Platform, you agree to these terms and our Privacy Policy.</p>

                <h2 class="h5 mt-4">2. Accounts</h2>
                <p>You are responsible for safeguarding your credentials and for activity under your account. Organizations must provide accurate company information; students must provide accurate academic details.</p>

                <h2 class="h5 mt-4">3. Platform use</h2>
                <p>The platform connects students, organizations, and administrators for internship applications, interviews, and messaging. Misuse, harassment, or fraudulent listings may result in suspension.</p>

                <h2 class="h5 mt-4">4. Content</h2>
                <p>You retain ownership of content you upload (CVs, messages, organization profiles). You grant the platform a license to display and process that content solely to operate the service.</p>

                <h2 class="h5 mt-4">5. Contact</h2>
                <p>Questions about these terms: <?= Html::mailto(Yii::$app->params['supportEmail'] ?? 'support@example.com') ?>.</p>

                <p class="mt-4"><?= Html::a('Back to signup', ['site/signup'], ['class' => 'btn btn-outline-primary']) ?></p>
            </div>
        </div>
    </section>
</div>
