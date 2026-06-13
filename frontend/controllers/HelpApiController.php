<?php

namespace frontend\controllers;

use common\models\SupportConversation;
use common\models\SupportMessage;
use common\services\SupportAiService;
use common\services\SupportService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class HelpApiController extends Controller
{
    private SupportService $support;
    private SupportAiService $ai;

    public function __construct($id, $module, SupportService $support = null, SupportAiService $ai = null, $config = [])
    {
        $this->support = $support ?? new SupportService();
        $this->ai = $ai ?? new SupportAiService();
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => static function () {
                            $role = Yii::$app->user->identity->role ?? '';
                            return in_array($role, ['student', 'organization'], true);
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'submit-request' => ['POST'],
                    'ai-ask' => ['POST'],
                    'chat-send' => ['POST'],
                    'chat-mark-read' => ['POST'],
                    'chat-poll' => ['GET'],
                    'chat-history' => ['GET'],
                    'chat-status' => ['GET'],
                    'unread-count' => ['GET'],
                ],
            ],
        ];
    }

    public function beforeAction($action): bool
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    public function actionSubmitRequest(): array
    {
        $user = Yii::$app->user->identity;
        $category = trim((string) Yii::$app->request->post('category', 'other'));
        $subject = trim((string) Yii::$app->request->post('subject', ''));
        $body = trim((string) Yii::$app->request->post('body', ''));

        $allowedCategories = SupportConversation::categoryOptionsForRole((string) $user->role);
        if (!isset($allowedCategories[$category])) {
            return ['ok' => false, 'error' => 'Invalid category.'];
        }

        try {
            $conversation = $this->support->submitRequest(
                (int) $user->id,
                (string) $user->role,
                $category,
                $subject,
                $body
            );

            return [
                'ok' => true,
                'conversation_id' => (int) $conversation->id,
                'message' => 'Your request was sent to the admin team. You will be notified when they reply.',
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionAiAsk(): array
    {
        $question = trim((string) Yii::$app->request->post('question', ''));
        $role = (string) (Yii::$app->user->identity->role ?? 'student');
        $result = $this->ai->ask($question, $role);

        return ['ok' => true, 'result' => $result];
    }

    public function actionChatHistory(): array
    {
        $userId = (int) Yii::$app->user->id;
        $this->support->markChatReadForUser($userId);

        return [
            'ok' => true,
            'messages' => $this->support->getChatHistory($userId),
            'admin_online' => $this->support->isAdminOnline(),
        ];
    }

    public function actionChatPoll(): array
    {
        $userId = (int) Yii::$app->user->id;
        $sinceId = (int) Yii::$app->request->get('since_id', 0);

        return [
            'ok' => true,
            'messages' => $this->support->pollChatMessages($userId, $sinceId),
            'admin_online' => $this->support->isAdminOnline(),
        ];
    }

    public function actionChatSend(): array
    {
        $user = Yii::$app->user->identity;
        $body = trim((string) Yii::$app->request->post('body', ''));

        try {
            $message = $this->support->sendChatMessage(
                (int) $user->id,
                (string) $user->role,
                (int) $user->id,
                (string) $user->role,
                $body
            );

            return [
                'ok' => true,
                'message' => $this->support->formatChatMessage($message),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionChatMarkRead(): array
    {
        $this->support->markChatReadForUser((int) Yii::$app->user->id);
        return ['ok' => true];
    }

    public function actionChatStatus(): array
    {
        return [
            'ok' => true,
            'admin_online' => $this->support->isAdminOnline(),
        ];
    }

    public function actionUnreadCount(): array
    {
        return [
            'ok' => true,
            'count' => $this->support->countUnreadForUser((int) Yii::$app->user->id),
        ];
    }
}
