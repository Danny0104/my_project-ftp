<?php

namespace common\services;

use common\models\Application;
use common\models\ChatConversation;
use common\models\ChatMessage;
use common\models\ChatMessageStatus;
use common\models\ChatParticipant;
use common\models\ChatPresence;
use common\models\ChatTyping;
use common\models\Notification;
use common\models\Organization;
use common\models\Student;
use common\models\User;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class ChatService
{
    public function assertParticipant(ChatConversation $conversation, int $userId): ChatParticipant
    {
        $participant = ChatParticipant::findOne([
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
        ]);
        if (!$participant) {
            throw new ForbiddenHttpException('You are not a participant in this conversation.');
        }
        return $participant;
    }

    public function getConversationForUser(int $conversationId, int $userId): ChatConversation
    {
        $conversation = ChatConversation::findOne($conversationId);
        if (!$conversation) {
            throw new NotFoundHttpException('Conversation not found.');
        }
        $this->assertParticipant($conversation, $userId);
        return $conversation;
    }

    public function ensureForApplicationAsStudent(int $applicationId, int $studentUserId): ChatConversation
    {
        $application = Application::find()
            ->where(['id' => $applicationId, 'user_id' => $studentUserId])
            ->with(['position'])
            ->one();
        if (!$application) {
            throw new NotFoundHttpException('Application not found.');
        }
        $orgId = (int) ($application->position->organization_id ?? 0);
        if (!$orgId) {
            throw new NotFoundHttpException('Organization not found.');
        }
        return $this->ensureConversation(
            $orgId,
            $studentUserId,
            $applicationId,
            $application->position->title ?? 'Application'
        );
    }

    public function ensureForApplication(int $applicationId, int $orgUserId): ChatConversation
    {
        $organization = Organization::findOne(['user_id' => $orgUserId]);
        if (!$organization) {
            throw new ForbiddenHttpException('Organization profile required.');
        }

        $application = Application::find()
            ->alias('a')
            ->innerJoin(['p' => \common\models\Position::tableName()], 'p.id = a.position_id')
            ->where(['a.id' => $applicationId, 'p.organization_id' => $organization->id])
            ->with(['student.user', 'position'])
            ->one();

        if (!$application) {
            throw new NotFoundHttpException('Application not found.');
        }

        $studentUserId = (int) $application->user_id;
        return $this->ensureConversation(
            (int) $organization->id,
            $studentUserId,
            $applicationId,
            $application->position->title ?? 'Application'
        );
    }

    public function ensureForNotification(int $notificationId, int $userId): ChatConversation
    {
        $notification = Notification::findOne(['id' => $notificationId, 'user_id' => $userId]);
        if (!$notification) {
            throw new NotFoundHttpException('Notification not found.');
        }

        $conversationId = $this->parseConversationIdFromUrl($notification->action_url ?? '');
        if ($conversationId > 0) {
            return $this->getConversationForUser($conversationId, $userId);
        }

        if ($notification->sender_type === Notification::SENDER_TYPE_ORGANIZATION) {
            $organization = Organization::findOne((int) $notification->sender_id);
            if (!$organization) {
                throw new NotFoundHttpException('Organization not found.');
            }

            return $this->ensureConversation(
                (int) $organization->id,
                $userId,
                null,
                $notification->title
            );
        }

        $user = User::findOne($userId);
        if ($user && $user->role === 'organization' && $notification->sender_type === Notification::SENDER_TYPE_SYSTEM) {
            $organization = Organization::findOne(['user_id' => $userId]);
            if ($organization) {
                $conversation = ChatConversation::find()
                    ->where(['organization_id' => $organization->id])
                    ->orderBy(['last_message_at' => SORT_DESC, 'id' => SORT_DESC])
                    ->one();
                if ($conversation) {
                    $this->assertParticipant($conversation, $userId);
                    return $conversation;
                }
            }
        }

        throw new ForbiddenHttpException('This notification cannot be opened as a chat thread.');
    }

    private function parseConversationIdFromUrl(?string $url): int
    {
        if (!$url || !preg_match('/[?&]chat=(\d+)/', $url, $matches)) {
            return 0;
        }
        return (int) $matches[1];
    }

    public function ensureConversation(
        int $organizationId,
        int $studentUserId,
        ?int $applicationId,
        ?string $title = null
    ): ChatConversation {
        $query = ChatConversation::find()
            ->where([
                'organization_id' => $organizationId,
                'student_user_id' => $studentUserId,
            ]);

        if ($applicationId) {
            $query->andWhere(['application_id' => $applicationId]);
        }

        $conversation = $query->one();
        if (!$conversation && $applicationId) {
            $conversation = ChatConversation::find()
                ->where([
                    'organization_id' => $organizationId,
                    'student_user_id' => $studentUserId,
                    'application_id' => null,
                ])
                ->one();
            if ($conversation) {
                $conversation->application_id = $applicationId;
                if ($title) {
                    $conversation->title = $title;
                }
                $conversation->save(false);
            }
        }
        if ($conversation) {
            return $conversation;
        }

        $org = Organization::findOne($organizationId);
        $orgUserId = $org ? (int) $org->user_id : 0;

        $conversation = new ChatConversation([
            'application_id' => $applicationId,
            'organization_id' => $organizationId,
            'student_user_id' => $studentUserId,
            'title' => $title ?: 'Conversation',
        ]);
        $conversation->save(false);

        $this->addParticipant($conversation->id, $studentUserId, ChatParticipant::ROLE_STUDENT);
        if ($orgUserId > 0) {
            $this->addParticipant($conversation->id, $orgUserId, ChatParticipant::ROLE_ORGANIZATION);
        }

        return $conversation;
    }

    private function addParticipant(int $conversationId, int $userId, string $role): void
    {
        if (ChatParticipant::findOne(['conversation_id' => $conversationId, 'user_id' => $userId])) {
            return;
        }
        $p = new ChatParticipant([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'role' => $role,
            'created_at' => time(),
        ]);
        $p->save(false);
    }

    /**
     * @return array{messages: array, hasMore: bool}
     */
    public function getMessages(int $conversationId, int $userId, ?int $beforeId = null, int $limit = 40): array
    {
        $conversation = $this->getConversationForUser($conversationId, $userId);
        $query = ChatMessage::find()
            ->where(['conversation_id' => $conversation->id])
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit + 1);

        if ($beforeId) {
            $query->andWhere(['<', 'id', $beforeId]);
        }

        $rows = array_reverse($query->all());
        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            array_shift($rows);
        }

        $messages = [];
        foreach ($rows as $msg) {
            $messages[] = $this->serializeMessage($msg, $userId);
        }

        $this->markDeliveredAndRead($conversation, $userId, $rows);

        return ['messages' => $messages, 'hasMore' => $hasMore];
    }

    public function sendMessage(
        int $conversationId,
        int $senderUserId,
        string $body,
        ?UploadedFile $attachment = null
    ): array {
        $body = trim($body);
        if ($body === '' && !$attachment) {
            throw new \InvalidArgumentException('Message cannot be empty.');
        }

        $conversation = $this->getConversationForUser($conversationId, $senderUserId);

        $message = new ChatMessage([
            'conversation_id' => $conversation->id,
            'sender_user_id' => $senderUserId,
            'body' => $body !== '' ? $body : ($attachment ? '[Attachment]' : ''),
        ]);

        if ($attachment) {
            $this->storeAttachment($message, $attachment);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $message->save(false);
            $conversation->last_message_id = $message->id;
            $conversation->last_message_at = $message->created_at;
            $conversation->save(false);

            foreach (ChatParticipant::find()->where(['conversation_id' => $conversation->id])->all() as $p) {
                if ((int) $p->user_id === $senderUserId) {
                    continue;
                }
                $status = new ChatMessageStatus([
                    'message_id' => $message->id,
                    'user_id' => (int) $p->user_id,
                    'delivered_at' => time(),
                ]);
                $status->save(false);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        ChatTyping::clearTyping($conversation->id, $senderUserId);

        $payload = $this->serializeMessage($message, $senderUserId);
        $this->notifyRecipient($conversation, $message, $senderUserId);
        ChatRealtimeBroadcaster::emit($conversation->id, 'message_sent', $payload);
        foreach (ChatParticipant::find()->where(['conversation_id' => $conversation->id])->all() as $p) {
            if ((int) $p->user_id !== $senderUserId) {
                ChatRealtimeBroadcaster::emitUser((int) $p->user_id, 'message_received', $payload);
            }
        }

        return $payload;
    }

    private function storeAttachment(ChatMessage $message, UploadedFile $file): void
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        if (!in_array($file->type, $allowed, true)) {
            throw new \InvalidArgumentException('File type not allowed.');
        }
        if ($file->size > 8 * 1024 * 1024) {
            throw new \InvalidArgumentException('File too large (max 8MB).');
        }

        $dir = Yii::getAlias('@frontend/web/uploads/chat');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = 'chat_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->baseName) . '.' . $file->extension;
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!$file->saveAs($path)) {
            throw new \RuntimeException('Failed to save attachment.');
        }
        $message->attachment_path = 'uploads/chat/' . $name;
        $message->attachment_name = $file->name;
        $message->attachment_mime = $file->type;
    }

    private function notifyRecipient(ChatConversation $conversation, ChatMessage $message, int $senderUserId): void
    {
        $messagesUrl = Yii::$app->urlManager->createUrl([
            'message/index',
            'conversation_id' => $conversation->id,
        ]);

        foreach (ChatParticipant::find()->where(['conversation_id' => $conversation->id])->all() as $p) {
            if ((int) $p->user_id === $senderUserId) {
                continue;
            }
            $recipientId = (int) $p->user_id;
            $recipient = User::findOne($recipientId);
            if (!$recipient) {
                continue;
            }

            $sender = User::findOne($senderUserId);
            $senderLabel = $this->resolveSenderLabel($conversation, $sender, $recipient);

            $title = 'New message received';
            $alertBody = $senderLabel . ' sent you a message. Open Messages to reply.';
            $meta = [
                'notification_type' => Notification::TYPE_NEW_MESSAGE,
                'category' => Notification::CATEGORY_MESSAGES,
                'priority' => Notification::PRIORITY_NORMAL,
                'conversation_id' => (int) $conversation->id,
                'related_id' => (int) ($conversation->application_id ?? 0) ?: null,
            ];

            $existing = Notification::find()
                ->where([
                    'user_id' => $recipientId,
                    'conversation_id' => $conversation->id,
                    'is_read' => 0,
                    'is_archived' => 0,
                ])
                ->andWhere(['notification_type' => Notification::TYPE_NEW_MESSAGE])
                ->orderBy(['id' => SORT_DESC])
                ->one();

            if ($existing) {
                $existing->title = $title;
                $existing->message = $alertBody;
                $existing->action_url = $messagesUrl;
                $existing->action_text = 'Open Messages';
                $existing->updated_at = time();
                $existing->save(false);
                continue;
            }

            if ($recipient->role === 'student') {
                Notification::createFromOrganization(
                    $recipientId,
                    $title,
                    $alertBody,
                    (int) $conversation->organization_id,
                    $messagesUrl,
                    'Open Messages',
                    $meta
                );
            } else {
                Notification::createSystemNotification(
                    $recipientId,
                    $title,
                    $alertBody,
                    $messagesUrl,
                    'Open Messages',
                    $meta
                );
            }
        }
    }

    private function resolveSenderLabel(ChatConversation $conversation, ?User $sender, User $recipient): string
    {
        if ($sender && $sender->role === 'organization') {
            $org = $conversation->organization ?: Organization::findOne($conversation->organization_id);
            return $org ? $org->name : 'An organization';
        }
        if ($sender && $sender->role === 'student') {
            $student = Student::findOne(['user_id' => $sender->id]);
            if ($student && $student->user) {
                return trim($student->user->username) ?: 'A student';
            }
            return 'A student';
        }
        return 'Someone';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listConversationsForUser(int $userId): array
    {
        $participants = ChatParticipant::find()
            ->alias('cp')
            ->innerJoin(['c' => ChatConversation::tableName()], 'c.id = cp.conversation_id')
            ->where(['cp.user_id' => $userId])
            ->with([
                'conversation.organization',
                'conversation.studentUser',
                'conversation.application.position',
                'conversation.lastMessage',
            ])
            ->orderBy(['c.last_message_at' => SORT_DESC, 'c.id' => SORT_DESC])
            ->all();

        $items = [];
        foreach ($participants as $participant) {
            $conversation = $participant->conversation;
            if (!$conversation) {
                continue;
            }
            $items[] = $this->serializeConversationListItem($conversation, $userId, $participant);
        }

        return $items;
    }

    public function countUnreadForUser(int $userId): int
    {
        return (int) ChatMessage::find()
            ->alias('m')
            ->innerJoin(
                ['p' => ChatParticipant::tableName()],
                'p.conversation_id = m.conversation_id AND p.user_id = :viewerId',
                [':viewerId' => $userId]
            )
            ->where(['>', 'm.id', new \yii\db\Expression('COALESCE(p.last_read_message_id, 0)')])
            ->andWhere(['!=', 'm.sender_user_id', $userId])
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeConversationListItem(ChatConversation $conversation, int $userId, ChatParticipant $participant): array
    {
        $viewer = User::findOne($userId);
        $lastRead = (int) ($participant->last_read_message_id ?? 0);
        $unread = (int) ChatMessage::find()
            ->where(['conversation_id' => $conversation->id])
            ->andWhere(['>', 'id', $lastRead])
            ->andWhere(['!=', 'sender_user_id', $userId])
            ->count();

        $lastMessage = $conversation->lastMessage;
        if (!$lastMessage && $conversation->last_message_id) {
            $lastMessage = ChatMessage::findOne($conversation->last_message_id);
        }

        $title = $conversation->title ?: 'Conversation';
        $subtitle = '';
        $avatarLabel = '?';
        $avatarUrl = null;
        $avatarOrganizationId = null;
        $avatarStudentId = null;
        $filterTags = 'inbox';
        $peerRole = 'organization';
        $imageService = new ProfileImageService();

        if ($viewer && $viewer->role === 'student') {
            $org = $conversation->organization;
            $title = $org ? $org->name : $title;
            $subtitle = $conversation->application && $conversation->application->position
                ? $conversation->application->position->title
                : ($conversation->title ?: 'Internship conversation');
            $avatarLabel = $org ? strtoupper(substr(preg_replace('/\s+/', '', $org->name), 0, 2)) : 'OR';
            $avatarOrganizationId = $org ? (int) $org->id : null;
            $avatarUrl = $imageService->organizationLogoUrl($org, 'sm');
            $filterTags = 'inbox organizations';
            $peerRole = 'organization';
        } elseif ($viewer && $viewer->role === 'organization') {
            $studentUser = $conversation->studentUser;
            $student = $studentUser ? Student::findOne(['user_id' => $studentUser->id]) : null;
            $title = $studentUser ? ($studentUser->username ?: 'Student') : 'Student';
            $subtitle = $conversation->application && $conversation->application->position
                ? $conversation->application->position->title
                : ($conversation->title ?: 'Applicant');
            $avatarLabel = strtoupper(substr($title, 0, 2));
            $avatarStudentId = $student ? (int) $student->id : null;
            $avatarUrl = $imageService->studentPhotoUrl($student, 'sm');
            $filterTags = 'inbox applicants';
            if ($conversation->application && in_array($conversation->application->status, [
                Application::STATUS_ORG_APPROVED,
                Application::STATUS_UNIVERSITY_APPROVED,
            ], true)) {
                $filterTags .= ' interviews';
            }
            $peerRole = 'student';
        }

        $preview = $lastMessage
            ? $this->formatMessagePreview($lastMessage->body)
            : 'No messages yet — say hello';
        $time = $conversation->last_message_at
            ? Yii::$app->formatter->asRelativeTime($conversation->last_message_at)
            : Yii::$app->formatter->asRelativeTime($conversation->created_at);

        $isArchived = (int) ($participant->is_archived ?? 0) === 1;

        return [
            'id' => (int) $conversation->id,
            'conversationId' => (int) $conversation->id,
            'applicationId' => $conversation->application_id ? (int) $conversation->application_id : null,
            'title' => $title,
            'subtitle' => $subtitle,
            'preview' => $preview,
            'time' => $time,
            'avatarLabel' => $avatarLabel,
            'avatarUrl' => $avatarUrl,
            'avatarOrganizationId' => $avatarOrganizationId,
            'avatarStudentId' => $avatarStudentId,
            'peerRole' => $peerRole,
            'filterTags' => $filterTags,
            'unread' => $unread > 0,
            'unreadCount' => $unread,
            'chatEnabled' => true,
            'source' => 'chat',
            'isArchived' => $isArchived,
        ];
    }

    public function setConversationArchived(int $conversationId, int $userId, bool $archived): bool
    {
        $conversation = $this->getConversationForUser($conversationId, $userId);
        $participant = $this->assertParticipant($conversation, $userId);
        $participant->is_archived = $archived ? 1 : 0;

        return $participant->save(false, ['is_archived']);
    }

    private function markDeliveredAndRead(ChatConversation $conversation, int $userId, array $messages): void
    {
        $now = time();
        foreach ($messages as $msg) {
            if ((int) $msg->sender_user_id === $userId) {
                continue;
            }
            $status = ChatMessageStatus::findOne(['message_id' => $msg->id, 'user_id' => $userId]);
            if ($status) {
                if (!$status->read_at) {
                    $status->read_at = $now;
                    $status->save(false);
                }
            }
        }

        $participant = ChatParticipant::findOne(['conversation_id' => $conversation->id, 'user_id' => $userId]);
        if ($participant && !empty($messages)) {
            $last = end($messages);
            $participant->last_read_message_id = $last->id;
            $participant->last_read_at = $now;
            $participant->save(false);
        }
    }

    public function serializeMessage(ChatMessage $msg, int $viewerUserId): array
    {
        $isMine = (int) $msg->sender_user_id === $viewerUserId;
        $statusLabel = 'Sent';
        $state = 'sent';

        if (!$isMine) {
            $st = ChatMessageStatus::findOne(['message_id' => $msg->id, 'user_id' => $viewerUserId]);
            if ($st && $st->read_at) {
                $statusLabel = 'Read';
                $state = 'read';
            } elseif ($st && $st->delivered_at) {
                $statusLabel = 'Delivered';
                $state = 'delivered';
            }
        } else {
            $recipients = ChatMessageStatus::find()->where(['message_id' => $msg->id])->all();
            if ($recipients) {
                $allRead = true;
                foreach ($recipients as $r) {
                    if (!$r->read_at) {
                        $allRead = false;
                        break;
                    }
                }
                if ($allRead && count($recipients) > 0) {
                    $statusLabel = 'Seen';
                    $state = 'read';
                } else {
                    $statusLabel = 'Delivered';
                    $state = 'delivered';
                }
            }
        }

        return [
            'id' => (int) $msg->id,
            'conversationId' => (int) $msg->conversation_id,
            'senderUserId' => (int) $msg->sender_user_id,
            'body' => $msg->body,
            'direction' => $isMine ? 'out' : 'in',
            'createdAt' => (int) $msg->created_at,
            'dateKey' => Yii::$app->formatter->asDate($msg->created_at, 'php:M j, Y'),
            'timeLabel' => Yii::$app->formatter->asRelativeTime($msg->created_at),
            'statusLabel' => $statusLabel,
            'state' => $state,
            'attachment' => $msg->attachment_path ? [
                'url' => Yii::$app->request->hostInfo . Yii::getAlias('@web') . '/' . ltrim($msg->attachment_path, '/'),
                'name' => $msg->attachment_name,
                'mime' => $msg->attachment_mime,
            ] : null,
        ];
    }

    public function pollEvents(int $conversationId, int $userId, int $sinceMessageId = 0): array
    {
        $conversation = $this->getConversationForUser($conversationId, $userId);
        $messages = ChatMessage::find()
            ->where(['conversation_id' => $conversation->id])
            ->andWhere(['>', 'id', $sinceMessageId])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $serialized = [];
        foreach ($messages as $msg) {
            $serialized[] = $this->serializeMessage($msg, $userId);
        }
        if ($messages) {
            $this->markDeliveredAndRead($conversation, $userId, $messages);
        }

        $typers = ChatTyping::activeTypers($conversation->id, $userId);
        $typingUsers = [];
        foreach ($typers as $t) {
            $u = User::findOne($t->user_id);
            $typingUsers[] = [
                'userId' => (int) $t->user_id,
                'name' => $u ? $u->username : 'User',
            ];
        }

        $presence = [];
        foreach (ChatParticipant::find()->where(['conversation_id' => $conversation->id])->all() as $p) {
            if ((int) $p->user_id === $userId) {
                continue;
            }
            $pr = ChatPresence::findOne((int) $p->user_id);
            $presence[] = [
                'userId' => (int) $p->user_id,
                'online' => $pr && (int) $pr->is_online === 1 && ($pr->last_seen_at > time() - 90),
                'lastSeen' => $pr ? (int) $pr->last_seen_at : null,
            ];
        }

        return [
            'messages' => $serialized,
            'typing' => $typingUsers,
            'presence' => $presence,
        ];
    }

    public function setTyping(int $conversationId, int $userId, bool $typing): void
    {
        $this->getConversationForUser($conversationId, $userId);
        if ($typing) {
            ChatTyping::setTyping($conversationId, $userId);
            ChatRealtimeBroadcaster::emit($conversationId, 'typing_started', ['userId' => $userId]);
        } else {
            ChatTyping::clearTyping($conversationId, $userId);
            ChatRealtimeBroadcaster::emit($conversationId, 'typing_stopped', ['userId' => $userId]);
        }
    }

    public function heartbeat(int $userId): void
    {
        ChatPresence::touch($userId, true);
        ChatRealtimeBroadcaster::emitUser($userId, 'user_online', ['userId' => $userId]);
    }

    public function markConversationUnread(int $conversationId, int $userId): bool
    {
        $conversation = $this->getConversationForUser($conversationId, $userId);
        $participant = $this->assertParticipant($conversation, $userId);

        $lastIncoming = ChatMessage::find()
            ->where(['conversation_id' => $conversation->id])
            ->andWhere(['!=', 'sender_user_id', $userId])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if (!$lastIncoming) {
            return false;
        }

        $previousReadId = (int) ChatMessage::find()
            ->where(['conversation_id' => $conversation->id])
            ->andWhere(['<', 'id', $lastIncoming->id])
            ->max('id');

        $participant->last_read_message_id = $previousReadId;
        $participant->last_read_at = null;

        return $participant->save(false, ['last_read_message_id', 'last_read_at']);
    }

    public function formatMessagePreview(?string $body, int $maxLength = 120): string
    {
        if ($body === null || trim($body) === '') {
            return '';
        }

        if (!mb_check_encoding($body, 'UTF-8')) {
            $body = mb_convert_encoding($body, 'UTF-8', 'UTF-8');
        }

        $body = trim($body);
        if (mb_strlen($body, 'UTF-8') <= $maxLength) {
            return $body;
        }

        return rtrim(mb_substr($body, 0, $maxLength, 'UTF-8')) . '…';
    }
}
