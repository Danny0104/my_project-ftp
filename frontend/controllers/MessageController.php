<?php

namespace frontend\controllers;

use common\models\Application;
use common\models\Organization;
use common\models\Position;
use common\services\ChatService;
use common\traits\RoleDashboardLayoutTrait;
use frontend\assets\MessagingCoreAsset;
use frontend\assets\NotificationHubAsset;
use frontend\assets\OrganizationMessagesAsset;
use frontend\assets\StudentMessagesHubAsset;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

class MessageController extends Controller
{
    use RoleDashboardLayoutTrait;

    public $layout = 'internal';

    private ChatService $chat;

    public function init()
    {
        parent::init();
        $this->chat = new ChatService();
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'send' => ['POST'],
                    'typing' => ['POST'],
                    'heartbeat' => ['POST'],
                    'mark-read' => ['POST'],
                    'mark-unread' => ['POST'],
                    'archive' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (in_array($action->id, ['thread', 'ensure', 'poll', 'send', 'typing', 'heartbeat', 'mark-read', 'mark-unread', 'unread-count', 'list', 'archive'], true)) {
            Yii::$app->response->format = Response::FORMAT_JSON;
        }
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        if (!$user) {
            return $this->redirect(['site/login']);
        }

        if ($user->role === 'organization') {
            $this->layout = 'organization';
            $this->view->params['orgNavActive'] = 'messages';
            $this->view->params['orgContentClass'] = 'org-content--messages';
        } else {
            $this->layout = 'student';
            $this->view->params['ftpNavActive'] = 'messages';
        }

        NotificationHubAsset::register($this->view);
        MessagingCoreAsset::register($this->view);
        if ($user->role === 'organization') {
            OrganizationMessagesAsset::register($this->view);
        } else {
            StudentMessagesHubAsset::register($this->view);
        }

        $userId = (int) Yii::$app->user->id;
        $conversations = $this->chat->listConversationsForUser($userId);
        $unreadMessages = $this->chat->countUnreadForUser($userId);
        $activeConversationId = (int) Yii::$app->request->get('conversation_id', 0);

        $pendingApplications = [];
        if ($user->role === 'organization') {
            $organization = Organization::findOrCreateForUserId($userId);
            if ($organization) {
                $existingAppIds = [];
                foreach ($conversations as $conv) {
                    if (!empty($conv['applicationId'])) {
                        $existingAppIds[] = (int) $conv['applicationId'];
                    }
                }
                $pendingApplications = Application::find()
                    ->alias('a')
                    ->innerJoin(['p' => Position::tableName()], 'p.id = a.position_id')
                    ->where(['p.organization_id' => $organization->id])
                    ->andWhere(['not in', 'a.id', $existingAppIds ?: [0]])
                    ->with(['student.user', 'position'])
                    ->orderBy(['a.created_at' => SORT_DESC])
                    ->limit(20)
                    ->all();
            }
        }

        return $this->render('index', [
            'conversations' => $conversations,
            'unreadMessages' => $unreadMessages,
            'activeConversationId' => $activeConversationId,
            'pendingApplications' => $pendingApplications,
        ]);
    }

    public function actionUnreadCount()
    {
        $userId = (int) Yii::$app->user->id;
        return ['count' => $this->chat->countUnreadForUser($userId)];
    }

    public function actionList()
    {
        $userId = (int) Yii::$app->user->id;
        return [
            'success' => true,
            'conversations' => $this->chat->listConversationsForUser($userId),
            'unread' => $this->chat->countUnreadForUser($userId),
        ];
    }

    public function actionEnsure()
    {
        $userId = (int) Yii::$app->user->id;
        $applicationId = (int) Yii::$app->request->get('application_id');
        $notificationId = (int) Yii::$app->request->get('notification_id');
        $conversationId = (int) Yii::$app->request->get('conversation_id');

        try {
            $role = Yii::$app->user->identity->role ?? '';
            if ($conversationId > 0) {
                $conversation = $this->chat->getConversationForUser($conversationId, $userId);
            } elseif ($applicationId > 0 && $role === 'organization') {
                $conversation = $this->chat->ensureForApplication($applicationId, $userId);
            } elseif ($applicationId > 0 && $role === 'student') {
                $conversation = $this->chat->ensureForApplicationAsStudent($applicationId, $userId);
            } elseif ($notificationId > 0) {
                $conversation = $this->chat->ensureForNotification($notificationId, $userId);
            } else {
                return ['success' => false, 'message' => 'application_id, conversation_id, or notification_id required'];
            }

            $thread = $this->chat->getMessages((int) $conversation->id, $userId);

            return [
                'success' => true,
                'conversation' => [
                    'id' => (int) $conversation->id,
                    'title' => $conversation->title,
                ],
                'messages' => $thread['messages'],
                'hasMore' => $thread['hasMore'],
            ];
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = $e instanceof \yii\web\HttpException ? $e->statusCode : 400;
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionThread()
    {
        $userId = (int) Yii::$app->user->id;
        $conversationId = (int) Yii::$app->request->get('conversation_id');
        $beforeId = (int) Yii::$app->request->get('before_id');

        try {
            $thread = $this->chat->getMessages($conversationId, $userId, $beforeId ?: null);
            return ['success' => true] + $thread;
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = $e instanceof \yii\web\HttpException ? $e->statusCode : 400;
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionSend()
    {
        $userId = (int) Yii::$app->user->id;
        $conversationId = (int) Yii::$app->request->post('conversation_id');
        $body = (string) Yii::$app->request->post('message', '');
        $file = UploadedFile::getInstanceByName('attachment');

        try {
            $message = $this->chat->sendMessage($conversationId, $userId, $body, $file);
            return ['success' => true, 'message' => $message];
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 400;
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionPoll()
    {
        $userId = (int) Yii::$app->user->id;
        $conversationId = (int) Yii::$app->request->get('conversation_id');
        $since = (int) Yii::$app->request->get('since_id');

        try {
            return ['success' => true] + $this->chat->pollEvents($conversationId, $userId, $since);
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 400;
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionTyping()
    {
        $userId = (int) Yii::$app->user->id;
        $conversationId = (int) Yii::$app->request->post('conversation_id');
        $typing = (bool) Yii::$app->request->post('typing', true);

        try {
            $this->chat->setTyping($conversationId, $userId, $typing);
            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionHeartbeat()
    {
        $this->chat->heartbeat((int) Yii::$app->user->id);
        return ['success' => true];
    }

    public function actionMarkRead()
    {
        $userId = (int) Yii::$app->user->id;
        $conversationId = (int) Yii::$app->request->post('conversation_id');
        try {
            $this->chat->getMessages($conversationId, $userId);
            return [
                'success' => true,
                'unread' => $this->chat->countUnreadForUser($userId),
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionMarkUnread()
    {
        $userId = (int) Yii::$app->user->id;
        $conversationId = (int) Yii::$app->request->post('conversation_id');
        try {
            $ok = $this->chat->markConversationUnread($conversationId, $userId);
            return [
                'success' => $ok,
                'unread' => $this->chat->countUnreadForUser($userId),
                'message' => $ok ? 'Conversation marked as unread.' : 'Nothing to mark unread.',
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function actionArchive()
    {
        $userId = (int) Yii::$app->user->id;
        $conversationId = (int) Yii::$app->request->post('conversation_id');
        $archived = (bool) Yii::$app->request->post('archived', true);

        try {
            $ok = $this->chat->setConversationArchived($conversationId, $userId, $archived);

            return [
                'success' => $ok,
                'message' => $archived ? 'Conversation archived.' : 'Conversation restored.',
            ];
        } catch (\Throwable $e) {
            Yii::$app->response->statusCode = 400;
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
