<?php
/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\StudentSignupForm $model */

use frontend\models\StudentSignupForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;
?>
<div class="auth-reg-step auth-reg-step--student is-active" data-reg-panel="student">
    <div class="auth-header">
        <a href="<?= Url::to(['site/signup']) ?>" class="auth-reg-back"><i class="fas fa-arrow-left"></i> Change role</a>
        <h1>Student Registration</h1>
        <p>Tell us about yourself and your academic background.</p>
    </div>

    <?php $form = \yii\bootstrap5\ActiveForm::begin([
        'id' => 'form-student-signup',
        'options' => ['enctype' => 'multipart/form-data'],
    ]); ?>
    <?= Html::hiddenInput('role', 'student') ?>

    <div class="auth-form-row">
        <div class="auth-field-group">
            <?= $form->field($model, 'firstname', [
                'template' => '<label class="form-label required-field">First Name</label>{input}{error}',
                'inputOptions' => ['class' => 'form-control', 'placeholder' => 'Daniel'],
            ])->textInput() ?>
        </div>
        <div class="auth-field-group">
            <?= $form->field($model, 'lastname', [
                'template' => '<label class="form-label required-field">Last Name</label>{input}{error}',
                'inputOptions' => ['class' => 'form-control', 'placeholder' => 'Neriwa'],
            ])->textInput() ?>
        </div>
    </div>

    <div class="auth-field-group">
        <?= $form->field($model, 'username', [
            'template' => '<label class="form-label required-field">Username</label>{input}<div class="help-text">Letters, numbers, and underscores only</div>{error}',
            'inputOptions' => ['class' => 'form-control', 'placeholder' => 'dani_neri'],
        ])->textInput() ?>
    </div>

    <div class="auth-form-row">
        <div class="auth-field-group">
            <?= $form->field($model, 'email', [
                'template' => '<label class="form-label required-field">Email</label>{input}{error}',
                'inputOptions' => ['class' => 'form-control', 'type' => 'email', 'placeholder' => 'you@university.ac.tz'],
            ])->textInput() ?>
        </div>
        <div class="auth-field-group">
            <?= $form->field($model, 'phone', [
                'template' => '<label class="form-label required-field">Phone</label>{input}{error}',
                'inputOptions' => ['class' => 'form-control', 'placeholder' => '+255 xxx xxx xxx'],
            ])->textInput() ?>
        </div>
    </div>

    <div class="auth-form-row">
        <div class="auth-field-group">
            <?= $form->field($model, 'password', [
                'template' => '<label class="form-label required-field">Password</label><div class="password-wrapper">{input}<span class="password-toggle" data-toggle-password="studentsignupform-password">👁️</span></div><div id="password-strength" class="password-strength"></div>{error}',
                'inputOptions' => ['class' => 'form-control', 'id' => 'studentsignupform-password'],
            ])->passwordInput() ?>
        </div>
        <div class="auth-field-group">
            <?= $form->field($model, 'confirm_password', [
                'template' => '<label class="form-label required-field">Confirm Password</label><div class="password-wrapper">{input}<span class="password-toggle" data-toggle-password="studentsignupform-confirm_password">👁️</span></div>{error}',
                'inputOptions' => ['class' => 'form-control', 'id' => 'studentsignupform-confirm_password'],
            ])->passwordInput() ?>
        </div>
    </div>

    <div class="auth-reg-section">
        <h6>Academic details</h6>
        <div class="auth-field-group">
            <?= $form->field($model, 'university', [
                'template' => '<label class="form-label required-field">University</label>{input}{error}',
            ])->dropDownList(StudentSignupForm::universityOptions(), ['class' => 'form-select', 'id' => 'student-university']) ?>
        </div>
        <div class="auth-field-group" id="student-university-other-wrap" hidden>
            <?= $form->field($model, 'university_other', [
                'template' => '<label class="form-label required-field">Specify university</label>{input}{error}',
                'inputOptions' => ['class' => 'form-control', 'placeholder' => 'Your university name'],
            ])->textInput() ?>
        </div>
        <div class="auth-form-row">
            <div class="auth-field-group">
                <?= $form->field($model, 'field_of_study', [
                    'template' => '<label class="form-label required-field">Field of Study</label>{input}{error}',
                ])->dropDownList(StudentSignupForm::fieldOptions(), ['class' => 'form-select']) ?>
            </div>
            <div class="auth-field-group">
                <?= $form->field($model, 'academic_level', [
                    'template' => '<label class="form-label required-field">Education Level</label>{input}{error}',
                ])->dropDownList(\common\models\Student::getAcademicLevelOptions(), ['class' => 'form-select']) ?>
            </div>
        </div>
        <div class="auth-field-group">
            <?= $form->field($model, 'graduation_year', [
                'template' => '<label class="form-label required-field">Graduation Year</label>{input}{error}',
            ])->dropDownList(StudentSignupForm::graduationYearOptions(), ['class' => 'form-select']) ?>
        </div>
        <div class="auth-field-group">
            <?= $form->field($model, 'cvFile', [
                'template' => '<label class="form-label">CV Upload <span class="info-badge">Optional</span></label>{input}<div class="help-text">PDF or Word, max 5 MB</div>{error}',
            ])->fileInput(['class' => 'form-control', 'accept' => '.pdf,.doc,.docx']) ?>
        </div>
    </div>

    <div class="form-check">
        <?= $form->field($model, 'terms', [
            'template' => '{input} {label}{error}',
            'options' => ['class' => ''],
        ])->checkbox([
            'class' => 'form-check-input',
            'label' => 'I agree to the ' . Html::a('Terms of Service', ['site/terms'], ['target' => '_blank']) . ' and ' . Html::a('Privacy Policy', ['site/privacy'], ['target' => '_blank']),
            'labelOptions' => ['class' => 'form-check-label'],
        ]) ?>
    </div>

    <?= Html::submitButton('Create Student Account', ['class' => 'btn btn-primary', 'name' => 'signup-button']) ?>
    <?php \yii\bootstrap5\ActiveForm::end(); ?>

    <div class="auth-card-footer">
        <p>Already have an account? <?= Html::a('Sign In', ['site/login'], ['class' => 'auth-switch-link', 'data-auth-target' => 'login']) ?></p>
    </div>
</div>
