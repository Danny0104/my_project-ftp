<?php
/** @var yii\web\View $this */

use frontend\assets\PublicPagesAsset;
use yii\helpers\Html;

$this->title = 'Privacy Policy';
PublicPagesAsset::register($this);
?>

<div class="pp-page">
    <section class="pp-hero pp-hero--compact">
        <div class="pp-hero__inner">
            <h1>Privacy Policy</h1>
            <p class="text-muted mb-0">Last updated: <?= date('F j, Y') ?></p>
        </div>
    </section>
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="h5">Information we collect</h2>
                <p>We collect account details (name, email, role), profile data (academic records, organization information), application activity, messages, and technical logs needed to secure the service.</p>

                <h2 class="h5 mt-4">How we use it</h2>
                <p>Data is used to match students with opportunities, facilitate interviews and messaging, send notifications (in-app and email when enabled), and improve platform security.</p>

                <h2 class="h5 mt-4">Sharing</h2>
                <p>Application data is shared with relevant organizations and administrators as part of the recruitment workflow. We do not sell personal data to third parties.</p>

                <h2 class="h5 mt-4">Your choices</h2>
                <p>You may update profile information in account settings, archive conversations, and contact support to request account deletion subject to legal retention requirements.</p>

                <h2 class="h5 mt-4">Contact</h2>
                <p>Privacy inquiries: <?= Html::mailto(Yii::$app->params['supportEmail'] ?? 'support@example.com') ?>.</p>

                <p class="mt-4"><?= Html::a('Back to signup', ['site/signup'], ['class' => 'btn btn-outline-primary']) ?></p>
            </div>
        </div>
    </section>
</div>
