<?php

namespace common\services;

use common\models\EmailQueue;
use common\models\Notification;
use common\models\User;
use Yii;

class EmailQueueService
{
    public function enqueue(
        string $toEmail,
        string $subject,
        string $bodyHtml,
        ?string $bodyText = null,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): bool {
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $row = new EmailQueue();
        $row->to_email = $toEmail;
        $row->subject = $subject;
        $row->body_html = $bodyHtml;
        $row->body_text = $bodyText ?? strip_tags($bodyHtml);
        $row->status = EmailQueue::STATUS_PENDING;
        $row->related_type = $relatedType;
        $row->related_id = $relatedId;

        return $row->save();
    }

    public function enqueueForNotification(Notification $notification): void
    {
        if (!(bool) (Yii::$app->params['mail.queueEnabled'] ?? true)) {
            return;
        }

        $user = $notification->user ?? User::findOne((int) $notification->user_id);
        if (!$user || empty($user->email)) {
            return;
        }

        $subject = '[Field Training] ' . $notification->title;
        $bodyHtml = '<p>' . nl2br(htmlspecialchars($notification->message, ENT_QUOTES, 'UTF-8')) . '</p>';
        if ($notification->action_url) {
            $bodyHtml .= '<p><a href="' . htmlspecialchars($notification->action_url, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($notification->action_text ?: 'View details', ENT_QUOTES, 'UTF-8')
                . '</a></p>';
        }

        $this->enqueue($user->email, $subject, $bodyHtml, $notification->message, 'notification', (int) $notification->id);
    }

    public function processBatch(int $limit = 50): int
    {
        $rows = EmailQueue::find()
            ->where(['status' => EmailQueue::STATUS_PENDING])
            ->orderBy(['id' => SORT_ASC])
            ->limit($limit)
            ->all();

        $sent = 0;
        foreach ($rows as $row) {
            if ($this->sendRow($row)) {
                $sent++;
            }
        }

        return $sent;
    }

    private function sendRow(EmailQueue $row): bool
    {
        $row->attempts = (int) $row->attempts + 1;

        try {
            $message = Yii::$app->mailer->compose()
                ->setTo($row->to_email)
                ->setFrom([
                    Yii::$app->params['senderEmail'] ?? 'noreply@example.com',
                    Yii::$app->params['senderName'] ?? 'Field Training Platform',
                ])
                ->setSubject($row->subject)
                ->setHtmlBody($row->body_html)
                ->setTextBody($row->body_text ?? strip_tags($row->body_html));

            if ($message->send()) {
                $row->status = EmailQueue::STATUS_SENT;
                $row->sent_at = time();
                $row->save(false);
                return true;
            }
        } catch (\Throwable $e) {
            Yii::warning('Email queue send failed: ' . $e->getMessage(), __METHOD__);
        }

        if ($row->attempts >= 3) {
            $row->status = EmailQueue::STATUS_FAILED;
        }
        $row->save(false);

        return false;
    }
}
