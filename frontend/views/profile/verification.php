<?php

use common\models\Student;
use frontend\assets\StudentSettingsAsset;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var Student $model */
/** @var array{ready: bool, missing: string[], profileSummary: array<string, string|null>} $readiness */
/** @var array<string, mixed> $verificationUi */

StudentSettingsAsset::register($this);

$this->title = 'Verification & Security';

$user = $model->user;
$statusKey = $verificationUi['statusKey'] ?? 'none';
$score = $verificationUi['verificationScore'] ?? null;
$profileSummary = $readiness['profileSummary'] ?? [];

$statusBadgeClass = match ($statusKey) {
    'auto_verified', 'verified' => 'sp-vc-badge--success',
    'rejected' => 'sp-vc-badge--danger',
    'pending_review', 'fraud' => 'sp-vc-badge--warning',
    default => 'sp-vc-badge--muted',
};

$statusLabel = match ($statusKey) {
    'auto_verified', 'verified' => 'Verified',
    'rejected' => 'Rejected',
    'pending_review' => 'Pending Review',
    'fraud' => 'Under Review',
    default => 'Not Verified',
};
?>

<div class="sp-set sp-set--verification">
    <header class="sp-set-header">
        <div>
            <nav class="sp-vc-breadcrumb" aria-label="Breadcrumb">
                <?= Html::a('Settings', ['settings'], ['class' => 'sp-vc-breadcrumb__link']) ?>
                <span aria-hidden="true">/</span>
                <span>Verification &amp; Security</span>
            </nav>
            <h1>Verification &amp; Security</h1>
            <p>Verify your student identity against your saved profile information</p>
        </div>
        <div class="sp-set-header-links">
            <?= Html::a('<i class="fas fa-pen"></i> Edit Profile', ['edit-profile', '#' => 'section-academic'], ['class' => 'sp-set-btn sp-set-btn--ghost']) ?>
            <?= Html::a('<i class="fas fa-gear"></i> Settings', ['settings'], ['class' => 'sp-set-btn sp-set-btn--ghost']) ?>
        </div>
    </header>

    <?php if (!$readiness['ready']): ?>
        <div class="sp-vc-readiness sp-vc-readiness--blocked" id="spVcReadinessBanner" role="alert">
            <div class="sp-vc-readiness__icon"><i class="fas fa-circle-exclamation"></i></div>
            <div>
                <strong>Complete and save your profile before verifying</strong>
                <p>Verification compares your student ID against saved profile data. Missing fields:
                    <?= Html::encode(implode(', ', $readiness['missing'])) ?>.</p>
                <?= Html::a('Go to Edit Profile', ['edit-profile', '#' => 'section-academic'], ['class' => 'sp-set-btn sp-set-btn--primary sp-set-btn--sm']) ?>
            </div>
        </div>
    <?php else: ?>
        <div class="sp-vc-readiness sp-vc-readiness--ready" id="spVcReadinessBanner">
            <div class="sp-vc-readiness__icon"><i class="fas fa-circle-check"></i></div>
            <div>
                <strong>Profile ready for verification</strong>
                <p>Your saved academic information will be used for matching.</p>
            </div>
        </div>
    <?php endif; ?>

    <div class="sp-vc-layout">
        <aside class="sp-vc-sidebar" aria-label="Verification summary">
            <div class="sp-vc-card">
                <h2 class="sp-vc-card__title">Verification Status</h2>
                <span class="sp-vc-badge <?= $statusBadgeClass ?>" id="spVcStatusBadge"><?= Html::encode($statusLabel) ?></span>
                <div class="sp-vc-score" id="spVcScoreBlock"<?= $score === null ? ' hidden' : '' ?>>
                    <div class="sp-vc-score__head">
                        <span>Verification Score</span>
                        <strong id="spVcScoreValue"><?= $score !== null ? (int) $score . '%' : '—' ?></strong>
                    </div>
                    <div class="sp-vc-score__bar" role="progressbar" aria-valuenow="<?= $score !== null ? (int) $score : 0 ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="sp-vc-score__fill" id="spVcScoreFill" style="width:<?= $score !== null ? min(100, max(0, (int) $score)) : 0 ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="sp-vc-card">
                <h2 class="sp-vc-card__title">Saved Profile Values</h2>
                <p class="sp-vc-card__hint">Used for matching after upload</p>
                <dl class="sp-vc-profile-dl">
                    <div><dt>Name</dt><dd id="spVcProfileName"><?= Html::encode($profileSummary['name'] ?? '—') ?></dd></div>
                    <div><dt>Registration #</dt><dd id="spVcProfileReg"><?= Html::encode($profileSummary['registrationNumber'] ?? '—') ?></dd></div>
                    <div><dt>University</dt><dd id="spVcProfileUni"><?= Html::encode($profileSummary['university'] ?? '—') ?></dd></div>
                    <div><dt>Program</dt><dd id="spVcProfileProgram"><?= Html::encode($profileSummary['program'] ?? '—') ?></dd></div>
                    <div><dt>Field of Study</dt><dd id="spVcProfileField"><?= Html::encode($profileSummary['fieldOfStudy'] ?? '—') ?></dd></div>
                </dl>
                <?= Html::a('<i class="fas fa-pen"></i> Edit profile', ['edit-profile', '#' => 'section-academic'], ['class' => 'sp-set-btn sp-set-btn--ghost sp-set-btn--sm w-100']) ?>
            </div>

            <div class="sp-vc-card">
                <h2 class="sp-vc-card__title">Requirements</h2>
                <ul class="sp-vc-requirements" id="spVcRequirements">
                    <?php
                    $reqChecks = $verificationUi['checks'] ?? [];
                    $requirements = [
                        ['key' => 'name', 'label' => 'Name matches profile'],
                        ['key' => 'registration', 'label' => 'Registration number matches profile'],
                        ['key' => 'university', 'label' => 'University matches profile'],
                        ['key' => 'program', 'label' => 'Academic program matches profile'],
                        ['key' => 'field_of_study', 'label' => 'Field of study matches profile'],
                    ];
                    foreach ($requirements as $req):
                        $pass = !empty($reqChecks[$req['key']]);
                        $hasResult = $model->hasIdDocument() && $score !== null;
                        ?>
                        <li class="<?= $hasResult ? ($pass ? 'is-pass' : 'is-fail') : 'is-pending' ?>" data-req="<?= Html::encode($req['key']) ?>">
                            <span class="sp-vc-requirements__icon"><?= $hasResult ? ($pass ? '✓' : '✗') : '○' ?></span>
                            <?= Html::encode($req['label']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="sp-vc-card" id="spVcTimelineCard"<?= empty($verificationUi['timeline']) ? ' hidden' : '' ?>>
                <h2 class="sp-vc-card__title">Activity Timeline</h2>
                <ol class="sp-vc-timeline" id="spVcTimeline">
                    <?php foreach ($verificationUi['timeline'] as $event): ?>
                        <li class="sp-vc-timeline__item sp-vc-timeline__item--<?= Html::encode($event['type']) ?>">
                            <span class="sp-vc-timeline__dot"></span>
                            <div>
                                <strong><?= Html::encode($event['label']) ?></strong>
                                <?php if (!empty($event['at'])): ?>
                                    <span class="sp-vc-timeline__time"><?= Html::encode($event['at']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($event['meta'])): ?>
                                    <span class="sp-vc-timeline__meta"><?= Html::encode($event['meta']) ?></span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </aside>

        <main class="sp-vc-main">
            <section class="sp-vc-section">
                <div class="sp-vc-section__head">
                    <h2><i class="fas fa-id-card"></i> Student Identity Verification</h2>
                    <p>Upload your university student ID card for automatic verification</p>
                </div>

                <?= $this->render('_verification_center', [
                    'model' => $model,
                    'readiness' => $readiness,
                    'verificationUi' => $verificationUi,
                ]) ?>
            </section>

            <section class="sp-vc-section" id="spVcOcrSection"<?= !$model->hasIdDocument() || $score === null ? ' hidden' : '' ?>>
                <div class="sp-vc-section__head">
                    <h2><i class="fas fa-file-lines"></i> OCR Result</h2>
                    <p>Information extracted from your uploaded ID</p>
                </div>
                <div class="sp-id-verify-extracted sp-vc-ocr-panel" id="spIdVerifyExtracted">
                    <?php if ($verificationUi['ocrConfidence'] !== null): ?>
                        <div class="sp-vc-ocr-confidence">
                            OCR Confidence: <strong id="spVcOcrConfidence"><?= (int) $verificationUi['ocrConfidence'] ?>%</strong>
                            <?php if (!empty($verificationUi['lowOcrConfidence'])): ?>
                                <span class="sp-vc-ocr-low-badge" id="spVcOcrLowBadge">Low OCR Confidence — manual review</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <dl class="sp-id-verify-extracted__list">
                        <div><dt>Name</dt><dd id="spIdExtractedName"><?= Html::encode($verificationUi['extracted']['name'] ?? '—') ?></dd></div>
                        <div><dt>Registration Number</dt><dd id="spIdExtractedReg"><?= Html::encode($verificationUi['extracted']['registrationNumber'] ?? '—') ?></dd></div>
                        <div><dt>University</dt><dd id="spIdExtractedUniversity"><?= Html::encode($verificationUi['extracted']['university'] ?? '—') ?></dd></div>
                        <div><dt>Program</dt><dd id="spIdExtractedProgram"><?= Html::encode($verificationUi['extracted']['program'] ?? '—') ?></dd></div>
                        <div><dt>Field of Study</dt><dd id="spIdExtractedField"><?= Html::encode($verificationUi['extracted']['fieldOfStudy'] ?? '—') ?></dd></div>
                        <div><dt>Expiry Date</dt><dd id="spIdExtractedExpiry"><?= Html::encode($verificationUi['extracted']['expiryDate'] ?? '—') ?></dd></div>
                    </dl>
                    <?php
                    $rawOcr = $verificationUi['rawOcrText'] ?? '';
                    $ocrDebug = $verificationUi['ocrDebug'] ?? [];
                    ?>
                    <details class="sp-vc-ocr-raw" id="spVcOcrRawDetails"<?= $rawOcr === '' ? ' hidden' : '' ?>>
                        <summary>Raw OCR text (before parsing)</summary>
                        <pre class="sp-vc-ocr-raw__pre" id="spVcOcrRawText"><?= Html::encode($rawOcr !== '' ? $rawOcr : "—") ?></pre>
                    </details>
                    <?php if (!empty($ocrDebug['parser_result'])): ?>
                        <details class="sp-vc-ocr-debug small mt-2">
                            <summary>OCR debug — why fields are empty</summary>
                            <ul class="list-unstyled mb-0 mt-2">
                                <?php foreach ($ocrDebug['parser_result'] as $field => $diag): ?>
                                    <?php if (($diag['value'] ?? null) === null): ?>
                                        <li><strong><?= Html::encode($field) ?>:</strong> <?= Html::encode($diag['reason'] ?? '') ?>
                                            <span class="text-muted">(<?= Html::encode(basename((string) ($diag['file'] ?? ''))) ?>:<?= (int) ($diag['line'] ?? 0) ?>)</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endif; ?>
                </div>
            </section>

            <section class="sp-vc-section" id="spVcCompareSection"<?= empty($verificationUi['comparisonRows']) ? ' hidden' : '' ?>>
                <div class="sp-vc-section__head">
                    <h2><i class="fas fa-table-columns"></i> Matching Results</h2>
                    <p>Side-by-side comparison of profile values vs OCR extraction</p>
                </div>
                <div class="table-responsive">
                    <table class="sp-vc-compare-table" id="spVcCompareTable">
                        <thead>
                            <tr>
                                <th scope="col">Field</th>
                                <th scope="col">Profile Value</th>
                                <th scope="col">OCR Value</th>
                                <th scope="col">Result</th>
                            </tr>
                        </thead>
                        <tbody id="spVcCompareBody">
                            <?php foreach ($verificationUi['comparisonRows'] as $row): ?>
                                <?php
                                $resultClass = match ($row['result']) {
                                    'match' => 'is-match',
                                    'partial' => 'is-partial',
                                    default => 'is-mismatch',
                                };
                                $resultIcon = match ($row['result']) {
                                    'match' => '✓',
                                    'partial' => '⚠',
                                    default => '✗',
                                };
                                ?>
                                <tr class="<?= $resultClass ?>" data-field="<?= Html::encode($row['key']) ?>">
                                    <th scope="row"><?= Html::encode($row['label']) ?></th>
                                    <td><?= Html::encode($row['profile'] ?? '—') ?></td>
                                    <td><?= Html::encode($row['ocr'] ?? '—') ?></td>
                                    <td><span class="sp-vc-result-badge sp-vc-result-badge--<?= Html::encode($row['result']) ?>"><?= $resultIcon ?> <?= Html::encode($row['resultLabel']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <ul class="sp-id-verify-feedback list-unstyled small mb-0 mt-3" id="spIdVerifyFeedback">
                    <?php foreach ($verificationUi['fieldFeedback'] ?? [] as $line): ?>
                        <li class="sp-id-verify-feedback__item">⚠ <?= Html::encode($line) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </main>
    </div>
</div>

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
});
</script>
<?php endif; ?>
