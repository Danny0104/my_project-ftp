<?php

namespace frontend\modules\support\controllers;

use common\models\SupportAttachment;
use common\models\SupportMessage;
use common\models\SupportTicket;
use common\models\SupportTicketRead;
use Yii;
use yii\web\Response;
use yii\web\UploadedFile;

class ApiController extends BaseController
{
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        return true;
    }

    public function actionUnreadCount(): array
    {
        $this->requireCan('support.ticket.viewOwn');
        $userId = (int) Yii::$app->user->id;
        $role = (string) (Yii::$app->user->identity->role ?? '');

        $total = $this->totalUnreadForUser($userId, $role);
        return $this->jsonSuccess(['count' => $total]);
    }

    public function actionPoll(string $code, int $since_id = 0): array
    {
        $this->requireCan('support.ticket.viewOwn');
        $userId = (int) Yii::$app->user->id;
        $role = (string) (Yii::$app->user->identity->role ?? '');

        $ticket = SupportTicket::findVisibleToUser($userId, $role)->andWhere(['code' => $code])->one();
        if (!$ticket) {
            Yii::$app->response->statusCode = 404;
            return $this->jsonError('Ticket not found.');
        }

        $q = SupportMessage::find()
            ->where(['ticket_id' => (int) $ticket->id])
            ->andWhere(['>', 'id', (int) $since_id])
            ->andWhere(['is_internal_note' => 0])
            ->orderBy(['id' => SORT_ASC])
            ->with(['sender', 'attachments']);

        $messages = [];
        foreach ($q->all() as $m) {
            $messages[] = $this->serializeMessage($m);
        }

        return $this->jsonSuccess([
            'messages' => $messages,
            'unread' => $this->ticketUnreadForUser((int) $ticket->id, $userId),
            'totalUnread' => $this->totalUnreadForUser($userId, $role),
        ]);
    }

    public function actionSend(): array
    {
        $this->requireCan('support.ticket.replyOwn');

        $code = trim((string) Yii::$app->request->post('code', ''));
        $body = trim((string) Yii::$app->request->post('body', ''));
        if ($code === '' || $body === '') {
            Yii::$app->response->statusCode = 422;
            return $this->jsonError('Message body required.');
        }

        $userId = (int) Yii::$app->user->id;
        $role = (string) (Yii::$app->user->identity->role ?? '');

        $ticket = SupportTicket::findVisibleToUser($userId, $role)->andWhere(['code' => $code])->one();
        if (!$ticket) {
            Yii::$app->response->statusCode = 404;
            return $this->jsonError('Ticket not found.');
        }
        if ($ticket->status === SupportTicket::STATUS_CLOSED) {
            Yii::$app->response->statusCode = 403;
            return $this->jsonError('Ticket is closed.');
        }

        $now = time();
        $msg = new SupportMessage();
        $msg->ticket_id = (int) $ticket->id;
        $msg->sender_user_id = $userId;
        $msg->sender_role = $role;
        $msg->body = $body;
        $msg->is_internal_note = 0;
        $msg->created_at = $now;
        $msg->save(false);

        $file = UploadedFile::getInstanceByName('attachment');
        $attachmentPayload = null;
        if ($file) {
            $this->requireCan('support.ticket.uploadOwn');
            $attachmentPayload = $this->storeAttachment((int) $ticket->id, (int) $msg->id, $file);
        }

        $ticket->last_message_id = (int) $msg->id;
        $ticket->last_message_at = $now;
        $ticket->updated_at = $now;
        $ticket->save(false);

        // Mark as read for sender immediately.
        $this->markRead((int) $ticket->id, $userId, (int) $msg->id);

        $serialized = $this->serializeMessage($msg);
        if ($attachmentPayload) {
            $serialized['attachments'] = [$attachmentPayload];
        }

        return $this->jsonSuccess(['message' => $serialized]);
    }

    public function actionMarkRead(): array
    {
        $this->requireCan('support.ticket.viewOwn');
        $code = trim((string) Yii::$app->request->post('code', ''));
        if ($code === '') {
            Yii::$app->response->statusCode = 422;
            return $this->jsonError('code required');
        }
        $userId = (int) Yii::$app->user->id;
        $role = (string) (Yii::$app->user->identity->role ?? '');
        $ticket = SupportTicket::findVisibleToUser($userId, $role)->andWhere(['code' => $code])->one();
        if (!$ticket) {
            Yii::$app->response->statusCode = 404;
            return $this->jsonError('Ticket not found.');
        }
        $last = (int) SupportMessage::find()->where(['ticket_id' => (int) $ticket->id])->max('id');
        $this->markRead((int) $ticket->id, $userId, $last);
        return $this->jsonSuccess(['ok' => true]);
    }

    private function serializeMessage(SupportMessage $m): array
    {
        $attachments = [];
        foreach ($m->attachments as $a) {
            $attachments[] = [
                'url' => Yii::$app->request->hostInfo . Yii::getAlias('@web') . '/' . ltrim($a->path, '/'),
                'name' => $a->name,
                'mime' => $a->mime,
                'size' => (int) $a->size,
            ];
        }
        return [
            'id' => (int) $m->id,
            'ticketId' => (int) $m->ticket_id,
            'senderUserId' => $m->sender_user_id ? (int) $m->sender_user_id : null,
            'senderRole' => $m->sender_role,
            'body' => $m->body,
            'createdAt' => (int) $m->created_at,
            'timeLabel' => Yii::$app->formatter->asRelativeTime($m->created_at),
            'attachments' => $attachments,
        ];
    }

    private function storeAttachment(int $ticketId, int $messageId, UploadedFile $file): array
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        if (!in_array($file->type, $allowed, true)) {
            throw new \InvalidArgumentException('File type not allowed.');
        }
        if ($file->size > 8 * 1024 * 1024) {
            throw new \InvalidArgumentException('File too large (max 8MB).');
        }

        $dir = Yii::getAlias('@frontend/web/uploads/support');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = 'support_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->baseName) . '.' . $file->extension;
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!$file->saveAs($path)) {
            throw new \RuntimeException('Failed to save attachment.');
        }

        $rel = 'uploads/support/' . $name;

        $a = new SupportAttachment();
        $a->ticket_id = $ticketId;
        $a->message_id = $messageId;
        $a->path = $rel;
        $a->name = $file->name;
        $a->mime = $file->type;
        $a->size = (int) $file->size;
        $a->created_at = time();
        $a->save(false);

        return [
            'url' => Yii::$app->request->hostInfo . Yii::getAlias('@web') . '/' . ltrim($rel, '/'),
            'name' => $a->name,
            'mime' => $a->mime,
            'size' => (int) $a->size,
        ];
    }

    private function markRead(int $ticketId, int $userId, int $messageId): void
    {
        if ($messageId <= 0) {
            return;
        }
        $row = SupportTicketRead::findOne(['ticket_id' => $ticketId, 'user_id' => $userId]);
        if (!$row) {
            $row = new SupportTicketRead();
            $row->ticket_id = $ticketId;
            $row->user_id = $userId;
        }
        $row->last_read_message_id = $messageId;
        $row->last_read_at = time();
        $row->save(false);
    }

    private function ticketUnreadForUser(int $ticketId, int $userId): int
    {
        $lastRead = (int) (SupportTicketRead::findOne(['ticket_id' => $ticketId, 'user_id' => $userId])->last_read_message_id ?? 0);
        return (int) SupportMessage::find()
            ->where(['ticket_id' => $ticketId, 'is_internal_note' => 0])
            ->andWhere(['>', 'id', $lastRead])
            ->count();
    }

    private function totalUnreadForUser(int $userId, string $role): int
    {
        $tickets = SupportTicket::findVisibleToUser($userId, $role)->select(['id'])->column();
        if (!$tickets) {
            return 0;
        }
        $reads = SupportTicketRead::find()->where(['user_id' => $userId, 'ticket_id' => $tickets])->indexBy('ticket_id')->all();
        $total = 0;
        foreach ($tickets as $tid) {
            $lastRead = isset($reads[$tid]) ? (int) $reads[$tid]->last_read_message_id : 0;
            $total += (int) SupportMessage::find()
                ->where(['ticket_id' => (int) $tid, 'is_internal_note' => 0])
                ->andWhere(['>', 'id', $lastRead])
                ->count();
        }
        return $total;
    }
}

