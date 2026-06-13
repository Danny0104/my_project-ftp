<?php
/** @var yii\web\View $this */
/** @var \frontend\models\OrganizationSignupForm $model */

use frontend\models\OrganizationSignupForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;

$steps = ['Account', 'Organization Details', 'Verification', 'Review & Submit'];
?>
<div class="auth-reg-step auth-reg-step--organization is-active" data-reg-panel="organization">
    <div class="auth-header">
        <a href="<?= Url::to(['site/signup']) ?>" class="auth-reg-back"><i class="fas fa-arrow-left"></i> Change role</a>
        <h1>Organization Registration</h1>
        <p>Register your organization in four quick steps.</p>
    </div>

    <div class="auth-wizard-progress" role="progressbar" aria-valuemin="1" aria-valuemax="4" aria-valuenow="1">
        <div class="auth-wizard-progress__track">
            <div class="auth-wizard-progress__fill" data-wizard-progress-fill style="width:25%"></div>
        </div>
        <ol class="auth-wizard-steps">
            <?php foreach ($steps as $i => $label): ?>
                <li class="auth-wizard-step<?= $i === 0 ? ' is-active' : '' ?>" data-wizard-step-indicator="<?= $i + 1 ?>">
                    <span class="auth-wizard-step__num"><?= $i + 1 ?></span>
                    <span class="auth-wizard-step__label"><?= Html::encode($label) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>

    <?php $form = \yii\bootstrap5\ActiveForm::begin([
        'id' => 'form-organization-signup',
        'options' => ['enctype' => 'multipart/form-data', 'class' => 'auth-wizard-form'],
    ]); ?>
    <?= Html::hiddenInput('role', 'organization') ?>

    <!-- Step 1: Account -->
    <div class="auth-wizard-panel is-active" data-wizard-panel="1">
        <h6 class="auth-wizard-panel__title">Step 1 — Account</h6>
        <div class="auth-field-group">
            <?= $form->field($model, 'contact_person', [
                'template' => '<label class="form-label required-field">Contact Person Name</label>{input}{error}',
                'inputOptions' => ['class' => 'form-control', 'data-wizard-required' => '1'],
            ])->textInput() ?>
        </div>
        <div class="auth-form-row">
            <div class="auth-field-group">
                <?= $form->field($model, 'email', [
                    'template' => '<label class="form-label required-field">Email</label>{input}{error}',
                    'inputOptions' => ['class' => 'form-control', 'type' => 'email', 'data-wizard-required' => '1'],
                ])->textInput() ?>
            </div>
            <div class="auth-field-group">
                <?= $form->field($model, 'phone', [
                    'template' => '<label class="form-label required-field">Phone</label>{input}{error}',
                    'inputOptions' => ['class' => 'form-control', 'data-wizard-required' => '1'],
                ])->textInput() ?>
            </div>
        </div>
        <div class="auth-form-row">
            <div class="auth-field-group">
                <?= $form->field($model, 'password', [
                    'template' => '<label class="form-label required-field">Password</label><div class="password-wrapper">{input}<span class="password-toggle" data-toggle-password="organizationsignupform-password">👁️</span></div>{error}',
                    'inputOptions' => ['class' => 'form-control', 'id' => 'organizationsignupform-password', 'data-wizard-required' => '1'],
                ])->passwordInput() ?>
            </div>
            <div class="auth-field-group">
                <?= $form->field($model, 'confirm_password', [
                    'template' => '<label class="form-label required-field">Confirm Password</label><div class="password-wrapper">{input}<span class="password-toggle" data-toggle-password="organizationsignupform-confirm_password">👁️</span></div>{error}',
                    'inputOptions' => ['class' => 'form-control', 'id' => 'organizationsignupform-confirm_password', 'data-wizard-required' => '1'],
                ])->passwordInput() ?>
            </div>
        </div>
    </div>

    <!-- Step 2: Organization Details -->
    <div class="auth-wizard-panel" data-wizard-panel="2" hidden>
        <h6 class="auth-wizard-panel__title">Step 2 — Organization Details</h6>
        <div class="auth-field-group">
            <?= $form->field($model, 'organization_name', [
                'template' => '<label class="form-label required-field">Organization Name</label>{input}{error}',
                'inputOptions' => ['class' => 'form-control', 'data-wizard-required' => '1'],
            ])->textInput() ?>
        </div>
        <div class="auth-form-row">
            <div class="auth-field-group">
                <?= $form->field($model, 'registration_number', [
                    'template' => '<label class="form-label required-field">Registration Number</label>{input}{error}',
                    'inputOptions' => ['class' => 'form-control', 'data-wizard-required' => '1'],
                ])->textInput() ?>
            </div>
            <div class="auth-field-group">
                <?= $form->field($model, 'industry', [
                    'template' => '<label class="form-label required-field">Industry</label>{input}{error}',
                ])->dropDownList(OrganizationSignupForm::industryOptions(), ['class' => 'form-select', 'data-wizard-required' => '1']) ?>
            </div>
        </div>
        <div class="auth-field-group">
            <?= $form->field($model, 'organization_type', [
                'template' => '<label class="form-label required-field">Organization Type</label>{input}{error}',
            ])->dropDownList(OrganizationSignupForm::organizationTypeOptions(), ['class' => 'form-select', 'data-wizard-required' => '1']) ?>
        </div>
        <div class="auth-form-row">
            <div class="auth-field-group">
                <?= $form->field($model, 'country', [
                    'template' => '<label class="form-label required-field">Country</label>{input}{error}',
                ])->dropDownList(OrganizationSignupForm::countryOptions(), ['class' => 'form-select', 'data-wizard-required' => '1']) ?>
            </div>
            <div class="auth-field-group">
                <?= $form->field($model, 'region', [
                    'template' => '<label class="form-label required-field">Region</label>{input}{error}',
                    'inputOptions' => ['class' => 'form-control', 'data-wizard-required' => '1'],
                ])->textInput() ?>
            </div>
        </div>
        <div class="auth-form-row">
            <div class="auth-field-group">
                <?= $form->field($model, 'city', [
                    'template' => '<label class="form-label required-field">City</label>{input}{error}',
                    'inputOptions' => ['class' => 'form-control', 'data-wizard-required' => '1'],
                ])->textInput() ?>
            </div>
            <div class="auth-field-group">
                <?= $form->field($model, 'website', [
                    'template' => '<label class="form-label">Website</label>{input}{error}',
                    'inputOptions' => ['class' => 'form-control', 'placeholder' => 'https://example.com'],
                ])->textInput() ?>
            </div>
        </div>
        <div class="auth-field-group">
            <?= $form->field($model, 'address', [
                'template' => '<label class="form-label required-field">Address</label>{input}{error}',
                'inputOptions' => ['class' => 'form-control', 'data-wizard-required' => '1'],
            ])->textarea(['rows' => 2]) ?>
        </div>
    </div>

    <!-- Step 3: Verification -->
    <div class="auth-wizard-panel" data-wizard-panel="3" hidden>
        <h6 class="auth-wizard-panel__title">Step 3 — Verification</h6>
        <div class="auth-field-group">
            <?= $form->field($model, 'logoFile', [
                'template' => '<label class="form-label">Organization Logo</label>{input}<div class="help-text">PNG, JPG, or WebP — max 5 MB</div>{error}',
            ])->fileInput(['class' => 'form-control', 'accept' => 'image/*', 'data-org-logo-input' => '1']) ?>
            <div class="auth-file-preview" data-org-logo-preview hidden></div>
        </div>
        <div class="auth-field-group">
            <?= $form->field($model, 'certificateFile', [
                'template' => '<label class="form-label required-field">Registration Certificate</label>{input}<div class="help-text">PDF or image — max 8 MB</div>{error}',
            ])->fileInput(['class' => 'form-control', 'accept' => '.pdf,image/*', 'data-wizard-required' => '1']) ?>
        </div>
    </div>

    <!-- Step 4: Review -->
    <div class="auth-wizard-panel" data-wizard-panel="4" hidden>
        <h6 class="auth-wizard-panel__title">Step 4 — Review & Submit</h6>
        <div class="auth-review-card" data-wizard-review>
            <p class="help-text">Confirm your details before submitting.</p>
            <dl class="auth-review-list"></dl>
        </div>
        <div class="form-check mt-3">
            <?= $form->field($model, 'terms', [
                'template' => '{input} {label}{error}',
                'options' => ['class' => ''],
            ])->checkbox([
                'class' => 'form-check-input',
                'label' => 'I agree to the ' . Html::a('Terms of Service', ['site/terms'], ['target' => '_blank']) . ' and ' . Html::a('Privacy Policy', ['site/privacy'], ['target' => '_blank']),
                'labelOptions' => ['class' => 'form-check-label'],
            ]) ?>
        </div>
    </div>

    <div class="auth-wizard-nav">
        <button type="button" class="btn btn-outline-light auth-wizard-prev" data-wizard-prev hidden>Back</button>
        <button type="button" class="btn btn-primary auth-wizard-next" data-wizard-next>Continue</button>
        <?= Html::submitButton('Submit Registration', ['class' => 'btn btn-primary auth-wizard-submit', 'name' => 'signup-button', 'hidden' => true, 'data-wizard-submit' => '1']) ?>
    </div>

    <?php \yii\bootstrap5\ActiveForm::end(); ?>

    <div class="auth-card-footer">
        <p>Already have an account? <?= Html::a('Sign In', ['site/login'], ['class' => 'auth-switch-link', 'data-auth-target' => 'login']) ?></p>
    </div>
</div>
