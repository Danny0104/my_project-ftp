<?php

namespace frontend\modules\support\controllers;

use common\models\SupportMessage;
use common\models\SupportTicket;
use common\models\SupportTicketRead;
use frontend\modules\support\assets\SupportAsset;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

class TicketController extends BaseController
{
    public function actionIndex()
    {
        $this->requireCan('support.ticket.viewOwn');
        SupportAsset::register($this->view);
        $role = (string) (Yii::$app->user->identity->role ?? '');
        $this->view->params['ftpNavActive'] = 'support';
        $this->view->params['orgNavActive'] = 'support';

        $userId = (int) Yii::$app->user->id;

        $status = trim((string) Yii::$app->request->get('status', ''));
        $q = trim((string) Yii::$app->request->get('q', ''));

        $query = SupportTicket::findVisibleToUser($userId, $role)->orderBy(['last_message_at' => SORT_DESC, 'id' => SORT_DESC]);
        if ($status !== '' && in_array($status, [
            SupportTicket::STATUS_OPEN,
            SupportTicket::STATUS_IN_PROGRESS,
            SupportTicket::STATUS_RESOLVED,
            SupportTicket::STATUS_CLOSED,
        ], true)) {
            $query->andWhere(['status' => $status]);
        }
        if ($q !== '') {
            $query->andWhere(['or',
                ['like', 'code', $q],
                ['like', 'subject', $q],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        $totalUnread = $this->totalUnreadForUser($userId, $role);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'status' => $status,
            'q' => $q,
            'totalUnread' => $totalUnread,
        ]);
    }

    public function actionView(string $code)
    {
        $this->requireCan('support.ticket.viewOwn');
        SupportAsset::register($this->view);
        $this->view->params['ftpNavActive'] = 'support';
        $this->view->params['orgNavActive'] = 'support';

        $userId = (int) Yii::$app->user->id;
        $role = (string) (Yii::$app->user->identity->role ?? '');

        $ticket = SupportTicket::findVisibleToUser($userId, $role)->andWhere(['code' => $code])->one();
        if (!$ticket) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        $messages = $ticket->getMessages()->with(['sender', 'attachments'])->all();

        $this->markRead($ticket->id, $userId);

        return $this->render('view', [
            'ticket' => $ticket,
            'messages' => $messages,
        ]);
    }

    public function actionCreate()
    {
        $this->requireCan('support.ticket.create');
        SupportAsset::register($this->view);
        $this->view->params['ftpNavActive'] = 'support';
        $this->view->params['orgNavActive'] = 'support';

        $ticket = new SupportTicket();
        $ticket->code = SupportTicket::generateCode();
        $ticket->created_by_user_id = (int) Yii::$app->user->id;
        $ticket->created_by_role = (string) (Yii::$app->user->identity->role ?? '');
        $ticket->status = SupportTicket::STATUS_OPEN;
        $ticket->priority = SupportTicket::PRIORITY_NORMAL;

        $subject = trim((string) Yii::$app->request->post('subject', ''));
        $body = trim((string) Yii::$app->request->post('body', ''));

        if (Yii::$app->request->isPost) {
            if ($subject === '' || $body === '') {
                Yii::$app->session->setFlash('error', 'Subject and message are required.');
            } else {
                $now = time();
                $ticket->subject = $subject;
                $ticket->created_at = $now;
                $ticket->updated_at = $now;

                if ($ticket->save(false)) {
                    $msg = new SupportMessage();
                    $msg->ticket_id = (int) $ticket->id;
                    $msg->sender_user_id = (int) Yii::$app->user->id;
                    $msg->sender_role = $ticket->created_by_role;
                    $msg->body = $body;
                    $msg->is_internal_note = 0;
                    $msg->created_at = $now;
                    $msg->save(false);

                    $ticket->last_message_id = (int) $msg->id;
                    $ticket->last_message_at = $now;
                    $ticket->updated_at = $now;
                    $ticket->save(false);

                    return $this->redirect(['view', 'code' => $ticket->code]);
                }
            }
        }

        return $this->render('create', [
            'ticket' => $ticket,
            'subject' => $subject,
            'body' => $body,
        ]);
    }

    private function markRead(int $ticketId, int $userId): void
    {
        $last = (int) SupportMessage::find()->where(['ticket_id' => $ticketId])->max('id');
        if ($last <= 0) {
            return;
        }
        $row = SupportTicketRead::findOne(['ticket_id' => $ticketId, 'user_id' => $userId]);
        if (!$row) {
            $row = new SupportTicketRead();
            $row->ticket_id = $ticketId;
            $row->user_id = $userId;
        }
        $row->last_read_message_id = $last;
        $row->last_read_at = time();
        $row->save(false);
    }

    private function totalUnreadForUser(int $userId, string $role): int
    {
        if ($role === 'admin') {
            return 0;
        }
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

