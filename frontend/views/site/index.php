<?php
use yii\helpers\Url;
use yii\helpers\Html;
use common\components\CacheHelper;
use frontend\assets\HomepageMotionAsset;

$this->title = 'Field Practical Training Platform';

// Get cached data
$cacheHelper = new CacheHelper();
$recentPositions = $cacheHelper->cacheQuery('homepage_positions', function() {
    return \common\models\Position::find()
        ->where(['status' => 'active'])
        ->with(['organization'])
        ->orderBy(['created_at' => SORT_DESC])
        ->limit(6)
        ->all();
}, 1800); // Cache for 30 minutes

$totalPositions = $cacheHelper->cacheQuery('total_active_positions', function() {
    return \common\models\Position::find()->where(['status' => 'active'])->count();
}, 3600); // Cache for 1 hour

$totalOrganizations = $cacheHelper->cacheQuery('total_organizations', function() {
    return \common\models\Organization::find()->count();
}, 3600); // Cache for 1 hour

$this->registerCssFile('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
$this->registerCssFile('https://unpkg.com/aos@2.3.4/dist/aos.css');
$this->registerCssFile('@web/css/premium-cards.css', ['depends' => [\frontend\assets\AppAsset::class]]);
$this->registerJsFile('https://unpkg.com/aos@2.3.4/dist/aos.js', ['position' => \yii\web\View::POS_END]);
HomepageMotionAsset::register($this);
?>
<style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --light-bg: #f8f9fa;
        }

        .home-page .hero-slide:nth-child(1) {
            background-image: url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=2071&q=80');
            animation-delay: 0s;
        }
        .home-page .hero-slide:nth-child(2) {
            background-image: url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            animation-delay: 4s;
        }
        .home-page .hero-slide:nth-child(3) {
            background-image: url('https://images.unsplash.com/photo-1552664730-d307ca884978?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            animation-delay: 8s;
        }
        .home-page .hero-slide:nth-child(4) {
            background-image: url('https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80');
            animation-delay: 12s;
        }
        .home-page .hero-slide:nth-child(5) {
            background-image: url('https://images.unsplash.com/photo-1521737604893-d14cc237f11d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2084&q=80');
            animation-delay: 16s;
        }

        .home-page .hero-slide {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            opacity: 0;
            animation: slideShow 20s infinite;
        }

        @keyframes slideShow {
            0% { opacity: 0; transform: scale(1.08); }
            8% { opacity: 1; transform: scale(1); }
            22% { opacity: 1; transform: scale(1); }
            30% { opacity: 0; transform: scale(1.08); }
            100% { opacity: 0; transform: scale(1.08); }
        }

        .home-page .hero-section:hover .hero-slide {
            animation-play-state: paused;
        }

        .home-page .position-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .home-page .position-company {
            color: var(--secondary-color);
            font-weight: 500;
            margin-bottom: 15px;
        }

        .home-page .position-description {
            color: #666;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

    </style>

<div class="home-page">

    <!-- Hero Section -->
    <section class="hero-section" data-hero-load>
        <!-- Sliding Background Images -->
        <div class="hero-slideshow">
            <div class="hero-slide"></div>
            <div class="hero-slide"></div>
            <div class="hero-slide"></div>
            <div class="hero-slide"></div>
            <div class="hero-slide"></div>
        </div>
        
        <div class="hero-gradient-mesh" aria-hidden="true"></div>
        <div class="hero-orb hero-orb--1" aria-hidden="true"></div>
        <div class="hero-orb hero-orb--2" aria-hidden="true"></div>
        <div class="hero-orb hero-orb--3" aria-hidden="true"></div>
        <span class="hero-float-icon hero-float-icon--1" aria-hidden="true"><i class="fas fa-briefcase"></i></span>
        <span class="hero-float-icon hero-float-icon--2" aria-hidden="true"><i class="fas fa-graduation-cap"></i></span>
        <span class="hero-float-icon hero-float-icon--3" aria-hidden="true"><i class="fas fa-building"></i></span>

        <div class="hero-overlay"></div>

        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 data-split-headline>Shape Your Future Through Field Training</h1>
                        <p class="hero-subline">Connect with leading organizations and gain real-world experience through our comprehensive field practical training platform.</p>
                        <div class="hero-cta-row">
                            <?php if (Yii::$app->user->isGuest): ?>
                                <a href="<?= Url::to(['/site/signup']) ?>" class="btn btn-hero btn-hero--primary" data-magnetic>
                                    <i class="fas fa-user-plus me-2"></i>Get Started
                                </a>
                                <a href="<?= Url::to(['/position/index']) ?>" class="btn btn-hero btn-hero--ghost" data-magnetic>
                                    <i class="fas fa-search me-2"></i>Browse Positions
                                </a>
                            <?php else: ?>
                                <a href="<?= Url::to(['/dashboard']) ?>" class="btn btn-hero btn-hero--primary" data-magnetic>
                                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                                </a>
                                <a href="<?= Url::to(['/position/index']) ?>" class="btn btn-hero btn-hero--ghost" data-magnetic>
                                    <i class="fas fa-briefcase me-2"></i>View Positions
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="hero-trust">
                            <span class="hero-trust__item"><i class="fas fa-shield-alt"></i> Verified partners</span>
                            <span class="hero-trust__item"><i class="fas fa-users"></i> 500+ students placed</span>
                            <span class="hero-trust__item"><i class="fas fa-chart-line"></i> 95% success rate</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-visual" data-parallax-visual>
                        <div class="hero-visual__glass">
                            <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section" data-reveal="fade-up">
        <div class="container">
            <div class="row text-center hm-spotlight-grid">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card" data-reveal="scale-in" data-reveal-delay="0">
                        <div class="stat-number" data-count="<?= (int) $totalPositions ?>">0</div>
                        <h4>Available Positions</h4>
                        <p>Active training opportunities</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card" data-reveal="scale-in" data-reveal-delay="80">
                        <div class="stat-number" data-count="<?= (int) $totalOrganizations ?>">0</div>
                        <h4>Partner Organizations</h4>
                        <p>Leading companies and institutions</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card" data-reveal="scale-in" data-reveal-delay="160">
                        <div class="stat-number" data-count="500" data-count-suffix="+">0</div>
                        <h4>Students Placed</h4>
                        <p>Successful placements</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card" data-reveal="scale-in" data-reveal-delay="240">
                        <div class="stat-number" data-count="95" data-count-suffix="%">0</div>
                        <h4>Success Rate</h4>
                        <p>Student satisfaction</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="features-section__bg" aria-hidden="true">
            <div class="features-blob features-blob--1"></div>
            <div class="features-blob features-blob--2"></div>
            <div class="features-blob features-blob--3"></div>
            <div class="features-section__grid"></div>
            <div class="features-section__overlay"></div>
            <div class="features-particle features-particle--1"></div>
            <div class="features-particle features-particle--2"></div>
            <div class="features-particle features-particle--3"></div>
            <div class="features-particle features-particle--4"></div>
            <div class="features-particle features-particle--5"></div>
        </div>
        <div class="container features-section__inner">
            <div class="row text-center mb-5 features-section__header">
                <div class="col-lg-8 mx-auto" data-aos="fade-up" data-aos-duration="700">
                    <span class="features-eyebrow">Platform capabilities</span>
                    <h2 class="mb-4 features-section__title">Why Choose Our Platform?</h2>
                    <p class="lead features-section__lead">We provide a comprehensive solution for field practical training that benefits students, organizations, and administrators.</p>
                </div>
            </div>
            <div class="row g-4 features-cards-row">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="600" data-aos-delay="0">
                    <div class="feature-card">
                        <div class="feature-card__glow" aria-hidden="true"></div>
                        <div class="feature-icon-wrap">
                            <div class="feature-icon">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                        <h4>Easy Discovery</h4>
                        <p>Find the perfect training position with our advanced search and filtering system.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="600" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-card__glow" aria-hidden="true"></div>
                        <div class="feature-icon-wrap">
                            <div class="feature-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                        <h4>Simple Application</h4>
                        <p>Apply to multiple positions with just a few clicks and track your application status.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="600" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-card__glow" aria-hidden="true"></div>
                        <div class="feature-icon-wrap">
                            <div class="feature-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                        </div>
                        <h4>Real-time Notifications</h4>
                        <p>Stay updated with instant notifications about your applications and opportunities.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="600" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-card__glow" aria-hidden="true"></div>
                        <div class="feature-icon-wrap">
                            <div class="feature-icon">
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                        <h4>Organization Management</h4>
                        <p>Organizations can easily post positions and manage applications efficiently.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="600" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-card__glow" aria-hidden="true"></div>
                        <div class="feature-icon-wrap">
                            <div class="feature-icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                        </div>
                        <h4>Analytics & Reports</h4>
                        <p>Comprehensive analytics and reporting tools for administrators and organizations.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-duration="600" data-aos-delay="300">
                    <div class="feature-card">
                        <div class="feature-card__glow" aria-hidden="true"></div>
                        <div class="feature-icon-wrap">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                        </div>
                        <h4>Secure & Reliable</h4>
                        <p>Your data is protected with enterprise-grade security and reliability.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Positions Section -->
    <section class="positions-section">
        <div class="container">
            <div class="row text-center mb-5 section-header" data-reveal="fade-up">
                <div class="col-lg-8 mx-auto">
                    <h2 class="mb-4">Latest Training Positions</h2>
                    <p class="lead">Discover exciting field practical training opportunities from leading organizations.</p>
                </div>
            </div>
            <div class="row hm-spotlight-grid">
                <?php if (!empty($recentPositions)): ?>
                    <?php foreach ($recentPositions as $i => $position): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="position-card" data-tilt data-reveal="fade-up" data-reveal-delay="<?= (int) ($i * 80) ?>">
                                <div class="position-title"><?= Html::encode($position->title) ?></div>
                                <div class="position-company">
                                    <i class="fas fa-building me-2"></i><?= Html::encode($position->organization->name ?? 'Unknown Organization') ?>
                                </div>
                                <div class="position-description">
                                    <?= Html::encode(substr($position->description, 0, 150)) ?>...
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary"><?= Html::encode(ucfirst($position->status ?? 'Active')) ?></span>
                                    <a href="<?= Url::to(['/position/view', 'id' => $position->id]) ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No positions available at the moment. Check back later!
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="row mt-4" data-reveal="fade-up">
                <div class="col-12 text-center">
                    <a href="<?= Url::to(['/position/index']) ?>" class="btn btn-primary btn-lg" data-magnetic>
                        <i class="fas fa-list me-2"></i>View All Positions
                    </a>
                </div>
            </div>
        </div>
    </section>

</div><!-- /.home-page -->

