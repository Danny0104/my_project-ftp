<?php

use yii\helpers\Html;
use yii\helpers\Url;

/** @var \yii\web\View $this */

$isGuest = Yii::$app->user->isGuest;
$dashboardUrl = $isGuest
    ? ['/site/login']
    : ((Yii::$app->user->identity->role ?? '') === 'student'
        ? ['/dashboard/student']
        : ['/dashboard']);

$faqUrl = Url::to(['/site/contact']) . '#pp-faq';
?>

<footer class="site-footer" role="contentinfo">
    <div class="site-footer__main">
        <div class="container site-footer__grid">
            <div class="site-footer__brand">
                <a class="site-footer__logo" href="<?= Url::to(['/site/index']) ?>">
                    <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                    <span>Field Training Platform</span>
                </a>
                <p class="site-footer__tagline">Connecting students with real-world field training opportunities.</p>
                <div class="site-footer__social" aria-label="Social media">
                    <a href="#" class="site-footer__social-link" title="Facebook" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                    <a href="#" class="site-footer__social-link" title="Twitter" aria-label="Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                    <a href="#" class="site-footer__social-link" title="LinkedIn" aria-label="LinkedIn"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                    <a href="#" class="site-footer__social-link" title="Instagram" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                    <a href="#" class="site-footer__social-link" title="YouTube" aria-label="YouTube"><i class="fab fa-youtube" aria-hidden="true"></i></a>
                </div>
            </div>

            <nav class="site-footer__col" aria-label="Quick links">
                <h6 class="site-footer__heading">Quick Links</h6>
                <ul class="site-footer__links">
                    <li><a href="<?= Url::to(['/site/index']) ?>">Home</a></li>
                    <li><a href="<?= Url::to(['/position/index']) ?>">Positions</a></li>
                    <li><a href="<?= Url::to(['/site/about']) ?>">About</a></li>
                    <li><a href="<?= Url::to(['/site/contact']) ?>">Contact</a></li>
                </ul>
            </nav>

            <nav class="site-footer__col" aria-label="Student resources">
                <h6 class="site-footer__heading">Students</h6>
                <ul class="site-footer__links">
                    <li><a href="<?= Url::to(['/site/signup']) ?>">Create Account</a></li>
                    <li><a href="<?= Url::to(['/position/index']) ?>">Browse Positions</a></li>
                    <li><a href="<?= Url::to($dashboardUrl) ?>">Dashboard</a></li>
                    <li><a href="<?= Html::encode($faqUrl) ?>">FAQ</a></li>
                </ul>
            </nav>

            <nav class="site-footer__col" aria-label="Organization resources">
                <h6 class="site-footer__heading">Organizations</h6>
                <ul class="site-footer__links">
                    <li><a href="<?= Url::to(['/site/contact']) ?>">Partner With Us</a></li>
                    <li><a href="<?= Url::to(['/site/signup']) ?>">Post Positions</a></li>
                    <li><a href="<?= Url::to($isGuest ? ['/site/login'] : $dashboardUrl) ?>">Manage Applications</a></li>
                    <li><a href="<?= Url::to(['/site/contact']) ?>">Support</a></li>
                </ul>
            </nav>
        </div>

        <div class="container">
            <ul class="site-footer__contact">
                <li>
                    <a href="tel:+255783863821">
                        <i class="fas fa-phone" aria-hidden="true"></i>
                        <span>+255 783 863 821</span>
                    </a>
                </li>
                <li>
                    <a href="mailto:info@fieldtraining.com">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <span>info@fieldtraining.com</span>
                    </a>
                </li>
                <li>
                    <span class="site-footer__contact-static">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <span>Dar es Salaam, Tanzania</span>
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <div class="site-footer__legal">
        <div class="container site-footer__legal-inner">
            <nav class="site-footer__legal-nav" aria-label="Legal">
                <a href="#">Privacy Policy</a>
                <span class="site-footer__sep" aria-hidden="true">|</span>
                <a href="#">Terms of Service</a>
                <span class="site-footer__sep" aria-hidden="true">|</span>
                <a href="#">Cookie Policy</a>
            </nav>
        </div>
    </div>

    <div class="site-footer__bar">
        <div class="container">
            <p class="site-footer__copy">&copy; <?= date('Y') ?> Field Training Platform. All rights reserved.</p>
        </div>
    </div>
</footer>

<button type="button" class="site-footer__back-top" id="backToTop" aria-label="Back to top">
    <i class="fas fa-chevron-up" aria-hidden="true"></i>
</button>
