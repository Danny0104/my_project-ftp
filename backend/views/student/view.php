<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var array $cvInfo */

$this->title = $model->user->username . ' - Student Profile';
$this->params['breadcrumbs'][] = ['label' => 'Students', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Format dates
$createdDate = date('F j, Y', $model->user->created_at);
$updatedDate = date('F j, Y', $model->user->updated_at);
$memberSince = date('F Y', $model->user->created_at);
?>

<style>
    .student-profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .profile-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 5px solid white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        object-fit: cover;
    }
    
    .info-card {
        padding: 25px;
        margin-bottom: 20px;
    }
    
    .info-card-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .info-card-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-right: 15px;
    }
    
    .icon-purple {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .icon-blue {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }
    
    .icon-green {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        color: white;
    }
    
    .icon-orange {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
    }
    
    .info-item {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f5f5f5;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #666;
        min-width: 160px;
        display: flex;
        align-items: center;
    }
    
    .info-label i {
        margin-right: 8px;
        color: #667eea;
    }
    
    .info-value {
        color: #333;
        flex: 1;
    }
    
    .status-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-active {
        background: #d4edda;
        color: #155724;
    }
    
    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    
    .btn-modern {
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    
    .btn-primary-modern {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-danger-modern {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }
    
    .btn-success-modern {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }
    
    .document-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-top: 10px;
    }
    
    .document-link {
        display: inline-flex;
        align-items: center;
        padding: 10px 20px;
        background: white;
        border-radius: 8px;
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .document-link:hover {
        background: #667eea;
        color: white;
        transform: translateX(5px);
    }
    
    .document-link i {
        margin-right: 10px;
        font-size: 18px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .stat-card {
        padding: 20px;
        text-align: center;
    }
    
    .stat-number {
        font-size: 32px;
        font-weight: bold;
        color: #667eea;
        margin: 10px 0;
    }
    
    .stat-label {
        color: #666;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
</style>

<div class="student-view">
    <!-- Profile Header -->
    <div class="student-profile-header">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <?php
                \frontend\assets\ProfileAvatarAsset::register($this);
                echo \common\widgets\ProfileAvatar::widget([
                    'type' => 'student',
                    'student' => $model,
                    'size' => 'xl',
                    'lazy' => false,
                    'cssClass' => 'profile-avatar',
                ]);
                ?>
            </div>
            <div class="col-md-9">
                <h1 style="font-size: 36px; font-weight: 700; margin-bottom: 10px;">
                    <?= Html::encode($model->user->username) ?>
                </h1>
                <p style="font-size: 18px; opacity: 0.9; margin-bottom: 15px;">
                    <i class="fas fa-graduation-cap"></i> Student at <?= Html::encode($model->university) ?>
                </p>
                <div style="display: flex; gap: 20px; flex-wrap: wrap; align-items: center;">
                    <span class="status-badge <?= $model->user->status == 10 ? 'status-active' : 'status-inactive' ?>">
                        <i class="fas fa-circle" style="font-size: 8px;"></i> 
                        <?= $model->user->status == 10 ? 'Active' : 'Inactive' ?>
                    </span>
                    <span style="opacity: 0.9;">
                        <i class="far fa-calendar-alt"></i> Member since <?= $memberSince ?>
                    </span>
                    <span style="opacity: 0.9;">
                        <i class="far fa-id-card"></i> ID: <?= Html::encode($model->student_id) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <?= Html::a('<i class="fas fa-edit"></i> Update Profile', ['update', 'id' => $model->id], [
            'class' => 'btn btn-primary-modern btn-modern'
        ]) ?>
        <?= Html::a('<i class="fas fa-envelope"></i> Send Message', '#', [
            'class' => 'btn btn-success-modern btn-modern'
        ]) ?>
        <?= Html::a('<i class="fas fa-trash-alt"></i> Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger-modern btn-modern',
            'data' => [
                'confirm' => 'Are you sure you want to delete this student?',
                'method' => 'post',
            ],
        ]) ?>
    </div>

    <div class="row mt-4">
        <!-- Personal Information -->
        <div class="col-md-6">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon icon-purple">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-weight: 700; color: #333;">Personal Information</h4>
                        <p style="margin: 0; color: #999; font-size: 13px;">Basic details about the student</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-user-circle"></i> Username
                    </div>
                    <div class="info-value"><?= Html::encode($model->user->username) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-envelope"></i> Email
                    </div>
                    <div class="info-value">
                        <a href="mailto:<?= Html::encode($model->user->email) ?>" style="color: #667eea; text-decoration: none;">
                            <?= Html::encode($model->user->email) ?>
                        </a>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-id-badge"></i> Student ID
                    </div>
                    <div class="info-value"><?= Html::encode($model->student_id) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-user-tag"></i> User Role
                    </div>
                    <div class="info-value">
                        <span class="badge badge-info" style="font-size: 13px; padding: 6px 12px;">
                            <?= Html::encode(ucfirst($model->user->role)) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic Information -->
        <div class="col-md-6">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon icon-blue">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-weight: 700; color: #333;">Academic Information</h4>
                        <p style="margin: 0; color: #999; font-size: 13px;">Educational background</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-university"></i> University
                    </div>
                    <div class="info-value"><?= Html::encode($model->university) ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="fas fa-book"></i> Field of Study
                    </div>
                    <div class="info-value">
                        <?php if ($model->field_of_study): ?>
                            <span class="badge badge-success" style="font-size: 13px; padding: 6px 12px;">
                                <?= Html::encode($model->field_of_study) ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #999; font-style: italic;">Not specified</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="far fa-calendar-plus"></i> Joined
                    </div>
                    <div class="info-value"><?= $createdDate ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">
                        <i class="far fa-calendar-check"></i> Last Updated
                    </div>
                    <div class="info-value"><?= $updatedDate ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Personal Statement -->
        <div class="col-md-12">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon icon-green">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-weight: 700; color: #333;">Personal Statement</h4>
                        <p style="margin: 0; color: #999; font-size: 13px;">Student's personal statement and motivation</p>
                    </div>
                </div>
                
                <div style="padding: 15px; background: #f8f9fa; border-radius: 10px; line-height: 1.8; color: #555;">
                    <?php if ($model->personal_statement): ?>
                        <?= nl2br(Html::encode($model->personal_statement)) ?>
                    <?php else: ?>
                        <em style="color: #999;">No personal statement provided yet.</em>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Student ID verification -->
        <div class="col-md-12">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon icon-green">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-weight: 700; color: #333;">Student ID Verification</h4>
                        <p style="margin: 0; color: #999; font-size: 13px;">University registration and ID document review</p>
                    </div>
                </div>
                <div class="document-section">
                    <p style="margin:0 0 8px"><strong>Registration #:</strong> <?= Html::encode($model->student_id ?: '—') ?></p>
                    <p style="margin:0 0 8px"><strong>Status:</strong> <?= Html::encode($model->getIdVerificationLabel()) ?></p>
                    <?php if ($model->id_verification_score !== null): ?>
                        <p style="margin:0 0 8px"><strong>Verification score:</strong> <?= (int) $model->id_verification_score ?>%</p>
                    <?php endif; ?>
                    <?php if ($model->id_fraud_flag): ?>
                        <p class="text-warning small" style="margin:0 0 8px"><i class="fas fa-triangle-exclamation"></i> <?= Html::encode($model->id_fraud_reason ?? 'Fraud flag') ?></p>
                    <?php endif; ?>
                    <?php
                    $verificationService = new \common\services\StudentIdVerificationService();
                    $verificationUi = $verificationService->buildUiPayload($model);
                    $ocrDebug = $verificationService->getOcrDebug($model);
                    $rawOcrText = $verificationService->getRawOcrText($model);
                    ?>
                    <?php if ($model->id_ocr_confidence !== null): ?>
                        <p style="margin:0 0 8px">
                            <strong>OCR confidence:</strong> <?= (int) $model->id_ocr_confidence ?>%
                            <?php if ($verificationService->isLowOcrConfidence($model)): ?>
                                <span class="badge bg-warning text-dark">Low OCR Confidence</span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($ocrDebug['failure_stage'])): ?>
                        <p class="small text-muted" style="margin:0 0 8px"><strong>Failure stage:</strong> <?= Html::encode($ocrDebug['failure_stage']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($ocrDebug['tesseract_binary'])): ?>
                        <p class="small text-muted" style="margin:0 0 8px"><strong>Tesseract:</strong> <?= Html::encode($ocrDebug['tesseract_binary']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($verificationUi['comparisonRows'])): ?>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Profile Value</th>
                                        <th>OCR Value</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($verificationUi['comparisonRows'] as $row): ?>
                                        <tr class="<?= $row['result'] === 'match' ? 'table-success' : ($row['result'] === 'partial' ? 'table-warning' : 'table-danger') ?>">
                                            <td><?= Html::encode($row['label']) ?></td>
                                            <td><?= Html::encode($row['profile'] ?? '—') ?></td>
                                            <td><?= Html::encode($row['ocr'] ?? '—') ?></td>
                                            <td><?= Html::encode($row['resultLabel']) ?> (<?= (int) $row['score'] ?>%)</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <?php if ($rawOcrText !== ''): ?>
                        <details class="mb-2">
                            <summary class="small text-muted" style="cursor:pointer">View raw OCR text (before parsing)</summary>
                            <pre style="white-space:pre-wrap;font-size:11px;max-height:200px;overflow:auto;background:#f8f9fa;padding:10px;border-radius:8px;margin-top:8px"><?= Html::encode($rawOcrText) ?></pre>
                        </details>
                    <?php endif; ?>
                    <?php if (!empty($ocrDebug['parser_result'])): ?>
                        <details class="mb-2">
                            <summary class="small text-muted" style="cursor:pointer">Parser diagnostics — null fields</summary>
                            <ul class="small mb-0 mt-2">
                                <?php foreach ($ocrDebug['parser_result'] as $field => $diag): ?>
                                    <?php if (($diag['value'] ?? null) === null): ?>
                                        <li><strong><?= Html::encode($field) ?>:</strong> <?= Html::encode($diag['reason'] ?? '') ?>
                                            (<?= Html::encode(basename((string) ($diag['file'] ?? ''))) ?>:<?= (int) ($diag['line'] ?? 0) ?>)
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endif; ?>
                    <p style="margin:0 0 12px"></p>
                    <?php if (!empty($hasIdDocument)): ?>
                        <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:12px">
                            <a href="<?= Url::to(['/student/view-id-document', 'id' => $model->id]) ?>" class="document-link" target="_blank" rel="noopener">
                                <i class="fas fa-id-card"></i>
                                <span>View ID document</span>
                            </a>
                        </div>
                        <?php if ($model->id_verification_status !== \common\models\Student::ID_VERIFICATION_APPROVED): ?>
                            <?= Html::beginForm(['verify-id', 'id' => $model->id], 'post', ['style' => 'display:inline-block;margin-right:8px']) ?>
                                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Approve</button>
                            <?= Html::endForm() ?>
                            <?= Html::beginForm(['reject-id', 'id' => $model->id], 'post', ['style' => 'display:inline-block']) ?>
                                <input type="text" name="reason" class="form-control form-control-sm d-inline-block" style="width:220px" placeholder="Rejection reason (optional)">
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-times"></i> Reject</button>
                            <?= Html::endForm() ?>
                            <?= Html::beginForm(['request-reupload', 'id' => $model->id], 'post', ['style' => 'display:inline-block;margin-left:8px']) ?>
                                <input type="text" name="reason" class="form-control form-control-sm d-inline-block" style="width:220px" placeholder="Re-upload message (optional)">
                                <button type="submit" class="btn btn-outline-warning btn-sm"><i class="fas fa-rotate"></i> Request Re-upload</button>
                            <?= Html::endForm() ?>
                        <?php endif; ?>
                        <?php if ($model->id_rejection_reason): ?>
                            <p class="text-danger small mt-2 mb-0"><?= Html::encode($model->id_rejection_reason) ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="margin:0;color:#999">No student ID document uploaded.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Documents & CV -->
        <div class="col-md-12">
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon icon-orange">
                        <i class="fas fa-file-download"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-weight: 700; color: #333;">Documents</h4>
                        <p style="margin: 0; color: #999; font-size: 13px;">CV and other uploaded documents</p>
                    </div>
                </div>
                
                <div class="document-section">
                    <?php if (!empty($cvInfo['available'])): ?>
                        <div style="display:flex;flex-wrap:wrap;gap:12px">
                            <a href="<?= Url::to(['/student/view-cv', 'id' => $model->id]) ?>" class="document-link" target="_blank" rel="noopener">
                                <i class="fas fa-file-pdf"></i>
                                <span>View CV / Resume</span>
                            </a>
                            <a href="<?= Url::to(['/student/download-cv', 'id' => $model->id]) ?>" class="document-link">
                                <i class="fas fa-download"></i>
                                <span>Download CV</span>
                            </a>
                        </div>
                        <?php if (!empty($cvInfo['filename'])): ?>
                            <p style="margin:12px 0 0;color:#999;font-size:13px"><?= Html::encode($cvInfo['filename']) ?></p>
                        <?php endif; ?>
                    <?php elseif (!empty($model->cv)): ?>
                        <div style="text-align:center;padding:24px;color:#999">
                            <i class="fas fa-triangle-exclamation" style="font-size:40px;margin-bottom:12px;opacity:.6"></i>
                            <p style="margin:0">CV path is on file (<code><?= Html::encode($model->cv) ?></code>) but the uploaded file could not be found.</p>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; color: #999;">
                            <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <p style="margin: 0;">No CV uploaded yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="info-card">
        <div class="info-card-header">
            <div class="info-card-icon icon-purple">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-weight: 700; color: #333;">Quick Statistics</h4>
                <p style="margin: 0; color: #999; font-size: 13px;">Overview of student activity</p>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-file-alt" style="font-size: 28px; color: #667eea;"></i>
                <div class="stat-number">
                    <?= \common\models\Application::find()->where(['student_id' => $model->id])->count() ?>
                </div>
                <div class="stat-label">Applications</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-clock" style="font-size: 28px; color: #4facfe;"></i>
                <div class="stat-number">
                    <?= \common\models\Application::find()
                        ->where(['student_id' => $model->id, 'status' => 'pending'])
                        ->count() ?>
                </div>
                <div class="stat-label">Pending</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-check-circle" style="font-size: 28px; color: #43e97b;"></i>
                <div class="stat-number">
                    <?= \common\models\Application::find()
                        ->where(['student_id' => $model->id, 'status' => 'accepted'])
                        ->count() ?>
                </div>
                <div class="stat-label">Accepted</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-calendar-alt" style="font-size: 28px; color: #fa709a;"></i>
                <div class="stat-number">
                    <?= floor((time() - $model->user->created_at) / (60 * 60 * 24)) ?>
                </div>
                <div class="stat-label">Days Active</div>
            </div>
        </div>
    </div>
</div>
