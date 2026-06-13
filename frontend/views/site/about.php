<?php

/** @var yii\web\View $this */

use frontend\assets\PublicPagesAsset;
use yii\helpers\Html;
use yii\helpers\Url;

PublicPagesAsset::register($this);

$this->title = 'About Us';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="pp-page">
    <section class="pp-hero">
        <div class="pp-hero__bg" style="background-image: url('https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=2000&q=80');" role="presentation"></div>
        <div class="pp-hero__overlay" aria-hidden="true"></div>
        <div class="pp-hero__mesh" aria-hidden="true"></div>
        <span class="pp-hero__float pp-hero__float--1" aria-hidden="true"></span>
        <span class="pp-hero__float pp-hero__float--2" aria-hidden="true"></span>

        <div class="pp-hero__inner">
            <div class="pp-hero__grid">
                <div>
                    <p class="pp-hero__eyebrow"><i class="fas fa-graduation-cap" aria-hidden="true"></i> About Field Training Platform</p>
                    <h1 class="pp-hero__title">Empowering Students Through Real-World Experience</h1>
                    <p class="pp-hero__lead">
                        Connecting ambitious students with leading organizations to create meaningful practical training experiences that prepare them for successful careers.
                    </p>
                    <div class="pp-hero__cta">
                        <a href="<?= Url::to(['/position/index']) ?>" class="pp-btn pp-btn--primary">
                            <i class="fas fa-briefcase" aria-hidden="true"></i> Browse Positions
                        </a>
                        <a href="<?= Url::to(['/site/contact']) ?>" class="pp-btn pp-btn--ghost">
                            <i class="fas fa-handshake" aria-hidden="true"></i> Partner With Us
                        </a>
                    </div>
                </div>

                <aside class="pp-hero__glass">
                    <div class="pp-hero__glass-icon" aria-hidden="true"><i class="fas fa-graduation-cap"></i></div>
                    <h3>Career growth starts here</h3>
                    <div class="pp-hero__glass-metrics">
                        <div class="pp-hero__glass-metric">
                            <i class="fas fa-chart-line" aria-hidden="true"></i>
                            <span>95% placement success rate</span>
                        </div>
                        <div class="pp-hero__glass-metric">
                            <i class="fas fa-building" aria-hidden="true"></i>
                            <span>100+ verified partner organizations</span>
                        </div>
                        <div class="pp-hero__glass-metric">
                            <i class="fas fa-users" aria-hidden="true"></i>
                            <span>End-to-end student support</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <section class="pp-stats" aria-label="Platform impact">
        <div class="pp-stats__grid">
            <div class="pp-stat pp-reveal">
                <span class="pp-stat__value" data-count="500" data-suffix="+">0</span>
                <span class="pp-stat__label">Students Placed</span>
            </div>
            <div class="pp-stat pp-reveal pp-reveal--delay-1">
                <span class="pp-stat__value" data-count="95" data-suffix="%">0</span>
                <span class="pp-stat__label">Success Rate</span>
            </div>
            <div class="pp-stat pp-reveal pp-reveal--delay-2">
                <span class="pp-stat__value" data-count="100" data-suffix="+">0</span>
                <span class="pp-stat__label">Partner Organizations</span>
            </div>
            <div class="pp-stat pp-reveal pp-reveal--delay-3">
                <span class="pp-stat__value" data-count="1000" data-suffix="+">0</span>
                <span class="pp-stat__label">Applications Processed</span>
            </div>
        </div>
    </section>

    <section class="pp-section">
        <div class="pp-section__inner">
            <div class="pp-split pp-reveal">
                <div class="pp-split__text">
                    <h2>Building bridges between education and industry</h2>
                    <p>Founded in 2020, Field Training Platform emerged from a vision to bridge the gap between academic learning and real-world professional experience. We connect students with leading organizations across Tanzania and beyond.</p>
                    <p>Today we're proud to facilitate thousands of successful placements — helping students launch careers with confidence, competence, and meaningful industry exposure.</p>
                </div>
                <div class="pp-split__media">
                    <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80" alt="Students collaborating in a professional setting" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <section class="pp-section pp-section--alt">
        <div class="pp-section__inner">
            <header class="pp-section__head pp-reveal">
                <h2>Our Mission, Vision &amp; Values</h2>
                <p>The principles that guide everything we do</p>
            </header>
            <div class="pp-cards">
                <article class="pp-card pp-card--glass pp-reveal">
                    <div class="pp-card__icon" aria-hidden="true"><i class="fas fa-bullseye"></i></div>
                    <h3>Our Mission</h3>
                    <p>To empower students with practical field training experiences that bridge academic learning and professional excellence, building a skilled workforce that drives innovation and growth.</p>
                </article>
                <article class="pp-card pp-card--glass pp-reveal pp-reveal--delay-1">
                    <div class="pp-card__icon" aria-hidden="true"><i class="fas fa-eye"></i></div>
                    <h3>Our Vision</h3>
                    <p>To become East Africa's premier platform for field training, where every student has access to meaningful professional experiences in their chosen field.</p>
                </article>
                <article class="pp-card pp-card--glass pp-reveal pp-reveal--delay-2">
                    <div class="pp-card__icon" aria-hidden="true"><i class="fas fa-heart"></i></div>
                    <h3>Our Values</h3>
                    <p>Excellence, integrity, collaboration, and empathy — we measure success by the positive impact we create for students, partners, and communities.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="pp-section">
        <div class="pp-section__inner">
            <header class="pp-section__head pp-reveal">
                <h2>Why Choose Us</h2>
                <p>A premium experience designed for students and organizations alike</p>
            </header>
            <div class="pp-features">
                <div class="pp-feature pp-reveal">
                    <i class="fas fa-shield-halved" aria-hidden="true"></i>
                    <h4>Verified Placements</h4>
                    <p>Every opportunity is reviewed so students connect with trusted, legitimate organizations.</p>
                </div>
                <div class="pp-feature pp-reveal pp-reveal--delay-1">
                    <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>
                    <h4>Smart Matching</h4>
                    <p>Filter by field, location, and duration to find roles aligned with your career goals.</p>
                </div>
                <div class="pp-feature pp-reveal pp-reveal--delay-2">
                    <i class="fas fa-headset" aria-hidden="true"></i>
                    <h4>Dedicated Support</h4>
                    <p>Our team guides you from application through completion of your training placement.</p>
                </div>
                <div class="pp-feature pp-reveal">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    <h4>Career Outcomes</h4>
                    <p>Gain real skills and references that strengthen your resume and job prospects.</p>
                </div>
                <div class="pp-feature pp-reveal pp-reveal--delay-1">
                    <i class="fas fa-mobile-screen" aria-hidden="true"></i>
                    <h4>Modern Platform</h4>
                    <p>Apply, track progress, and communicate — all in one intuitive digital workspace.</p>
                </div>
                <div class="pp-feature pp-reveal pp-reveal--delay-2">
                    <i class="fas fa-globe-africa" aria-hidden="true"></i>
                    <h4>Regional Reach</h4>
                    <p>Partnerships across Tanzania and East Africa with growing industry coverage.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="pp-section pp-section--alt">
        <div class="pp-section__inner">
            <header class="pp-section__head pp-reveal">
                <h2>Meet Our Team</h2>
                <p>The passionate professionals behind our success</p>
            </header>
            <div class="pp-team">
                <article class="pp-team-member pp-reveal">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&q=80" alt="Miss. Elizabeth Milaho" loading="lazy">
                    <h4> Elizabeth Milaho</h4>
                    <p class="role">Chief Executive Officer</p>
                    <p>15+ years in education and workforce development, leading our mission to transform student–industry connections.</p>
                </article>
                <article class="pp-team-member pp-reveal pp-reveal--delay-1">
                    <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=200&q=80" alt="Samson Mabula" loading="lazy">
                    <h4>Samson Mabula</h4>
                    <p class="role">Chief Technology Officer</p>
                    <p>Oversees platform development for seamless experiences across students and partner organizations.</p>
                </article>
                <article class="pp-team-member pp-reveal pp-reveal--delay-2">
                    <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?auto=format&fit=crop&w=200&q=80" alt="Daniel Barnabas" loading="lazy">
                    <h4>Baniel Barnabas</h4>
                    <p class="role">Head of Partnerships</p>
                    <p>Builds relationships with leading organizations to expand quality training opportunities.</p>
                </article>
                <article class="pp-team-member pp-reveal pp-reveal--delay-3">
                    <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=200&q=80" alt="Rashid Kalyoma" loading="lazy">
                    <h4>Rashid Kalyoma</h4>
                    <p class="role">Student Success Manager</p>
                    <p>Ensures every student receives support from application through training completion.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="pp-section">
        <div class="pp-section__inner">
            <header class="pp-section__head pp-reveal">
                <h2>Success Stories</h2>
                <p>What our students and partners say about us</p>
            </header>
            <div class="pp-testimonials">
                <blockquote class="pp-testimonial pp-reveal">
                    <p>Field Training Platform transformed my career prospects. I gained hands-on experience at a leading tech company and secured a full-time role before graduation.</p>
                    <footer class="pp-testimonial__author">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=96&q=80" alt="" loading="lazy">
                        <div>
                            <strong>John Mwamba</strong>
                            <span>Computer Science Graduate</span>
                        </div>
                    </footer>
                </blockquote>
                <blockquote class="pp-testimonial pp-reveal pp-reveal--delay-1">
                    <p>As an organization, we've found exceptional talent through the platform. Students are well-prepared, motivated, and bring fresh perspectives to our team.</p>
                    <footer class="pp-testimonial__author">
                        <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=96&q=80" alt="" loading="lazy">
                        <div>
                            <strong>Dr. Amina Hassan</strong>
                            <span>HR Director, Tech Solutions Ltd</span>
                        </div>
                    </footer>
                </blockquote>
                <blockquote class="pp-testimonial pp-reveal pp-reveal--delay-2">
                    <p>The platform made it easy to find relevant opportunities. Support was always available whenever I had questions about my application.</p>
                    <footer class="pp-testimonial__author">
                        <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?auto=format&fit=crop&w=96&q=80" alt="" loading="lazy">
                        <div>
                            <strong>Mary Kimani</strong>
                            <span>Business Administration Graduate</span>
                        </div>
                    </footer>
                </blockquote>
            </div>
        </div>
    </section>

    <section class="pp-cta-band pp-reveal">
        <h3>Ready to start your training journey?</h3>
        <div class="pp-cta-band__actions">
            <a href="<?= Url::to(['/position/index']) ?>" class="pp-btn pp-btn--ghost">Browse Positions</a>
            <a href="<?= Url::to(['/site/signup']) ?>" class="pp-btn pp-btn--primary">Create Free Account</a>
        </div>
    </section>
</div>
