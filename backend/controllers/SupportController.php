<?php

namespace backend\controllers;

use common\models\SupportChatMessage;
use common\models\SupportConversation;
use common\models\SupportMessage;
use common\models\User;
use common\services\SupportService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SupportController extends BaseController
{
    private SupportService $support;

    public function __construct($id, $module, SupportService $support = null, $config = [])
    {
        $this->support = $support ?? new SupportService();
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'reply' => ['POST'],
                    'chat-send' => ['POST'],
                    'chat-poll' => ['GET'],
                    'chat-mark-read' => ['POST'],
                    'mark-read' => ['POST'],
                    'unread-count' => ['GET'],
                ],
            ],
        ]);
    }

    public function actionIndex()
    {
        $this->view->params['apNavActive'] = 'support';
        $q = trim((string) Yii::$app->request->get('q', ''));

        $query = SupportConversation::find()
            ->with(['user'])
            ->orderBy(['last_message_at' => SORT_DESC, 'id' => SORT_DESC]);

        if ($q !== '') {
            $query->andWhere(['or', ['like', 'subject', $q], ['like', 'category', $q]]);
        }

        $dp = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 30],
        ]);

        $chatUsers = SupportChatMessage::find()
            ->select(['user_id', 'MAX(id) as last_id'])
            ->groupBy(['user_id'])
            ->orderBy(['last_id' => SORT_DESC])
            ->limit(20)
            ->asArray()
            ->all();

        return $this->render('index', [
            'dataProvider' => $dp,
            'q' => $q,
            'chatUsers' => User::find()->where(['id' => array_column($chatUsers, 'user_id')])->indexBy('id')->all(),
            'unreadCount' => $this->support->countUnreadForAdmin(),
        ]);
    }

    public function actionView(int $id)
    {
        $this->view->params['apNavActive'] = 'support';
        $conversation = $this->support->getConversationForAdmin($id);
        $this->support->markConversationReadByAdmin($id, (int) Yii::$app->user->id);

        return $this->render('view', [
            'conversation' => $conversation,
            'user' => $conversation->user,
        ]);
    }

    public function actionReply(int $id)
    {
        $body = trim((string) Yii::$app->request->post('body', ''));
        if ($body === '') {
            Yii::$app->session->setFlash('error', 'Message cannot be empty.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $this->support->replyAsAdmin($id, (int) Yii::$app->user->id, $body);
        Yii::$app->session->setFlash('success', 'Reply sent.');

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionChat(int $user_id)
    {
        $this->view->params['apNavActive'] = 'support';
        $user = User::findOne($user_id);
        if (!$user) {
            throw new NotFoundHttpException('User not found.');
        }

        $this->support->markChatReadForAdmin($user_id);

        return $this->render('chat', [
            'user' => $user,
            'messages' => $this->support->getChatHistory($user_id, 100),
        ]);
    }

    public function actionChatSend(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $userId = (int) Yii::$app->request->post('user_id', 0);
        $body = trim((string) Yii::$app->request->post('body', ''));
        $user = User::findOne($userId);
        if (!$user) {
            return ['ok' => false, 'error' => 'User not found.'];
        }

        try {
            $message = $this->support->sendChatMessage(
                $userId,
                (string) $user->role,
                (int) Yii::$app->user->id,
                SupportMessage::ROLE_ADMIN,
                $body
            );

            return ['ok' => true, 'message' => $this->support->formatChatMessage($message)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionChatPoll(int $user_id): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $sinceId = (int) Yii::$app->request->get('since_id', 0);

        return [
            'ok' => true,
            'messages' => $this->support->pollChatMessages($user_id, $sinceId),
        ];
    }

    public function actionMarkRead(int $id): Response
    {
        $this->support->markConversationReadByAdmin($id, (int) Yii::$app->user->id);
        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionUnreadCount(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return ['ok' => true, 'count' => $this->support->countUnreadForAdmin()];
    }
}
