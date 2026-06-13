<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\OAuthCompleteProfileForm $model */
/** @var string $detectionSummary */

use yii\bootstrap5\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Complete Your Profile';
$showOrgFields = $model->role === 'organization';
?>
<div class="auth-bg auth-bg--signup" data-auth-page="signup">
    <div class="auth-overlay"></div>
    <div class="auth-ambient auth-ambient--one" aria-hidden="true"></div>
    <div class="auth-ambient auth-ambient--two" aria-hidden="true"></div>
    <div class="auth-ambient auth-ambient--three" aria-hidden="true"></div>
    <div class="auth-transition-overlay" aria-hidden="true"></div>
    <div class="auth-form-container">
        <div class="auth-logo auth-transition-chrome">
            <img src="https://upload.wikimedia.org/wikipedia/commons/6/61/HTML5_logo_and_wordmark.svg" alt="Logo">
        </div>

        <div class="auth-unified auth-transition-panel" data-auth-state="signup">
            <div class="auth-unified__inner">
                <section class="auth-form-pane auth-form-pane--signup is-active" data-auth-pane="signup">
                    <div class="auth-card auth-card--wide">
                        <div class="auth-header">
                            <h1>Complete Your Profile</h1>
                            <p><?= Html::encode($detectionSummary) ?></p>
                        </div>

                        <div class="auth-divider">confirm your account type</div>

                        <?php $form = ActiveForm::begin(['id' => 'form-complete-profile']); ?>

                        <div class="auth-field-group">
                            <?= $form->field($model, 'role', [
                                'template' => '<label class="form-label required-field"><i>🎯</i> I am a</label>{input}{error}',
                            ])->dropDownList(
                                [
                                    'student' => 'Student - Looking for opportunities',
                                    'organization' => 'Organization - Offering opportunities',
                                ],
                                ['class' => 'form-select', 'id' => 'role-select']
                            ) ?>
                        </div>

                        <div id="organization-fields" class="organization-fields<?= $showOrgFields ? ' show' : '' ?>">
                            <h6>📋 Organization Details</h6>
                            <div class="auth-field-group">
                                <?= $form->field($model, 'organization_name', [
                                    'template' => '<label class="form-label"><i>🏢</i> Organization Name</label>{input}{error}',
                                    'inputOptions' => ['placeholder' => 'Acme Corporation', 'class' => 'form-control'],
                                ])->textInput() ?>
                            </div>
                            <div class="auth-field-group">
                                <?= $form->field($model, 'organization_type', [
                                    'template' => '<label class="form-label"><i>🏷️</i> Organization Type</label>{input}{error}',
                                ])->dropDownList(
                                    [
                                        'company' => 'Company',
                                        'nonprofit' => 'Non-Profit',
                                        'government' => 'Government',
                                        'educational' => 'Educational Institution',
                                        'startup' => 'Startup',
                                        'other' => 'Other',
                                    ],
                                    ['prompt' => 'Select type', 'class' => 'form-select']
                                ) ?>
                            </div>
                        </div>

                        <?= Html::submitButton('Confirm & Continue', ['class' => 'btn btn-primary', 'name' => 'complete-profile-button']) ?>

                        <?php ActiveForm::end(); ?>

                        <div class="auth-card-footer">
                            <p class="help-text">You can change this later in your account settings.</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="auth-copyright auth-transition-chrome">
            <small>&copy; <?= date('Y') ?> Key. All Rights Reserved | Designed with ❤️ by danny</small>
        </div>
    </div>
</div>
