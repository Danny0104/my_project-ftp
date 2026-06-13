<?php

use common\models\Application;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

/** @var Application $app */
/** @var bool $isActive */

$isActive = !empty($isActive);

$studentName = $app->student && $app->student->user
    ? ($app->student->user->username ?? 'Student')
    : ('Student #' . (int) $app->student_id);
$roleTitle = $app->position->title ?? 'Internship';
$statusLabel = Application::getStatusOptions()[$app->status] ?? $app->status;
$isInterview = in_array($app->status, [
    Application::STATUS_ORG_APPROVED,
    Application::STATUS_UNIVERSITY_APPROVED,
], true);
$gpa = $app->student->gpa ?? null;
$skills = $app->student->skills ?? '';
$previewLine = $statusLabel . ' · ' . ($skills ?: 'Skills not listed');

$context = [
    'source' => 'application',
    'applicationId' => (int) $app->id,
    'studentName' => $studentName,
    'roleTitle' => $roleTitle,
    'status' => $statusLabel,
    'statusKey' => $app->status,
    'gpa' => $gpa,
    'skills' => $skills,
    'field' => $app->student->field_of_study ?? '',
    'applied' => date('M d, Y', $app->created_at),
    'viewUrl' => Url::to(['application/view', 'id' => $app->id]),
    'atsUrl' => Url::to(['application/index']),
    'message' => 'Open to view application details and chat live.',
];
?>

<div class="sp-conv-item msg-conv-item org-msg-conv org-msg-conv--applicant read applicant-notification<?= $isActive ? ' is-active' : '' ?>"
     data-conversation-id="app-<?= (int) $app->id ?>"
     data-application-id="<?= (int) $app->id ?>"
     data-conv-source="application"
     data-conv-filter-tags="applicants <?= $isInterview ? 'interviews' : 'active' ?>"
     data-sender-type="student"
     data-action-url="<?= Html::encode(Url::to(['application/view', 'id' => $app->id])) ?>"
     data-action-text="View application"
     data-context-json="<?= Html::encode(json_encode($context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
     data-search-text="<?= Html::encode(strtolower($studentName . ' ' . $roleTitle . ' ' . $statusLabel)) ?>"
     role="button" tabindex="0" aria-selected="<?= $isActive ? 'true' : 'false' ?>">
    <div class="sp-conv-avatar org-msg-avatar org-msg-avatar--student">
        <?= ProfileAvatar::widget(['type' => 'student', 'student' => $app->student ?? null, 'size' => 'sm']) ?>
    </div>
    <div class="sp-conv-body min-w-0">
        <div class="org-msg-conv-head">
            <h3 class="sp-conv-title org-msg-truncate"><?= Html::encode($studentName) ?></h3>
            <span class="sp-conv-time"><?= Yii::$app->formatter->asRelativeTime($app->created_at) ?></span>
        </div>
        <p class="org-msg-role org-msg-truncate"><?= Html::encode($roleTitle) ?></p>
        <p class="sp-conv-preview org-msg-truncate"><?= Html::encode($previewLine) ?></p>
    </div>
    <span class="org-msg-priority org-msg-priority--<?= $isInterview ? 'interview' : 'normal' ?>"><?= $isInterview ? 'Interview' : 'Live chat' ?></span>
</div>
