<?php

use common\models\Student;
use common\services\ProfileCompletionService;
use common\widgets\ProfileAvatar;
use frontend\assets\StudentSettingsAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use common\models\FieldOfStudy;

/** @var yii\web\View $this */
/** @var Student $model */

require_once __DIR__ . '/../dashboard/_student_helpers.php';

StudentSettingsAsset::register($this);

$this->title = 'Edit Profile';

$user = $model->user;
$firstName = trim((string) ($user->first_name ?? ''));
$lastName = trim((string) ($user->last_name ?? ''));
$displayName = $user->username ?? 'Student';
$email = $user->email ?? '';
$phone = trim((string) ($user->phone ?? ''));

$fieldOptions = FieldOfStudy::find()
    ->select('name')
    ->where(['is_active' => true])
    ->orderBy(['name' => SORT_ASC])
    ->column();
$fieldDropdown = array_combine($fieldOptions, $fieldOptions);

$profileCompletionService = new ProfileCompletionService();
$completionTasks = $profileCompletionService->dashboardTasks($model);
$profilePct = $profileCompletionService->dashboardPercent($model);

$sectionAnchors = [
    'profile_photo' => '#section-personal',
    'university' => '#section-academic',
    'student_id' => '#section-academic',
    'field_of_study' => '#section-academic',
    'cv' => '#section-documents',
    'id_document' => Url::to(['profile/verification']),
];

$universities = [
    '' => 'Select your university...',
    'University of Dar es Salaam (UDSM)' => 'University of Dar es Salaam (UDSM)',
    'Sokoine University of Agriculture (SUA)' => 'Sokoine University of Agriculture (SUA)',
    'Open University of Tanzania (OUT)' => 'Open University of Tanzania (OUT)',
    'State University of Zanzibar (SUZA)' => 'State University of Zanzibar (SUZA)',
    'Mzumbe University (MU)' => 'Mzumbe University (MU)',
    'Other (Please specify)' => 'Other (Please specify)',
];
$presetUniversities = array_filter(array_keys($universities), static fn($k) => $k !== '' && $k !== 'Other (Please specify)');
$currentUniversity = (string) $model->university;
$universityIsOther = $currentUniversity !== '' && !in_array($currentUniversity, $presetUniversities, true);
$universityDropValue = $universityIsOther ? 'Other (Please specify)' : $currentUniversity;
$universityOtherValue = $universityIsOther ? $currentUniversity : '';

$skillsList = array_values(array_filter(array_map('trim', explode(',', (string) ($model->skills ?? '')))));
$suggestedSkills = ['Python', 'PHP', 'SQL', 'Power BI', 'Machine Learning', 'JavaScript', 'Communication', 'Excel'];

$bioLength = mb_strlen((string) ($model->personal_statement ?? ''));
?>

<div class="sp-profile-edit">
    <header class="sp-profile-edit__header">
        <div>
            <h1>Edit Profile</h1>
            <p>Manage your personal and academic information.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?= Html::a('<i class="fas fa-shield-halved"></i> Verification', ['verification'], ['class' => 'sp-set-btn sp-set-btn--ghost']) ?>
            <?= Html::a('<i class="fas fa-gear"></i> Settings', ['settings'], ['class' => 'sp-set-btn sp-set-btn--ghost']) ?>
            <?= Html::a('<i class="fas fa-eye"></i> View public profile', ['view-student'], ['class' => 'sp-set-btn sp-set-btn--ghost']) ?>
        </div>
    </header>

    <?php if ($model->hasErrors()): ?>
        <div class="alert alert-danger sp-set-form-errors" role="alert">
            <strong>Unable to save profile information.</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($model->getFirstErrors() as $error): ?>
                    <li><?= Html::encode($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="sp-profile-edit__layout">
        <main class="sp-profile-edit__main">
            <?php $form = ActiveForm::begin([
                'options' => ['enctype' => 'multipart/form-data', 'id' => 'spSettingsForm', 'class' => 'sp-profile-form'],
            ]); ?>

            <!-- Personal Information -->
            <section class="sp-profile-section" id="section-personal">
                <div class="sp-profile-section__head">
                    <h2><i class="fas fa-user"></i> Personal Information</h2>
                    <p>How recruiters and organizations see you</p>
                </div>
                <div class="sp-profile-personal">
                    <div class="sp-set-photo-upload">
                        <div class="sp-set-photo-preview sp-set-avatar" id="spPhotoPreview">
                            <?= ProfileAvatar::widget(['type' => 'student', 'student' => $model, 'size' => 'xl', 'lazy' => false]) ?>
                        </div>
                        <div class="sp-set-photo-actions">
                            <label class="sp-set-btn sp-set-btn--ghost mb-0" for="profilePhotoInput">
                                <i class="fas fa-camera me-1"></i> Upload photo
                            </label>
                            <input type="file" name="profile_photo" id="profilePhotoInput" class="d-none" accept="image/jpeg,image/png,image/webp">
                            <?php if ($model->hasProfilePhoto()): ?>
                                <?= Html::a('<i class="fas fa-trash me-1"></i> Remove', ['remove-photo'], [
                                    'class' => 'sp-set-btn sp-set-btn--ghost text-danger',
                                    'data' => ['method' => 'post', 'confirm' => 'Remove your profile photo?'],
                                ]) ?>
                            <?php endif; ?>
                            <small>JPG, PNG, WEBP · max 5 MB</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="user-first-name">First Name</label>
                            <input type="text" class="form-control sp-input" id="user-first-name"
                                   name="User[first_name]" value="<?= Html::encode($firstName) ?>" placeholder="First name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="user-last-name">Last Name</label>
                            <input type="text" class="form-control sp-input" id="user-last-name"
                                   name="User[last_name]" value="<?= Html::encode($lastName) ?>" placeholder="Last name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="user-username">Username</label>
                            <input type="text" class="form-control sp-input" id="user-username"
                                   name="User[username]" value="<?= Html::encode($displayName) ?>" placeholder="Username" required>
                        </div>
                        <div class="col-md-6">
                            <div class="sp-set-readonly">
                                <span class="sp-set-readonly-label">Email</span>
                                <span class="sp-set-readonly-value"><?= Html::encode($email ?: '—') ?></span>
                            </div>
                            <p class="text-muted small mb-0">Contact support to change your email address.</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="user-phone">Phone Number</label>
                            <input type="tel" class="form-control sp-input" id="user-phone"
                                   name="User[phone]" value="<?= Html::encode($phone) ?>" placeholder="+255…">
                        </div>
                    </div>
                </div>
            </section>

            <!-- Academic Information -->
            <section class="sp-profile-section" id="section-academic">
                <div class="sp-profile-section__head">
                    <h2><i class="fas fa-graduation-cap"></i> Academic Information</h2>
                    <p>University details used for verification and matching</p>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <?= $form->field($model, 'university')->dropDownList($universities, [
                            'class' => 'form-control sp-input',
                            'id' => 'student-university',
                            'value' => $universityDropValue,
                        ])->label('University <span class="text-danger">*</span>') ?>
                        <div id="other-university-field" class="mt-1"<?= $universityIsOther ? '' : ' style="display:none"' ?>>
                            <label class="form-label" for="other-university-input">Specify university</label>
                            <input type="text" class="form-control sp-input" id="other-university-input"
                                   name="Student[university_other]" placeholder="University name"
                                   value="<?= Html::encode($universityOtherValue) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'student_id')->textInput([
                            'class' => 'form-control sp-input',
                            'placeholder' => 'University registration number',
                        ])->label('Student Registration Number <span class="text-danger">*</span>') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'program')->textInput([
                            'class' => 'form-control sp-input',
                            'placeholder' => 'BSc Computer Science',
                        ])->label('Academic Program') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'department')->textInput(['class' => 'form-control sp-input'])->label('Department') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'faculty')->textInput(['class' => 'form-control sp-input'])->label('Faculty / School') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'field_of_study')->dropDownList($fieldDropdown, [
                            'class' => 'form-control sp-input',
                            'prompt' => 'Select field…',
                        ])->label('Field of Study <span class="text-danger">*</span>') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'academic_level')->dropDownList(
                            Student::getAcademicLevelOptions(),
                            ['class' => 'form-control sp-input', 'prompt' => 'Select level…']
                        )->label('Education Level') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'graduation_year')->textInput([
                            'type' => 'number',
                            'class' => 'form-control sp-input',
                            'placeholder' => (string) ((int) date('Y') + 1),
                            'min' => (int) date('Y'),
                            'max' => (int) date('Y') + 8,
                        ])->label('Year of Study / Graduation') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'gpa')->textInput([
                            'type' => 'number', 'step' => '0.01', 'min' => 0, 'max' => 4,
                            'class' => 'form-control sp-input', 'placeholder' => '3.2',
                        ])->label('GPA <span class="text-muted">(optional)</span>') ?>
                    </div>
                </div>
            </section>

            <!-- Professional Summary -->
            <section class="sp-profile-section" id="section-summary">
                <div class="sp-profile-section__head">
                    <h2><i class="fas fa-align-left"></i> Professional Summary</h2>
                    <p>Tell recruiters about your goals and experience</p>
                </div>
                <?= $form->field($model, 'personal_statement')->textarea([
                    'rows' => 5,
                    'class' => 'form-control sp-input',
                    'placeholder' => 'Short professional summary for recruiters…',
                    'maxlength' => 500,
                    'id' => 'spBioInput',
                ])->label('Bio / Personal Statement') ?>
                <p class="sp-profile-char-count text-muted small mb-0">
                    <span id="spBioCount"><?= (int) $bioLength ?></span> / 500 characters
                </p>
            </section>

            <!-- Skills -->
            <section class="sp-profile-section" id="section-skills">
                <div class="sp-profile-section__head">
                    <h2><i class="fas fa-tags"></i> Skills</h2>
                    <p>Add skills to improve internship match scores</p>
                </div>
                <div class="sp-skills-editor" id="spSkillsEditor">
                    <div class="sp-skills-tags" id="spSkillsTags" aria-live="polite">
                        <?php foreach ($skillsList as $skill): ?>
                            <span class="sp-skills-tag"><?= Html::encode($skill) ?><button type="button" aria-label="Remove">&times;</button></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="sp-skills-input-row">
                        <input type="text" class="form-control sp-input" id="spSkillsAddInput" placeholder="Type a skill and press Enter">
                        <button type="button" class="sp-set-btn sp-set-btn--ghost" id="spSkillsAddBtn"><i class="fas fa-plus"></i> Add Skill</button>
                    </div>
                    <input type="hidden" name="Student[skills]" id="spSkillsHidden" value="<?= Html::encode((string) $model->skills) ?>">
                    <div class="sp-skills-suggestions">
                        <span class="text-muted small">Suggested:</span>
                        <?php foreach ($suggestedSkills as $s): ?>
                            <button type="button" class="sp-skills-suggest" data-skill="<?= Html::encode($s) ?>"><?= Html::encode($s) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- CV & Documents -->
            <section class="sp-profile-section" id="section-documents">
                <div class="sp-profile-section__head">
                    <h2><i class="fas fa-file-alt"></i> CV &amp; Documents</h2>
                    <p>Your resume for applications</p>
                </div>
                <?php if ($model->cv): ?>
                    <div class="sp-set-doc-row">
                        <i class="fas fa-file-pdf text-danger"></i>
                        <span style="flex:1"><strong>Current CV</strong></span>
                        <?= Html::a('Download', ['profile/download-cv'], ['class' => 'sp-set-btn sp-set-btn--ghost', 'data-pjax' => 0]) ?>
                        <button type="submit" name="remove_cv" value="1" class="sp-set-btn sp-set-btn--ghost text-danger"
                                form="spSettingsForm" onclick="return confirm('Remove your CV?');">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                <?php endif; ?>
                <div class="sp-upload-zone" id="spCvDropzone">
                    <i class="fas fa-cloud-arrow-up" style="font-size:1.5rem;color:var(--ftp-primary)"></i>
                    <p class="mb-1 mt-2"><strong>Drag &amp; drop CV</strong> or click to browse</p>
                    <span class="text-muted small">PDF, DOC, DOCX · Max 5MB</span>
                    <?= $form->field($model, 'cv')->fileInput([
                        'class' => 'sp-upload-input',
                        'accept' => '.pdf,.doc,.docx',
                        'id' => 'spCvFileInput',
                    ])->label(false) ?>
                    <p class="sp-upload-filename text-muted small mb-0" id="spCvFileName" hidden></p>
                </div>
            </section>

            <section class="sp-profile-section" id="section-internship">
                <div class="sp-profile-section__head">
                    <h2><i class="fas fa-briefcase"></i> Internship Preferences</h2>
                    <p>Help us match you with the right opportunities</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <?= $form->field($model, 'preferred_industry')->textInput([
                            'class' => 'form-control sp-input',
                            'placeholder' => 'Technology, Finance, Healthcare…',
                        ])->label('Preferred Industry') ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($model, 'preferred_work_mode')->dropDownList(
                            Student::getWorkModeOptions(),
                            ['class' => 'form-control sp-input']
                        )->label('Work Mode') ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($model, 'preferred_locations')->textInput([
                            'class' => 'form-control sp-input',
                            'placeholder' => 'Dar es Salaam, Remote, Arusha…',
                        ])->label('Preferred Locations') ?>
                    </div>
                </div>
            </section>

            <section class="sp-profile-section" id="section-social">
                <div class="sp-profile-section__head">
                    <h2><i class="fas fa-link"></i> Social Links <span class="text-muted fw-normal">(optional)</span></h2>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <?= $form->field($model, 'linkedin_url')->textInput([
                            'class' => 'form-control sp-input',
                            'placeholder' => 'https://linkedin.com/in/…',
                        ])->label('LinkedIn') ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($model, 'github_url')->textInput([
                            'class' => 'form-control sp-input',
                            'placeholder' => 'https://github.com/…',
                        ])->label('GitHub') ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($model, 'portfolio_url')->textInput([
                            'class' => 'form-control sp-input',
                            'placeholder' => 'https://your-portfolio.com',
                        ])->label('Portfolio') ?>
                    </div>
                </div>
            </section>

            <?php ActiveForm::end(); ?>
        </main>

        <aside class="sp-profile-edit__aside" aria-label="Profile completion">
            <div class="sp-profile-completion-card">
                <div class="sp-profile-completion-card__ring">
                    <svg viewBox="0 0 56 56" width="56" height="56" aria-hidden="true">
                        <circle cx="28" cy="28" r="24" fill="none" stroke="#e2e8f0" stroke-width="5"/>
                        <circle cx="28" cy="28" r="24" fill="none" stroke="url(#spProfGrad)" stroke-width="5"
                                stroke-dasharray="150.8" stroke-dashoffset="<?= 150.8 - (150.8 * $profilePct / 100) ?>"
                                stroke-linecap="round" transform="rotate(-90 28 28)"/>
                        <defs><linearGradient id="spProfGrad" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#2563eb"/><stop offset="1" stop-color="#6366f1"/></linearGradient></defs>
                    </svg>
                    <span class="sp-profile-completion-card__pct"><?= (int) $profilePct ?>%</span>
                </div>
                <h3>Profile Completion</h3>
                <p class="text-muted small">Complete all items to unlock 100%</p>
                <ul class="sp-profile-checklist">
                    <?php foreach ($completionTasks as $key => $task): ?>
                        <li class="<?= $task['done'] ? 'is-done' : 'is-missing' ?>">
                            <a href="<?= Html::encode($sectionAnchors[$key] ?? '#section-personal') ?>" class="sp-profile-checklist__link">
                                <i class="fas <?= $task['done'] ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                                <?= Html::encode($task['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <nav class="sp-profile-section-nav" aria-label="Jump to section">
                    <span class="sp-profile-section-nav__label">Sections</span>
                    <a href="#section-personal">Personal</a>
                    <a href="#section-academic">Academic</a>
                    <a href="#section-summary">Bio</a>
                    <a href="#section-skills">Skills</a>
                    <a href="#section-documents">CV</a>
                    <a href="#section-internship">Internship</a>
                    <a href="#section-social">Social</a>
                </nav>
            </div>
        </aside>
    </div>

    <div class="sp-set-savebar" id="spSetSavebar">
        <div class="sp-set-savebar-inner">
            <span class="sp-set-savebar-status" id="spSetSaveStatus">All changes saved when you click Save Profile</span>
            <div class="d-flex gap-2">
                <?= Html::submitButton('<i class="fas fa-check"></i> Save Profile', [
                    'class' => 'sp-set-btn sp-set-btn--primary',
                    'form' => 'spSettingsForm',
                ]) ?>
                <?= Html::a('Cancel', ['view-student'], ['class' => 'sp-set-btn sp-set-btn--ghost']) ?>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<'JS'
document.getElementById('profilePhotoInput')?.addEventListener('change', function () {
    var file = this.files && this.files[0];
    if (!file) return;
    var preview = document.getElementById('spPhotoPreview');
    if (!preview) return;
    var reader = new FileReader();
    reader.onload = function (e) {
        preview.innerHTML = '<span class="ft-avatar ft-avatar--xl ft-avatar--student ft-avatar--has-image" style="width:200px;height:200px;"><img class="ft-avatar__img" src="' + e.target.result + '" alt="Preview" width="200" height="200"></span>';
    };
    reader.readAsDataURL(file);
});
JS
);
?>
<?php if (Yii::$app->session->hasFlash('success') || Yii::$app->session->hasFlash('error')): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.ftpShowToast) {
        <?php if (Yii::$app->session->hasFlash('success')): ?>
        window.ftpShowToast(<?= json_encode(Yii::$app->session->getFlash('success'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
        <?php endif; ?>
        <?php if (Yii::$app->session->hasFlash('error')): ?>
        window.ftpShowToast(<?= json_encode(Yii::$app->session->getFlash('error'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
        <?php endif; ?>
    }
    var bar = document.getElementById('spSetSavebar');
    if (bar) bar.classList.add('is-visible');
});
</script>
<?php endif; ?>
