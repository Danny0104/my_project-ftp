<?php

namespace common\services;

use common\models\Notification;
use common\models\SupportAdminPresence;
use common\models\SupportChatMessage;
use common\models\SupportConversation;
use common\models\SupportMessage;
use common\models\User;
use Yii;
use yii\web\NotFoundHttpException;

class SupportService
{
    public function submitRequest(int $userId, string $userRole, string $category, string $subject, string $body): SupportConversation
    {
        $subject = trim($subject);
        $body = trim($body);
        if ($subject === '' || $body === '') {
            throw new \InvalidArgumentException('Subject and description are required.');
        }

        $conversation = new SupportConversation([
            'user_id' => $userId,
            'user_role' => $userRole,
            'category' => $category,
            'subject' => $subject,
            'last_message_at' => time(),
        ]);
        $conversation->save(false);

        $message = new SupportMessage([
            'conversation_id' => (int) $conversation->id,
            'sender_id' => $userId,
            'receiver_id' => 0,
            'sender_role' => $userRole,
            'body' => $body,
            'is_read' => 0,
            'created_at' => time(),
        ]);
        $message->save(false);

        $this->notifyAdminsNewRequest($conversation, $message);

        return $conversation;
    }

    public function replyAsAdmin(int $conversationId, int $adminId, string $body): SupportMessage
    {
        $conversation = SupportConversation::findOne($conversationId);
        if (!$conversation) {
            throw new NotFoundHttpException('Conversation not found.');
        }

        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Message cannot be empty.');
        }

        $message = new SupportMessage([
            'conversation_id' => $conversationId,
            'sender_id' => $adminId,
            'receiver_id' => (int) $conversation->user_id,
            'sender_role' => SupportMessage::ROLE_ADMIN,
            'body' => $body,
            'is_read' => 0,
            'created_at' => time(),
        ]);
        $message->save(false);

        $conversation->last_message_at = time();
        $conversation->save(false);

        $this->markConversationReadByAdmin($conversationId, $adminId);
        $this->notifyUserSupportReply($conversation, $message);

        return $message;
    }

    public function getConversationForAdmin(int $id): SupportConversation
    {
        $conversation = SupportConversation::find()->where(['id' => $id])->with(['user', 'messages'])->one();
        if (!$conversation) {
            throw new NotFoundHttpException('Conversation not found.');
        }

        return $conversation;
    }

    public function markConversationReadByUser(int $conversationId, int $userId): void
    {
        SupportMessage::updateAll(
            ['is_read' => 1],
            [
                'and',
                ['conversation_id' => $conversationId],
                ['receiver_id' => $userId],
                ['is_read' => 0],
            ]
        );
    }

    public function markConversationReadByAdmin(int $conversationId, int $adminId): void
    {
        unset($adminId);
        SupportMessage::updateAll(
            ['is_read' => 1],
            [
                'and',
                ['conversation_id' => $conversationId],
                ['receiver_id' => 0],
                ['is_read' => 0],
            ]
        );
    }

    public function countUnreadForUser(int $userId): int
    {
        $chatUnread = (int) SupportChatMessage::find()
            ->where(['receiver_id' => $userId, 'is_read' => 0])
            ->count();

        $requestUnread = (int) SupportMessage::find()
            ->where(['receiver_id' => $userId, 'is_read' => 0])
            ->count();

        return $chatUnread + $requestUnread;
    }

    public function countUnreadForAdmin(): int
    {
        return (int) SupportMessage::find()
            ->where(['receiver_id' => 0, 'is_read' => 0])
            ->andWhere(['!=', 'sender_role', SupportMessage::ROLE_ADMIN])
            ->count()
            + (int) SupportChatMessage::find()
                ->where(['receiver_id' => 0, 'is_read' => 0])
                ->andWhere(['!=', 'sender_role', SupportMessage::ROLE_ADMIN])
                ->count();
    }

    public function sendChatMessage(int $userId, string $userRole, int $senderId, string $senderRole, string $body): SupportChatMessage
    {
        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Message cannot be empty.');
        }

        $receiverId = $senderRole === SupportMessage::ROLE_ADMIN ? $userId : 0;

        $message = new SupportChatMessage([
            'user_id' => $userId,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'sender_role' => $senderRole,
            'body' => $body,
            'is_read' => 0,
            'created_at' => time(),
        ]);
        $message->save(false);

        if ($senderRole === SupportMessage::ROLE_ADMIN) {
            $this->notifyUserChatMessage($userId, $body);
        } else {
            $this->notifyAdminsChatMessage($userId, $userRole, $body);
        }

        return $message;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pollChatMessages(int $userId, int $sinceId = 0): array
    {
        $query = SupportChatMessage::find()
            ->where(['user_id' => $userId])
            ->orderBy(['id' => SORT_ASC])
            ->limit(100);

        if ($sinceId > 0) {
            $query->andWhere(['>', 'id', $sinceId]);
        }

        return array_map([$this, 'formatChatMessage'], $query->all());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getChatHistory(int $userId, int $limit = 50): array
    {
        $messages = SupportChatMessage::find()
            ->where(['user_id' => $userId])
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->all();

        return array_map([$this, 'formatChatMessage'], array_reverse($messages));
    }

    public function markChatReadForUser(int $userId): void
    {
        SupportChatMessage::updateAll(
            ['is_read' => 1],
            ['user_id' => $userId, 'receiver_id' => $userId, 'is_read' => 0]
        );
    }

    public function markChatReadForAdmin(int $userId): void
    {
        SupportChatMessage::updateAll(
            ['is_read' => 1],
            ['user_id' => $userId, 'receiver_id' => 0, 'is_read' => 0]
        );
    }

    public function isAdminOnline(): bool
    {
        return SupportAdminPresence::isAnyAdminOnline(300);
    }

    public function formatChatMessage(SupportChatMessage $message): array
    {
        return [
            'id' => (int) $message->id,
            'body' => $message->body,
            'sender_role' => $message->sender_role,
            'is_mine' => $this->isMineChatMessage($message),
            'is_read' => (bool) $message->is_read,
            'created_at' => (int) $message->created_at,
            'time_label' => Yii::$app->formatter->asDatetime($message->created_at, 'short'),
        ];
    }

    private function isMineChatMessage(SupportChatMessage $message): bool
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return false;
        }
        if ($user instanceof User) {
            return (int) $message->sender_id === (int) $user->id
                && in_array($message->sender_role, [SupportMessage::ROLE_STUDENT, SupportMessage::ROLE_ORGANIZATION], true);
        }

        return $message->sender_role === SupportMessage::ROLE_ADMIN;
    }

    private function helpCenterUrl(): string
    {
        return Yii::$app->urlManager->createAbsoluteUrl(['/site/contact']);
    }

    private function notifyAdminsNewRequest(SupportConversation $conversation, SupportMessage $message): void
    {
        unset($message);
        Yii::info(sprintf(
            'Support request #%d from user %d: %s',
            $conversation->id,
            $conversation->user_id,
            $conversation->subject
        ), 'support');
    }

    private function notifyAdminsChatMessage(int $userId, string $userRole, string $body): void
    {
        unset($userId, $userRole, $body);
        Yii::info('Live chat message from user awaiting admin response.', 'support');
    }

    private function notifyUserSupportReply(SupportConversation $conversation, SupportMessage $message): void
    {
        Notification::createAlert(
            (int) $conversation->user_id,
            'support',
            Notification::CATEGORY_MESSAGES,
            'Admin replied to your support request',
            mb_substr($message->body, 0, 200),
            [
                'sender_type' => Notification::SENDER_TYPE_ADMIN,
                'sender_id' => (int) $message->sender_id,
                'action_url' => $this->helpCenterUrl() . '#hc-request-help',
                'action_text' => 'View in Help Center',
                'related_id' => (int) $conversation->id,
            ]
        );
    }

    private function notifyUserChatMessage(int $userId, string $body): void
    {
        Notification::createAlert(
            $userId,
            'support',
            Notification::CATEGORY_MESSAGES,
            'New message from support',
            mb_substr($body, 0, 200),
            [
                'sender_type' => Notification::SENDER_TYPE_ADMIN,
                'action_url' => $this->helpCenterUrl() . '#hc-live-chat',
                'action_text' => 'Open Live Chat',
            ]
        );
    }
}
