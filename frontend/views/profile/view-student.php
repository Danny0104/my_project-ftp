<?php

use common\models\Student;
use common\services\ProfileCompletionService;
use common\services\StudentCvService;
use common\services\StudentIdDocumentService;
use common\widgets\ProfileAvatar;
use frontend\assets\StudentProfileAsset;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var Student $model */

require_once __DIR__ . '/../dashboard/_student_helpers.php';

StudentProfileAsset::register($this);

$this->title = 'My Profile';

$user = $model->user;
$firstName = trim((string) ($user->first_name ?? ''));
$lastName = trim((string) ($user->last_name ?? ''));
$fullName = trim($firstName . ' ' . $lastName);
if ($fullName === '') {
    $fullName = $user->username ?? 'Student';
}

$phone = trim((string) ($user->phone ?? ''));
$email = trim((string) ($user->email ?? ''));

$profileCompletionService = new ProfileCompletionService();
$completionItems = $profileCompletionService->dashboardTasks($model);
$profilePercent = $profileCompletionService->dashboardPercent($model);
$missingItems = array_values(array_filter($completionItems, static fn($item) => !$item['done']));

$idDocService = new StudentIdDocumentService();
$cvService = new StudentCvService();
$hasIdDocument = $model->hasIdDocument();
$hasCv = $cvService->hasCvFile($model);
$idVerificationStatus = $model->id_verification_status ?? Student::ID_VERIFICATION_NONE;

$skillsList = array_values(array_filter(array_map('trim', explode(',', (string) ($model->skills ?? '')))));

$academicLevels = Student::getAcademicLevelOptions();
$academicLevelLabel = null;
if (!empty($model->academic_level)) {
    $academicLevelLabel = $academicLevels[$model->academic_level] ?? $model->academic_level;
}

$workModes = Student::getWorkModeOptions();
$workModeLabel = $workModes[$model->preferred_work_mode ?? ''] ?? ($model->preferred_work_mode ?: null);

$bio = trim((string) ($model->personal_statement ?? ''));
$bioLong = mb_strlen($bio) > 380;

$locationParts = array_filter([
    trim((string) ($model->preferred_locations ?? '')),
]);
$locationDisplay = $locationParts !== [] ? implode(' · ', $locationParts) : null;

if ($model->isIdVerified()) {
    $verificationLabel = 'Verified';
    $verificationClass = 'verified';
} elseif ($idVerificationStatus === Student::ID_VERIFICATION_REJECTED) {
    $verificationLabel = 'Rejected';
    $verificationClass = 'rejected';
} elseif ($hasIdDocument || $idVerificationStatus === Student::ID_VERIFICATION_PENDING) {
    $verificationLabel = 'Pending Review';
    $verificationClass = 'pending';
} else {
    $verificationLabel = 'Not Submitted';
    $verificationClass = 'none';
}

$idAbsolutePath = $idDocService->resolveAbsolutePath($model);
$idIsImage = $idAbsolutePath && $idDocService->isImage($idAbsolutePath);
$idPreviewUrl = $hasIdDocument
    ? Url::to(['profile/view-id-document', 'v' => (int) $model->id_uploaded_at])
    : null;

$socialLinks = array_filter([
    'LinkedIn' => ['url' => trim((string) ($model->linkedin_url ?? '')), 'icon' => 'fab fa-linkedin', 'class' => 'linkedin'],
    'GitHub' => ['url' => trim((string) ($model->github_url ?? '')), 'icon' => 'fab fa-github', 'class' => 'github'],
    'Portfolio' => ['url' => trim((string) ($model->portfolio_url ?? '')), 'icon' => 'fas fa-globe', 'class' => 'portfolio'],
], static fn($item) => $item['url'] !== '');

$academicRows = [
    'University' => $model->university,
    'Registration Number' => $model->student_id,
    'Program' => $model->program,
    'Department' => $model->department,
    'Faculty / School' => $model->faculty,
    'Field of Study' => $model->field_of_study,
    'Education Level' => $academicLevelLabel,
    'Graduation Year' => $model->graduation_year,
    'GPA' => $model->gpa !== null && $model->gpa !== '' ? $model->gpa : null,
];
$hasAcademicData = (bool) array_filter($academicRows, static fn($v) => $v !== null && $v !== '');

$internshipRows = [
    'Preferred Industry' => $model->preferred_industry,
    'Work Mode' => $workModeLabel,
    'Preferred Locations' => $model->preferred_locations,
];
$hasInternshipPrefs = (bool) array_filter($internshipRows, static fn($v) => $v !== null && $v !== '');
?>

<div class="sp-portfolio">
    <!-- Hero -->
    <header class="sp-portfolio-hero">
        <div class="sp-portfolio-hero__cover" aria-hidden="true"></div>
        <div class="sp-portfolio-hero__body">
            <div class="sp-portfolio-hero__photo">
                <?= ProfileAvatar::widget(['type' => 'student', 'student' => $model, 'size' => 'xl', 'lazy' => false]) ?>
            </div>
            <div class="sp-portfolio-hero__main">
                <div class="sp-portfolio-hero__title-row">
                    <h1><?= Html::encode($fullName) ?></h1>
                    <span class="sp-portfolio-verify sp-portfolio-verify--<?= Html::encode($verificationClass) ?>">
                        <?php if ($verificationClass === 'verified'): ?>
                            <i class="fas fa-circle-check"></i>
                        <?php elseif ($verificationClass === 'pending'): ?>
                            <i class="fas fa-clock"></i>
                        <?php elseif ($verificationClass === 'rejected'): ?>
                            <i class="fas fa-circle-xmark"></i>
                        <?php else: ?>
                            <i class="fas fa-shield"></i>
                        <?php endif; ?>
                        <?= Html::encode($verificationLabel) ?>
                    </span>
                </div>
                <ul class="sp-portfolio-hero__meta">
                    <?php if ($model->university): ?>
                        <li><i class="fas fa-university"></i> <?= Html::encode($model->university) ?></li>
                    <?php endif; ?>
                    <?php if ($model->program): ?>
                        <li><i class="fas fa-book"></i> <?= Html::encode($model->program) ?></li>
                    <?php endif; ?>
                    <?php if ($model->field_of_study): ?>
                        <li><i class="fas fa-graduation-cap"></i> <?= Html::encode($model->field_of_study) ?></li>
                    <?php endif; ?>
                    <?php if ($model->graduation_year): ?>
                        <li><i class="fas fa-calendar"></i> Class of <?= Html::encode((string) $model->graduation_year) ?></li>
                    <?php elseif ($academicLevelLabel): ?>
                        <li><i class="fas fa-layer-group"></i> <?= Html::encode($academicLevelLabel) ?></li>
                    <?php endif; ?>
                    <?php if ($locationDisplay): ?>
                        <li><i class="fas fa-location-dot"></i> <?= Html::encode($locationDisplay) ?></li>
                    <?php elseif ($phone): ?>
                        <li><i class="fas fa-phone"></i> <?= Html::encode($phone) ?></li>
                    <?php endif; ?>
                </ul>
                <div class="sp-portfolio-hero__actions">
                    <?= Html::a('<i class="fas fa-pen"></i> Edit Profile', ['edit-profile'], ['class' => 'sp-prof-btn sp-prof-btn--primary']) ?>
                    <?php if ($hasCv): ?>
                        <?= Html::a('<i class="fas fa-download"></i> Download CV', ['profile/download-cv'], ['class' => 'sp-prof-btn sp-prof-btn--ghost', 'data-pjax' => 0]) ?>
                    <?php endif; ?>
                    <button type="button" class="sp-prof-btn sp-prof-btn--ghost" id="spProfShareBtn">
                        <i class="fas fa-share-nodes"></i> Share Profile
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="sp-portfolio-layout">
        <main class="sp-portfolio-main">
            <!-- About -->
            <section class="sp-portfolio-section" id="about">
                <div class="sp-portfolio-section__head">
                    <h2><i class="fas fa-user"></i> About Me</h2>
                    <?php if ($bio === ''): ?>
                        <?= Html::a('Add bio', ['edit-profile', '#' => 'section-summary'], ['class' => 'sp-portfolio-link']) ?>
                    <?php endif; ?>
                </div>
                <?php if ($bio !== ''): ?>
                    <div class="sp-portfolio-about<?= $bioLong ? ' is-collapsed' : '' ?>" id="spProfAboutText"><?= nl2br(Html::encode($bio)) ?></div>
                    <?php if ($bioLong): ?>
                        <button type="button" class="sp-portfolio-read-more" id="spProfAboutToggle">Read more</button>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="sp-portfolio-empty">Add a professional summary to tell recruiters about your goals and experience.</p>
                <?php endif; ?>
            </section>

            <!-- Academic -->
            <section class="sp-portfolio-section" id="academic">
                <div class="sp-portfolio-section__head">
                    <h2><i class="fas fa-graduation-cap"></i> Academic Information</h2>
                    <?= Html::a('Edit', ['edit-profile', '#' => 'section-academic'], ['class' => 'sp-portfolio-link']) ?>
                </div>
                <?php if ($hasAcademicData): ?>
                    <dl class="sp-portfolio-dl">
                        <?php foreach ($academicRows as $label => $value): ?>
                            <?php if ($value !== null && $value !== ''): ?>
                                <div class="sp-portfolio-dl__row">
                                    <dt><?= Html::encode($label) ?></dt>
                                    <dd><?= Html::encode((string) $value) ?></dd>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </dl>
                <?php else: ?>
                    <p class="sp-portfolio-empty">No academic details yet.</p>
                    <?= Html::a('Add academic information', ['edit-profile', '#' => 'section-academic'], ['class' => 'sp-portfolio-cta']) ?>
                <?php endif; ?>
            </section>

            <!-- Skills -->
            <section class="sp-portfolio-section" id="skills">
                <div class="sp-portfolio-section__head">
                    <h2><i class="fas fa-tags"></i> Skills</h2>
                    <?= Html::a('Edit', ['edit-profile', '#' => 'section-skills'], ['class' => 'sp-portfolio-link']) ?>
                </div>
                <?php if ($skillsList !== []): ?>
                    <div class="sp-portfolio-skills">
                        <?php foreach ($skillsList as $skill): ?>
                            <span class="sp-portfolio-skill"><?= Html::encode($skill) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="sp-portfolio-empty">Add skills to highlight your strengths to recruiters.</p>
                    <?= Html::a('Add skills', ['edit-profile', '#' => 'section-skills'], ['class' => 'sp-portfolio-cta']) ?>
                <?php endif; ?>
            </section>

            <!-- Verification -->
            <section class="sp-portfolio-section" id="verification">
                <div class="sp-portfolio-section__head">
                    <h2><i class="fas fa-shield-check"></i> Student Verification</h2>
                    <?= Html::a('Manage', ['verification'], ['class' => 'sp-portfolio-link']) ?>
                </div>
                <div class="sp-portfolio-verify-card sp-portfolio-verify-card--<?= Html::encode($verificationClass) ?>">
                    <div class="sp-portfolio-verify-card__status">
                        <strong><?= Html::encode($verificationLabel) ?></strong>
                        <?php if ($verificationClass === 'verified' && $model->getIdVerifiedAtFormatted()): ?>
                            <span class="text-muted small">Verified <?= Html::encode($model->getIdVerifiedAtFormatted()) ?></span>
                        <?php elseif ($verificationClass === 'pending' && $model->getIdUploadedAtFormatted()): ?>
                            <span class="text-muted small">Submitted <?= Html::encode($model->getIdUploadedAtFormatted()) ?></span>
                        <?php elseif ($verificationClass === 'rejected' && $model->id_rejection_reason): ?>
                            <span class="text-danger small"><?= Html::encode($model->id_rejection_reason) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($hasIdDocument): ?>
                        <?php if ($idIsImage): ?>
                            <a href="<?= Html::encode($idPreviewUrl) ?>" class="sp-portfolio-id-preview" target="_blank" rel="noopener">
                                <img src="<?= Html::encode($idPreviewUrl) ?>" alt="Student ID preview">
                            </a>
                        <?php else: ?>
                            <div class="sp-portfolio-id-file">
                                <i class="fas fa-file-pdf"></i>
                                <span><?= Html::encode($idDocService->downloadFilename($model)) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($verificationClass === 'none'): ?>
                        <p class="sp-portfolio-empty mb-2">Upload your university student ID to verify your identity.</p>
                        <?= Html::a('<i class="fas fa-upload"></i> Upload Student ID', ['verification'], ['class' => 'sp-portfolio-cta']) ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Documents -->
            <section class="sp-portfolio-section" id="documents">
                <div class="sp-portfolio-section__head">
                    <h2><i class="fas fa-folder-open"></i> Documents</h2>
                    <?= Html::a('Manage', ['edit-profile', '#' => 'section-documents'], ['class' => 'sp-portfolio-link']) ?>
                </div>
                <div class="sp-portfolio-docs">
                    <article class="sp-portfolio-doc<?= $hasCv ? '' : ' is-missing' ?>">
                        <div class="sp-portfolio-doc__icon"><i class="fas fa-file-pdf"></i></div>
                        <div class="sp-portfolio-doc__body">
                            <strong>CV / Resume</strong>
                            <?php if ($hasCv): ?>
                                <span class="text-muted small">Ready for applications</span>
                            <?php else: ?>
                                <span class="text-muted small">Not uploaded</span>
                            <?php endif; ?>
                        </div>
                        <div class="sp-portfolio-doc__actions">
                            <?php if ($hasCv): ?>
                                <?= Html::a('Download', ['profile/download-cv'], ['class' => 'sp-portfolio-link', 'data-pjax' => 0]) ?>
                            <?php else: ?>
                                <?= Html::a('Upload', ['edit-profile', '#' => 'section-documents'], ['class' => 'sp-portfolio-link']) ?>
                            <?php endif; ?>
                        </div>
                    </article>
                    <article class="sp-portfolio-doc<?= $hasIdDocument ? '' : ' is-missing' ?>">
                        <div class="sp-portfolio-doc__icon"><i class="fas fa-id-card"></i></div>
                        <div class="sp-portfolio-doc__body">
                            <strong>Student ID</strong>
                            <?php if ($hasIdDocument): ?>
                                <span class="text-muted small"><?= Html::encode($model->getIdVerificationLabel()) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">Not uploaded</span>
                            <?php endif; ?>
                        </div>
                        <div class="sp-portfolio-doc__actions">
                            <?php if ($hasIdDocument): ?>
                                <?php if ($idIsImage): ?>
                                    <?= Html::a('View', ['profile/view-id-document'], ['class' => 'sp-portfolio-link', 'target' => '_blank', 'data-pjax' => 0]) ?>
                                <?php endif; ?>
                                <?= Html::a('Download', ['profile/download-id-document'], ['class' => 'sp-portfolio-link', 'data-pjax' => 0]) ?>
                            <?php else: ?>
                                <?= Html::a('Upload', ['verification'], ['class' => 'sp-portfolio-link']) ?>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Internship Preferences -->
            <section class="sp-portfolio-section" id="preferences">
                <div class="sp-portfolio-section__head">
                    <h2><i class="fas fa-briefcase"></i> Internship Preferences</h2>
                    <?= Html::a('Edit', ['edit-profile', '#' => 'section-internship'], ['class' => 'sp-portfolio-link']) ?>
                </div>
                <?php if ($hasInternshipPrefs): ?>
                    <dl class="sp-portfolio-dl">
                        <?php foreach ($internshipRows as $label => $value): ?>
                            <?php if ($value !== null && $value !== ''): ?>
                                <div class="sp-portfolio-dl__row">
                                    <dt><?= Html::encode($label) ?></dt>
                                    <dd><?= Html::encode((string) $value) ?></dd>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </dl>
                <?php else: ?>
                    <p class="sp-portfolio-empty">Set your industry, work mode, and location preferences.</p>
                    <?= Html::a('Set preferences', ['edit-profile', '#' => 'section-internship'], ['class' => 'sp-portfolio-cta']) ?>
                <?php endif; ?>
            </section>

            <!-- Social -->
            <?php if ($socialLinks !== []): ?>
            <section class="sp-portfolio-section" id="social">
                <div class="sp-portfolio-section__head">
                    <h2><i class="fas fa-link"></i> Portfolio &amp; Social Links</h2>
                    <?= Html::a('Edit', ['edit-profile', '#' => 'section-social'], ['class' => 'sp-portfolio-link']) ?>
                </div>
                <div class="sp-portfolio-social">
                    <?php foreach ($socialLinks as $name => $link): ?>
                        <a href="<?= Html::encode($link['url']) ?>" class="sp-portfolio-social__link sp-portfolio-social__link--<?= Html::encode($link['class']) ?>"
                           target="_blank" rel="noopener noreferrer">
                            <i class="<?= Html::encode($link['icon']) ?>"></i>
                            <span><?= Html::encode($name) ?></span>
                            <i class="fas fa-arrow-up-right-from-square sp-portfolio-social__ext"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </main>

        <aside class="sp-portfolio-aside" aria-label="Profile completion">
            <section class="sp-portfolio-completion">
                <div class="sp-portfolio-completion__head">
                    <span>Profile Completion</span>
                    <strong><?= (int) $profilePercent ?>%</strong>
                </div>
                <div class="sp-portfolio-completion__bar" role="progressbar" aria-valuenow="<?= (int) $profilePercent ?>">
                    <span style="width:<?= (int) $profilePercent ?>%"></span>
                </div>
                <?php if ($missingItems !== []): ?>
                    <p class="sp-portfolio-completion__label">Missing:</p>
                    <ul class="sp-portfolio-completion__list">
                        <?php foreach ($missingItems as $item): ?>
                            <li>
                                <i class="fas fa-circle-exclamation"></i>
                                <?= Html::a(Html::encode($item['label']), $item['url'], ['class' => 'text-decoration-none']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="sp-portfolio-completion__done"><i class="fas fa-circle-check"></i> Profile complete</p>
                <?php endif; ?>
                <?= Html::a('Complete profile', ['edit-profile'], ['class' => 'sp-portfolio-cta sp-portfolio-cta--block mt-2']) ?>
            </section>

            <?php if ($email || $phone): ?>
            <section class="sp-portfolio-contact">
                <h3>Contact</h3>
                <?php if ($email): ?>
                    <p><i class="fas fa-envelope"></i> <?= Html::encode($email) ?></p>
                <?php endif; ?>
                <?php if ($phone): ?>
                    <p><i class="fas fa-phone"></i> <?= Html::encode($phone) ?></p>
                <?php endif; ?>
            </section>
            <?php endif; ?>
        </aside>
    </div>
</div>
