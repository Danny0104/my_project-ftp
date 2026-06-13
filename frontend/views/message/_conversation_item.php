<?php
/** @var array $conv */
/** @var bool $isActive */

use common\models\Organization;
use common\widgets\ProfileAvatar;
use yii\helpers\Html;

$orgMeta = [];
if (!empty($conv['avatarOrganizationId'])) {
    $org = Organization::findOne((int) $conv['avatarOrganizationId']);
    if ($org) {
        $orgMeta = [
            'orgLocation' => $org->location,
            'orgIndustry' => $org->description ? mb_substr(strip_tags((string) $org->description), 0, 80) : '',
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
$context = array_merge($context, $orgMeta);
?>
<div class="sp-conv-item msg-conv-item <?= !empty($conv['unread']) ? 'unread' : 'read' ?> <?= $isActive ? 'is-active' : '' ?>"
     data-conversation-id="<?= (int) $conv['id'] ?>"
     data-chat-conversation-id="<?= (int) $conv['conversationId'] ?>"
     data-conv-source="chat"
     data-conv-filter-tags="<?= Html::encode($conv['filterTags']) ?>"
     data-is-archived="<?= !empty($conv['isArchived']) ? '1' : '0' ?>"
     data-sender-type="<?= Html::encode($conv['peerRole']) ?>"
     data-context-json="<?= Html::encode(json_encode($context, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
     data-search-text="<?= Html::encode(strtolower($conv['title'] . ' ' . $conv['subtitle'] . ' ' . $conv['preview'])) ?>"
     role="button" tabindex="0" aria-selected="<?= $isActive ? 'true' : 'false' ?>">
    <div class="sp-conv-avatar sp-conv-avatar--organization">
        <?= ProfileAvatar::widget([
            'type' => 'organization',
            'organizationId' => $conv['avatarOrganizationId'] ?? null,
            'name' => $conv['title'] ?? '',
            'size' => 'sm',
        ]) ?>
    </div>
    <div class="sp-conv-body">
        <div class="d-flex justify-content-between gap-2">
            <h3 class="sp-conv-title"><?= Html::encode($conv['title']) ?></h3>
            <span class="sp-conv-time"><?= Html::encode($conv['time']) ?></span>
        </div>
        <?php if (!empty($conv['subtitle'])): ?>
            <p class="sp-conv-subtitle small text-muted mb-1"><?= Html::encode($conv['subtitle']) ?></p>
        <?php endif; ?>
        <p class="sp-conv-preview"><?= Html::encode($conv['preview']) ?></p>
        <span class="sp-conv-chat-badge">Live chat</span>
    </div>
    <?php if (!empty($conv['unread'])): ?>
        <span class="sp-conv-unread sp-pulse" aria-label="Unread"><?= (int) $conv['unreadCount'] > 1 ? (int) $conv['unreadCount'] : '' ?></span>
    <?php endif; ?>
</div>
