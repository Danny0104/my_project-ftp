<?php
/**
 * Shared messaging hub JSON config — live two-way chat.
 *
 * @var string $role organization|student
 * @var string $markReadUrl
 * @var string $csrfParam
 * @var string $csrfToken
 * @var bool $asJsonScript output plain JSON for script tag (optional)
 */

use yii\helpers\Json;
use yii\helpers\Url;

$pollMs = (int) (Yii::$app->params['chat.pollIntervalMs'] ?? 2500);

$config = [
    'role' => $role,
    'currentUserId' => (int) Yii::$app->user->id,
    'capabilities' => [
        'realtime' => true,
        'typingIndicators' => true,
        'onlinePresence' => true,
        'readReceipts' => true,
        'deliveryReceipts' => true,
        'attachments' => true,
        'twoWayChat' => true,
        'optimisticSend' => true,
        'pollUnreadMs' => 30000,
        'pollChatMs' => $pollMs,
    ],
    'csrfParam' => $csrfParam,
    'csrfToken' => $csrfToken,
    'markReadUrl' => $markReadUrl,
    'unreadCountUrl' => Url::to(['message/unread-count']),
    'notificationUnreadUrl' => Url::to(['notification/unread-count']),
    'filterAttr' => $role === 'organization' ? 'data-org-filter' : 'data-conv-filter',
    'toastStackId' => $role === 'organization' ? 'orgMsgToastStack' : 'msgToastStack',
    'websocketUrl' => Yii::$app->params['chat.websocketUrl'] ?? null,
    'chat' => [
        'ensureUrl' => Url::to(['message/ensure'], true),
        'threadUrl' => Url::to(['message/thread'], true),
        'sendUrl' => Url::to(['message/send'], true),
        'pollUrl' => Url::to(['message/poll'], true),
        'typingUrl' => Url::to(['message/typing'], true),
        'heartbeatUrl' => Url::to(['message/heartbeat'], true),
        'markReadUrl' => Url::to(['message/mark-read'], true),
        'markUnreadUrl' => Url::to(['message/mark-unread'], true),
        'archiveUrl' => Url::to(['message/archive'], true),
    ],
];

echo !empty($asJsonScript) ? Json::encode($config) : Json::htmlEncode($config);
