<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\ContactForm $model */

use frontend\assets\PublicPagesAsset;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->title = 'Contact Us';

$userRole = !Yii::$app->user->isGuest && Yii::$app->user->identity
    ? (string) Yii::$app->user->identity->role
    : '';

if (in_array($userRole, ['student', 'organization'], true)) {
    $this->title = 'Help Center';
    echo $this->render('_help_center', ['userRole' => $userRole]);
    return;
}

PublicPagesAsset::register($this);

$this->params['breadcrumbs'][] = $this->title;

$floatFieldTemplate = "{input}\n{label}\n{error}\n{hint}";
?>

<div class="pp-page">
    <section class="pp-hero">
        <div class="pp-hero__bg" style="background-image: url('https://images.unsplash.com/photo-1423666639041-f56000c27a9a?auto=format&fit=crop&w=2000&q=80');" role="presentation"></div>
        <div class="pp-hero__overlay" aria-hidden="true"></div>
        <div class="pp-hero__mesh" aria-hidden="true"></div>
        <span class="pp-hero__float pp-hero__float--1" aria-hidden="true"></span>
        <span class="pp-hero__float pp-hero__float--2" aria-hidden="true"></span>

        <div class="pp-hero__inner">
            <div class="pp-hero__grid">
                <div>
                    <p class="pp-hero__eyebrow"><i class="fas fa-headset" aria-hidden="true"></i> Contact &amp; Support</p>
                    <h1 class="pp-hero__title">We're Here to Help</h1>
                    <p class="pp-hero__lead">
                        Whether you need support, have questions about internships, or want to partner with us, our team is ready to assist.
                    </p>
                    <div class="pp-hero__cta">
                        <a href="#contact-form" class="pp-btn pp-btn--primary">
                            <i class="fas fa-envelope" aria-hidden="true"></i> Contact Support
                        </a>
                        <a href="<?= Url::to(['/position/index']) ?>" class="pp-btn pp-btn--ghost">
                            <i class="fas fa-briefcase" aria-hidden="true"></i> Explore Opportunities
                        </a>
                    </div>
                    <div class="pp-badges">
                        <span class="pp-badge"><i class="fas fa-bolt" aria-hidden="true"></i> Fast Response</span>
                        <span class="pp-badge"><i class="fas fa-user-graduate" aria-hidden="true"></i> Student Support</span>
                        <span class="pp-badge"><i class="fas fa-compass" aria-hidden="true"></i> Career Guidance</span>
                        <span class="pp-badge"><i class="fas fa-handshake" aria-hidden="true"></i> Organization Partnerships</span>
                    </div>
                </div>

                <aside class="pp-hero__glass">
                    <div class="pp-hero__glass-icon" aria-hidden="true"><i class="fas fa-comments"></i></div>
                    <h3>We're online to help</h3>
                    <div class="pp-hero__glass-metrics">
                        <div class="pp-hero__glass-metric">
                            <i class="fas fa-clock" aria-hidden="true"></i>
                            <span>Response within 24 hours</span>
                        </div>
                        <div class="pp-hero__glass-metric">
                            <i class="fas fa-phone" aria-hidden="true"></i>
                            <span>+255 783 863 821</span>
                        </div>
                        <div class="pp-hero__glass-metric">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                            <span>support@fieldtraining.com</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <section class="pp-section pp-section--alt">
        <div class="pp-section__inner">
            <header class="pp-section__head pp-reveal">
                <h2>Contact Information</h2>
                <p>Multiple ways to reach our team</p>
            </header>
            <div class="pp-contact-grid">
                <article class="pp-contact-card pp-reveal">
                    <i class="fas fa-envelope" aria-hidden="true"></i>
                    <h4>Email</h4>
                    <p><strong>General:</strong></p>
                    <p><a href="mailto:info@fieldtraining.com">info@fieldtraining.com</a></p>
                    <p><strong>Support:</strong></p>
                    <p><a href="mailto:support@fieldtraining.com">support@fieldtraining.com</a></p>
                </article>
                <article class="pp-contact-card pp-reveal pp-reveal--delay-1">
                    <i class="fas fa-phone" aria-hidden="true"></i>
                    <h4>Phone</h4>
                    <p><a href="tel:+255783863821">+255 783 863 821</a></p>
                    <p><a href="tel:+255222345678">+255 222 345 678</a></p>
                </article>
                <article class="pp-contact-card pp-reveal pp-reveal--delay-2">
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    <h4>Address</h4>
                    <p>123 University Avenue<br>Dar es Salaam, Tanzania<br>P.O. Box 12345</p>
                    <p><a href="https://maps.google.com/?q=Dar+es+Salaam+Tanzania" target="_blank" rel="noopener">Get directions</a></p>
                </article>
                <article class="pp-contact-card pp-reveal pp-reveal--delay-3">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    <h4>Support Hours</h4>
                    <p><strong>Mon – Fri:</strong> 8:00 AM – 6:00 PM</p>
                    <p><strong>Saturday:</strong> 9:00 AM – 2:00 PM</p>
                    <p><strong>Sunday:</strong> Closed</p>
                </article>
            </div>
        </div>
    </section>

    <section class="pp-section" id="contact-form-section">
        <div class="pp-section__inner">
            <header class="pp-section__head pp-reveal">
                <h2>Send Us a Message</h2>
                <p>Fill out the form below and we'll get back to you within 24 hours</p>
            </header>

            <div class="pp-form-shell pp-reveal">
                <?php $form = ActiveForm::begin([
                    'id' => 'contact-form',
                    'options' => ['class' => 'needs-validation', 'novalidate' => true],
                    'fieldConfig' => [
                        'template' => $floatFieldTemplate,
                        'labelOptions' => ['class' => 'form-label'],
                        'inputOptions' => ['class' => 'form-control', 'placeholder' => ' '],
                        'options' => ['class' => 'pp-float-field'],
                    ],
                ]); ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <?= $form->field($model, 'name')->textInput(['required' => true])->label('Full Name') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'email')->input('email', ['required' => true])->label('Email Address') ?>
                    </div>
                </div>

                <?= $form->field($model, 'subject')->textInput(['required' => true])->label('Subject') ?>

                <?= $form->field($model, 'body', ['options' => ['class' => 'pp-float-field pp-float-field--area']])
                    ->textarea(['rows' => 6, 'required' => true])
                    ->label('Message') ?>

                <div class="text-center mt-4">
                    <?= Html::submitButton('<i class="fas fa-paper-plane me-2" aria-hidden="true"></i>Send Message', [
                        'class' => 'pp-btn pp-btn--primary',
                        'name' => 'contact-button',
                        'encode' => false,
                    ]) ?>
                </div>

                <?php ActiveForm::end(); ?>

                <div class="d-flex justify-content-center gap-3 mt-4 flex-wrap">
                    <a href="#" class="text-secondary" title="Facebook" aria-label="Facebook"><i class="fab fa-facebook-f fa-lg"></i></a>
                    <a href="#" class="text-secondary" title="Twitter" aria-label="Twitter"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="text-secondary" title="LinkedIn" aria-label="LinkedIn"><i class="fab fa-linkedin-in fa-lg"></i></a>
                    <a href="#" class="text-secondary" title="Instagram" aria-label="Instagram"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="https://wa.me/255683863821" class="text-secondary" title="WhatsApp" aria-label="WhatsApp" target="_blank" rel="noopener"><i class="fab fa-whatsapp fa-lg"></i></a>
                </div>
            </div>
        </div>
    </section>

    <section class="pp-section pp-section--alt">
        <div class="pp-section__inner">
            <header class="pp-section__head pp-reveal">
                <h2>Find Us</h2>
                <p>Visit our office in Dar es Salaam</p>
            </header>
            <div class="pp-map pp-reveal">
                <iframe
                    title="Field Training Platform office location"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3969.404176964702!2d39.20820631476678!3d-6.792841995028692!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x185c4b0d28f38c8b%3A0x8c4c4c4c4c4c4c4c!2sDar%20es%20Salaam%2C%20Tanzania!5e0!3m2!1sen!2sus!4v1234567890123!5m2!1sen!2sus"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>

    <section class="pp-section">
        <div class="pp-section__inner">
            <header class="pp-section__head pp-reveal">
                <h2>Frequently Asked Questions</h2>
                <p>Quick answers to common questions</p>
            </header>
            <div class="pp-faq pp-reveal" id="pp-faq">
                <div class="pp-faq-item" data-pp-faq-item>
                    <button type="button" data-pp-faq-toggle aria-expanded="false">
                        How do I apply for field training positions?
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="pp-faq-item__answer">
                        <div class="pp-faq-item__answer-inner">
                            Create a student account, complete your profile with your field of study and documents, then browse available positions and submit your application through our platform.
                        </div>
                    </div>
                </div>
                <div class="pp-faq-item" data-pp-faq-item>
                    <button type="button" data-pp-faq-toggle aria-expanded="false">
                        What documents do I need to upload?
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="pp-faq-item__answer">
                        <div class="pp-faq-item__answer-inner">
                            You'll need your CV, academic transcripts, and relevant certificates. Additional requirements may be listed on each position description.
                        </div>
                    </div>
                </div>
                <div class="pp-faq-item" data-pp-faq-item>
                    <button type="button" data-pp-faq-toggle aria-expanded="false">
                        How long does the application process take?
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="pp-faq-item__answer">
                        <div class="pp-faq-item__answer-inner">
                            Reviews typically take 1–2 weeks. You'll receive notifications about your application status through your dashboard and email.
                        </div>
                    </div>
                </div>
                <div class="pp-faq-item" data-pp-faq-item>
                    <button type="button" data-pp-faq-toggle aria-expanded="false">
                        Can I apply to multiple positions?
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="pp-faq-item__answer">
                        <div class="pp-faq-item__answer-inner">
                            Yes, you can apply to multiple positions that match your field of study. Each position can only be applied to once.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pp-cta-band pp-reveal">
        <h3>Need immediate assistance?</h3>
        <div class="pp-cta-band__actions">
            <a href="tel:+255783863821" class="pp-btn pp-btn--ghost"><i class="fas fa-phone" aria-hidden="true"></i> Call Now</a>
            <a href="mailto:support@fieldtraining.com" class="pp-btn pp-btn--ghost"><i class="fas fa-envelope" aria-hidden="true"></i> Email Support</a>
            <a href="https://wa.me/255683863821" class="pp-btn pp-btn--primary" target="_blank" rel="noopener"><i class="fab fa-whatsapp" aria-hidden="true"></i> WhatsApp</a>
        </div>
    </section>
</div>
