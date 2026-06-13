<?php
/** @var array $conv */
/** @var bool $isActive */

use common\models\Student;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;

$studentMeta = [];
if (!empty($conv['avatarStudentId'])) {
    $student = Student::findOne((int) $conv['avatarStudentId']);
    if ($student) {
        $studentMeta = [
            'field' => $student->field_of_study,
            'gpa' => $student->gpa,
            'skills' => $student->skills,
        ];
    }
}

$context = [
    'source' => 'chat',
    'id' => (int) $conv['id'],
    'conversationId' => (int) $conv['conversationId'],
    'applicationId' => $conv['applicationId'],
    'title' => $conv['title'],
    'subtitle' => $conv['subtitle'],
    'preview' => $conv['preview'],
    'time' => $conv['time'],
    'chatEnabled' => true,
    'peerRole' => $conv['peerRole'],
];
$context = array_merge($context, $studentMeta);
?>
<div class="sp-conv-item msg-conv-item org-msg-conv <?= !empty($conv['unread']) ? 'unread' : 'read' ?> <?= $isActive ? 'is-active' : '' ?>"
     data-conversation-id="<?= (int) $conv['id'] ?>"
     data-chat-conversation-id="<?= (int) $conv['conversationId'] ?>"
     data-conv-source="chat"
     data-conv-filter-tags="<?= Html::encode($conv['filterTags']) ?>"
     data-is-archived="<?= !empty($conv['isArchived']) ? '1' : '0' ?>"
     data-sender-type="student"
     data-context-json="<?= Html::encode(json_encode($context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
     data-search-text="<?= Html::encode(strtolower($conv['title'] . ' ' . $conv['subtitle'] . ' ' . $conv['preview'])) ?>"
     role="button" tabindex="0" aria-selected="<?= $isActive ? 'true' : 'false' ?>">
    <div class="sp-conv-avatar sp-conv-avatar--student">
        <?= ProfileAvatar::widget([
            'type' => 'student',
            'studentId' => $conv['avatarStudentId'] ?? null,
            'name' => $conv['title'] ?? '',
            'size' => 'sm',
        ]) ?>
    </div>
    <div class="sp-conv-body min-w-0">
        <div class="org-msg-conv-head">
            <h3 class="sp-conv-title org-msg-truncate"><?= Html::encode($conv['title']) ?></h3>
            <span class="sp-conv-time"><?= Html::encode($conv['time']) ?></span>
        </div>
        <?php if (!empty($conv['subtitle'])): ?>
            <p class="sp-conv-subtitle small text-muted mb-1 org-msg-truncate"><?= Html::encode($conv['subtitle']) ?></p>
        <?php endif; ?>
        <p class="sp-conv-preview org-msg-truncate"><?= Html::encode($conv['preview']) ?></p>
    </div>
    <?php if (!empty($conv['unread'])): ?>
        <span class="sp-conv-unread sp-pulse"></span>
    <?php endif; ?>
</div>
