<?php

use common\models\Student;
use common\services\StudentIdDocumentService;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Student $model */
/** @var array{ready: bool, missing: string[]} $readiness */
/** @var array<string, mixed> $verificationUi */

$idDocService = new StudentIdDocumentService();
$hasIdDocument = $model->hasIdDocument();
$idVerificationStatus = $model->id_verification_status ?? Student::ID_VERIFICATION_NONE;

$idAbsolutePath = $idDocService->resolveAbsolutePath($model);
$idIsImage = $idAbsolutePath && $idDocService->isImage($idAbsolutePath);
$statusClass = match ($idVerificationStatus) {
    Student::ID_VERIFICATION_APPROVED => 'success',
    Student::ID_VERIFICATION_REJECTED => 'danger',
    default => 'warning',
};
$idPreviewUrl = $hasIdDocument ? Url::to(['profile/view-id-document', 'v' => (int) $model->id_uploaded_at]) : '';
$profileReady = !empty($readiness['ready']);
?>

<div id="spIdVerifyWidget"
     class="sp-id-verify-widget"
     data-upload-url="<?= Html::encode(Url::to(['profile/upload-id-document'])) ?>"
     data-remove-url="<?= Html::encode(Url::to(['profile/remove-id-document'])) ?>"
     data-download-url="<?= Html::encode(Url::to(['profile/download-id-document'])) ?>"
     data-edit-profile-url="<?= Html::encode(Url::to(['edit-profile', '#' => 'section-academic'])) ?>"
     data-csrf-param="<?= Html::encode(Yii::$app->request->csrfParam) ?>"
     data-csrf-token="<?= Html::encode(Yii::$app->request->csrfToken) ?>"
     data-has-document="<?= $hasIdDocument ? '1' : '0' ?>"
     data-profile-ready="<?= $profileReady ? '1' : '0' ?>">

    <div id="spIdVerifyAlert" class="sp-id-verify-alert" hidden role="alert"></div>

    <div id="spIdVerifyStatusCard" class="sp-id-verify-status-card sp-id-verify-status-card--<?= $statusClass ?>"<?= $hasIdDocument ? '' : ' hidden' ?>>
        <?php if ($verificationUi['statusKey'] === 'auto_verified' || ($idVerificationStatus === Student::ID_VERIFICATION_APPROVED && $model->isIdAutoVerified())): ?>
            <div class="sp-id-verify-status-card__main">
                <i class="fas fa-circle-check sp-id-verify-status-card__icon sp-id-verify-status-card__icon--success"></i>
                <div><strong>Verified</strong><span class="sp-id-verify-status-card__time">Student identity matched</span></div>
            </div>
        <?php elseif ($idVerificationStatus === Student::ID_VERIFICATION_REJECTED): ?>
            <div class="sp-id-verify-status-card__main">
                <i class="fas fa-circle-xmark sp-id-verify-status-card__icon sp-id-verify-status-card__icon--danger"></i>
                <div><strong>Rejected</strong>
                    <?php if ($model->id_rejection_reason): ?>
                        <span class="sp-id-verify-status-card__reason">Reason: <?= Html::encode($model->id_rejection_reason) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($idVerificationStatus === Student::ID_VERIFICATION_APPROVED): ?>
            <div class="sp-id-verify-status-card__main">
                <i class="fas fa-circle-check sp-id-verify-status-card__icon sp-id-verify-status-card__icon--success"></i>
                <div><strong>Verified</strong></div>
            </div>
        <?php else: ?>
            <div class="sp-id-verify-status-card__main">
                <i class="fas fa-clock sp-id-verify-status-card__icon sp-id-verify-status-card__icon--warning"></i>
                <div><strong>Pending Review</strong>
                    <?php if ($model->getIdUploadedAtFormatted()): ?>
                        <span class="sp-id-verify-status-card__time">Submitted <?= Html::encode($model->getIdUploadedAtFormatted()) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div id="spIdDropzone"
         class="sp-upload-zone sp-id-dropzone<?= $hasIdDocument ? ' sp-id-dropzone--compact' : '' ?><?= !$profileReady ? ' sp-id-dropzone--disabled' : '' ?>"
         <?= !$profileReady ? 'aria-disabled="true"' : '' ?>>
        <div class="sp-id-dropzone__idle" id="spIdDropzoneIdle">
            <i class="fas fa-cloud-arrow-up sp-id-dropzone__icon"></i>
            <h3 class="sp-id-dropzone__title">Upload Student ID</h3>
            <p class="sp-id-dropzone__lead mb-1">Drag &amp; drop your student ID here</p>
            <p class="sp-id-dropzone__or text-muted mb-2">or</p>
            <button type="button" class="sp-set-btn sp-set-btn--primary sp-id-dropzone__browse" id="spIdBrowseBtn"<?= !$profileReady ? ' disabled' : '' ?>>
                <i class="fas fa-folder-open"></i> Browse File
            </button>
            <p class="sp-id-dropzone__meta text-muted small mb-0">JPG, PNG, PDF · Max 5MB</p>
            <?php if (!$profileReady): ?>
                <p class="sp-id-dropzone__blocked text-danger small mt-2 mb-0">Save your profile first to enable upload.</p>
            <?php endif; ?>
        </div>
        <div class="sp-id-dropzone__preview" id="spIdLocalPreview" hidden>
            <div id="spIdPreviewContent"></div>
            <p class="sp-id-dropzone__filename text-muted small mb-2" id="spIdFileName"></p>
            <div class="sp-id-dropzone__preview-actions">
                <button type="button" class="sp-set-btn sp-set-btn--primary" id="spIdUploadBtn"><i class="fas fa-cloud-arrow-up"></i> Upload &amp; Verify</button>
                <button type="button" class="sp-set-btn sp-set-btn--ghost" id="spIdClearBtn"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </div>
        <div class="sp-id-dropzone__progress" id="spIdProgress" hidden>
            <div class="sp-id-dropzone__progress-bar" id="spIdProgressBar" style="width:0%"></div>
            <span class="sp-id-dropzone__progress-label" id="spIdProgressLabel">Uploading…</span>
        </div>
        <input type="file" name="id_document" id="idDocumentInput" class="sp-upload-input"
               accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"<?= !$profileReady ? ' disabled' : '' ?>>
    </div>

    <div id="spIdUploadedCard" class="sp-id-verify-card sp-id-verify-card--uploaded"<?= $hasIdDocument ? '' : ' hidden' ?>>
        <div class="sp-id-verify-card__head">
            <h3><i class="fas fa-id-card"></i> Student ID on file</h3>
            <span class="sp-id-verify-status sp-id-verify-status--<?= $statusClass ?>" id="spIdStatusBadge"><?= Html::encode($model->getIdVerificationLabel()) ?></span>
        </div>
        <div class="sp-id-verify-preview" id="spIdServerPreview">
            <?php if ($idIsImage): ?>
                <img src="<?= Html::encode($idPreviewUrl) ?>" alt="Student ID preview" class="sp-id-verify-img" id="spIdPreviewImg">
            <?php else: ?>
                <div class="sp-id-verify-pdf" id="spIdPreviewPdf">
                    <i class="fas fa-file-pdf"></i>
                    <span><?= Html::encode($idDocService->downloadFilename($model)) ?></span>
                </div>
            <?php endif; ?>
        </div>
        <div class="sp-id-verify-actions">
            <a href="<?= Html::encode(Url::to(['profile/download-id-document'])) ?>" class="sp-set-btn sp-set-btn--ghost" id="spIdDownloadBtn" data-pjax="0"><i class="fas fa-download"></i> Download</a>
            <button type="button" class="sp-set-btn sp-set-btn--ghost" id="spIdReplaceBtn"<?= !$profileReady ? ' disabled' : '' ?>><i class="fas fa-rotate"></i> Replace</button>
            <button type="button" class="sp-set-btn sp-set-btn--ghost text-danger" id="spIdRemoveBtn"><i class="fas fa-trash"></i> Remove</button>
        </div>
    </div>
</div>
