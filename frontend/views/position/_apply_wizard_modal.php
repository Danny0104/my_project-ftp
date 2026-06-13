<?php

/** @var yii\web\View $this */
/** @var common\models\Position $model */
/** @var common\models\Student $student */
/** @var common\services\EligibilityResult $eligibility */
/** @var int|null $profileCompletion */
/** @var array $profileReadiness */
/** @var array<int, array{id: string, type: string, label: string, required: bool, placeholder: string, options: string[]}> $applicationQuestions */
/** @var array $deadlineMeta */
/** @var int $matchScore */
/** @var array<int, string> $allowedFieldNames */
/** @var float|string|null $minGpaDisplay */

use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\Url;

$org = $model->organization;
$hasQuestions = $applicationQuestions !== [];
$applyUrl = Url::to(['application/apply', 'position_id' => $model->id]);
$editProfileUrl = Url::to(['profile/edit-profile']);
$applicationsUrl = Url::to(['application/index']);
$browseUrl = Url::to(['position/index']);

$requirements = array_filter([
    $model->academic_level_required ? 'Level: ' . $model->academic_level_required : null,
    $minGpaDisplay !== null && $minGpaDisplay !== '' ? 'Min GPA: ' . $minGpaDisplay : null,
    !empty($allowedFieldNames) ? 'Fields: ' . implode(', ', $allowedFieldNames) : null,
    $model->skills_required ? 'Skills: ' . $model->skills_required : null,
    $model->criteria ? trim(strip_tags((string) $model->criteria)) : null,
]);
?>

<div class="modal fade ft-modal-stack" id="pdApplyModal" tabindex="-1" aria-labelledby="pdApplyLabel" aria-hidden="true"
     data-pd-apply-wizard
     data-has-questions="<?= $hasQuestions ? '1' : '0' ?>"
     data-apply-url="<?= Html::encode($applyUrl) ?>"
     data-csrf-param="<?= Html::encode(Yii::$app->request->csrfParam) ?>"
     data-csrf-token="<?= Html::encode(Yii::$app->request->csrfToken) ?>"
     data-position-title="<?= Html::encode($model->title) ?>"
     data-org-name="<?= Html::encode($org->name ?? 'Organization') ?>"
     data-profile-ready="<?= $profileReadiness['ready'] ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content pd-modal-dark">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="pdApplyLabel">Apply to <?= Html::encode($model->title) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="pd-wizard-steps" role="list" aria-label="Application progress" data-pd-wizard-steps>
                    <span data-pd-step-key="review" class="is-current">1 Review</span>
                    <span data-pd-step-key="profile">2 Profile</span>
                    <span data-pd-step-key="questions"<?= $hasQuestions ? '' : ' hidden' ?>>3 Questions</span>
                    <span data-pd-step-key="confirm">4 Submit</span>
                </div>

                <div class="pd-wizard-alert alert alert-danger d-none" role="alert" data-pd-wizard-error></div>

                <!-- Step 1: Review opportunity -->
                <div class="pd-wizard-panel" data-pd-step-panel="review">
                    <h6 class="pd-wizard-heading">Review opportunity</h6>
                    <dl class="pd-wizard-dl">
                        <div><dt>Position</dt><dd><?= Html::encode($model->title) ?></dd></div>
                        <div><dt>Organization</dt><dd><?= Html::encode($org->name ?? 'Partner organization') ?></dd></div>
                        <div><dt>Location</dt><dd><?= Html::encode($model->location ?: 'To be confirmed') ?></dd></div>
                        <div><dt>Duration</dt><dd><?= Html::encode($model->duration ?: 'To be confirmed') ?></dd></div>
                        <div><dt>Deadline</dt><dd><?= Html::encode($deadlineMeta['label'] ?? 'Rolling') ?></dd></div>
                        <div><dt>Match score</dt><dd><?= (int) $matchScore ?>%</dd></div>
                        <div><dt>Eligibility</dt><dd><span class="text-success fw-semibold">Eligible to apply</span></dd></div>
                    </dl>
                    <?php if ($requirements !== []): ?>
                        <h6 class="pd-wizard-subheading">Requirements</h6>
                        <ul class="pd-wizard-list small mb-0">
                            <?php foreach ($requirements as $req): ?>
                                <li><?= Html::encode($req) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="small text-secondary mb-0">Review the full posting before continuing.</p>
                    <?php endif; ?>
                </div>

                <!-- Step 2: Review profile -->
                <div class="pd-wizard-panel" data-pd-step-panel="profile" hidden>
                    <h6 class="pd-wizard-heading">Review profile</h6>
                    <div class="pd-wizard-profile">
                        <div class="pd-wizard-profile-photo">
                            <?= ProfileAvatar::widget(['type' => 'student', 'student' => $student, 'size' => 'lg']) ?>
                        </div>
                        <dl class="pd-wizard-dl pd-wizard-dl--compact mb-0">
                            <div><dt>Full name</dt><dd><?= Html::encode($profileReadiness['fullName']) ?></dd></div>
                            <div><dt>University</dt><dd><?= Html::encode($profileReadiness['university']) ?></dd></div>
                            <div><dt>Registration #</dt><dd><?= Html::encode($profileReadiness['registrationNumber']) ?></dd></div>
                            <div><dt>Field of study</dt><dd><?= Html::encode($profileReadiness['fieldOfStudy']) ?></dd></div>
                            <div><dt>Skills</dt><dd><?= $profileReadiness['skills'] !== [] ? Html::encode(implode(', ', $profileReadiness['skills'])) : '—' ?></dd></div>
                            <div><dt>CV</dt><dd class="<?= $profileReadiness['cvUploaded'] ? 'text-success' : 'text-danger' ?>"><?= Html::encode($profileReadiness['cvLabel']) ?></dd></div>
                            <div><dt>Student ID</dt><dd class="<?= $profileReadiness['idDocumentUploaded'] ? 'text-success' : 'text-danger' ?>"><?= Html::encode($profileReadiness['idVerificationLabel']) ?></dd></div>
                            <?php if ($profileReadiness['profileCompletion'] !== null): ?>
                                <div><dt>Profile completion</dt><dd><?= (int) $profileReadiness['profileCompletion'] ?>%</dd></div>
                            <?php endif; ?>
                        </dl>
                    </div>
                    <div class="pd-wizard-profile-warning alert alert-warning mt-3 mb-0<?= $profileReadiness['ready'] ? ' d-none' : '' ?>" data-pd-profile-warning>
                        <strong>Please complete your profile before applying.</strong>
                        <ul class="mb-2 mt-2 small" data-pd-profile-issues>
                            <?php foreach ($profileReadiness['issues'] as $issue): ?>
                                <li><?= Html::encode($issue) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?= Html::a('Go to Edit Profile', $editProfileUrl, ['class' => 'btn btn-sm btn-warning', 'target' => '_blank', 'data-pd-edit-profile']) ?>
                    </div>
                </div>

                <!-- Step 3: Application questions -->
                <?php if ($hasQuestions): ?>
                <div class="pd-wizard-panel" data-pd-step-panel="questions" hidden>
                    <h6 class="pd-wizard-heading">Application questions</h6>
                    <p class="small text-secondary">Answer the questions below. Required fields are marked with *.</p>
                    <div class="pd-wizard-questions" data-pd-questions-form>
                        <?php foreach ($applicationQuestions as $question): ?>
                            <?php
                            $qId = $question['id'];
                            $required = $question['required'];
                            $label = $question['label'] . ($required ? ' *' : '');
                            $inputName = 'ApplicationAnswers[' . Html::encode($qId) . ']';
                            ?>
                            <div class="mb-3" data-pd-question-id="<?= Html::encode($qId) ?>" data-pd-question-required="<?= $required ? '1' : '0' ?>">
                                <label class="form-label small fw-semibold" for="pd-q-<?= Html::encode($qId) ?>"><?= Html::encode($label) ?></label>
                                <?php if ($question['type'] === 'long'): ?>
                                    <textarea class="form-control form-control-sm" id="pd-q-<?= Html::encode($qId) ?>" name="<?= $inputName ?>" rows="4" placeholder="<?= Html::encode($question['placeholder']) ?>"<?= $required ? ' required' : '' ?>></textarea>
                                <?php elseif ($question['type'] === 'choice' && $question['options'] !== []): ?>
                                    <select class="form-select form-select-sm" id="pd-q-<?= Html::encode($qId) ?>" name="<?= $inputName ?>"<?= $required ? ' required' : '' ?>>
                                        <option value="">Select an option</option>
                                        <?php foreach ($question['options'] as $option): ?>
                                            <option value="<?= Html::encode($option) ?>"><?= Html::encode($option) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($question['type'] === 'file'): ?>
                                    <input type="file" class="form-control form-control-sm" id="pd-q-<?= Html::encode($qId) ?>" name="<?= $inputName ?>" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"<?= $required ? ' required' : '' ?>>
                                    <div class="form-text">PDF, Word, or image up to 5 MB.</div>
                                <?php else: ?>
                                    <input type="text" class="form-control form-control-sm" id="pd-q-<?= Html::encode($qId) ?>" name="<?= $inputName ?>" placeholder="<?= Html::encode($question['placeholder']) ?>"<?= $required ? ' required' : '' ?>>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Step 4: Confirmation -->
                <div class="pd-wizard-panel" data-pd-step-panel="confirm" hidden>
                    <h6 class="pd-wizard-heading">Application summary</h6>
                    <dl class="pd-wizard-dl">
                        <div><dt>Position</dt><dd data-pd-summary-position><?= Html::encode($model->title) ?></dd></div>
                        <div><dt>Organization</dt><dd data-pd-summary-org><?= Html::encode($org->name ?? 'Organization') ?></dd></div>
                        <div><dt>Applicant</dt><dd><?= Html::encode($profileReadiness['fullName']) ?></dd></div>
                        <div><dt>University</dt><dd><?= Html::encode($profileReadiness['university']) ?></dd></div>
                        <div><dt>CV</dt><dd><?= $profileReadiness['cvUploaded'] ? 'Attached' : 'Missing' ?></dd></div>
                    </dl>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" value="1" id="pdApplyDeclaration" name="declaration" data-pd-declaration>
                        <label class="form-check-label small" for="pdApplyDeclaration">
                            I confirm that all information provided is accurate and truthful.
                        </label>
                    </div>
                </div>

                <!-- Success -->
                <div class="pd-wizard-panel pd-wizard-success" data-pd-step-panel="success" hidden>
                    <div class="text-center py-3">
                        <div class="pd-wizard-success-icon text-success mb-3"><i class="fas fa-circle-check fa-3x"></i></div>
                        <h5 class="mb-3">Application Submitted Successfully</h5>
                        <dl class="pd-wizard-dl pd-wizard-dl--center text-start mx-auto" style="max-width: 320px">
                            <div><dt>Position</dt><dd data-pd-success-position><?= Html::encode($model->title) ?></dd></div>
                            <div><dt>Organization</dt><dd data-pd-success-org><?= Html::encode($org->name ?? 'Organization') ?></dd></div>
                            <div><dt>Status</dt><dd>Pending Review</dd></div>
                        </dl>
                        <div class="text-start mx-auto small text-secondary" style="max-width: 360px">
                            <p class="fw-semibold text-body mb-2">Next steps</p>
                            <ul class="mb-0 ps-3">
                                <li>Organization will review your application.</li>
                                <li>Status updates will appear in Applications.</li>
                                <li>Notifications will be sent automatically.</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap gap-2 justify-content-center mt-4">
                            <?= Html::a('View Applications', $applicationsUrl, ['class' => 'btn btn-primary']) ?>
                            <?= Html::a('Continue Browsing', $browseUrl, ['class' => 'btn btn-outline-secondary']) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 flex-wrap gap-2" data-pd-wizard-footer>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-pd-wizard-cancel>Cancel</button>
                <button type="button" class="btn btn-outline-secondary" data-pd-wizard-back hidden>Back</button>
                <button type="button" class="btn btn-primary" data-pd-wizard-next>Continue</button>
                <button type="button" class="btn btn-primary" data-pd-wizard-submit hidden>
                    <i class="fas fa-paper-plane me-1"></i> Submit Application
                </button>
            </div>
        </div>
    </div>
</div>
