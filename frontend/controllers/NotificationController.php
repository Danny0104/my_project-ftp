<?php



namespace frontend\controllers;



use common\traits\RoleDashboardLayoutTrait;

use common\models\Application;
use common\models\Organization;
use common\models\Position;
use common\models\Notification;
use common\services\ChatService;
use frontend\assets\MessagingCoreAsset;
use frontend\assets\NotificationHubAsset;

use Yii;

use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;



class NotificationController extends Controller

{

    use RoleDashboardLayoutTrait;



    public $layout = 'internal';



    public function behaviors()

    {

        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'send-applicant-message' => ['POST'],
                    'archive-read' => ['POST'],
                ],
            ],
        ];

    }



    public function actionIndex()
    {
        $user = Yii::$app->user->identity;

        if (Yii::$app->request->get('view') === 'messages') {
            $params = ['message/index'];
            $chatId = (int) Yii::$app->request->get('chat', 0);
            if ($chatId > 0) {
                $params['conversation_id'] = $chatId;
            }
            return $this->redirect($params);
        }

        if ($user && $user->role === 'organization') {
            $this->layout = 'organization';
            $this->view->params['orgNavActive'] = 'notifications';
        } else {
            $this->layout = 'student';
            $this->view->params['ftpNavActive'] = 'notifications';
        }

        NotificationHubAsset::register($this->view);

        $userId = (int) Yii::$app->user->id;
        $category = trim((string) Yii::$app->request->get('category', ''));
        $query = \common\models\Notification::find()
            ->with(['organization'])
            ->where(['user_id' => $userId, 'is_archived' => 0])
            ->orderBy(['created_at' => SORT_DESC]);

        if ($category !== '' && $category !== 'all') {
            $query->andWhere(['category' => $category]);
        }

        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        $notifications = $dataProvider->getModels();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'notifications' => $notifications,
            'activeCategory' => $category ?: 'all',
        ]);
    }



    public function actionUnreadCount()

    {

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $userId = Yii::$app->user->id;

        $count = \common\models\Notification::getUnreadCount($userId);

        return ['count' => (int) $count];
    }

    public function actionArchiveRead()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $userId = (int) Yii::$app->user->id;
        $count = \common\models\Notification::archiveAllRead($userId);
        return ['success' => true, 'count' => $count];
    }

    /**
     * Send recruiter message to student via existing notification workflow (not live chat).
     */
    public function actionSendApplicantMessage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $identity = Yii::$app->user->identity;
        if (!$identity || $identity->role !== 'organization') {
            return ['success' => false, 'message' => 'Only organizations can send applicant messages'];
        }

        $applicationId = (int) Yii::$app->request->post('application_id');
        $message = trim((string) Yii::$app->request->post('message', ''));
        $title = trim((string) Yii::$app->request->post('title', ''));

        if ($applicationId <= 0 || $message === '') {
            return ['success' => false, 'message' => 'Application and message are required'];
        }

        if (mb_strlen($message) > 4000) {
            return ['success' => false, 'message' => 'Message is too long'];
        }

        $organization = Organization::findOrCreateForUserId((int) Yii::$app->user->id);
        if (!$organization) {
            return ['success' => false, 'message' => 'Unable to load organization profile'];
        }

        $application = Application::find()
            ->alias('a')
            ->innerJoin(['p' => Position::tableName()], 'p.id = a.position_id')
            ->where(['a.id' => $applicationId, 'p.organization_id' => $organization->id])
            ->with(['position', 'student'])
            ->one();

        if (!$application) {
            return ['success' => false, 'message' => 'Application not found'];
        }

        $studentUserId = (int) $application->user_id;
        if ($studentUserId <= 0) {
            return ['success' => false, 'message' => 'Student account not found'];
        }

        $orgName = $organization->name ?? 'Recruiter';
        $roleTitle = $application->position->title ?? 'your application';
        if ($title === '') {
            $title = 'Message from ' . $orgName;
        }

        $actionUrl = Yii::$app->urlManager->createUrl(['message/index']);
        $chat = new ChatService();
        try {
            $conversation = $chat->ensureForApplication($applicationId, (int) Yii::$app->user->id);
            $chat->sendMessage((int) $conversation->id, (int) Yii::$app->user->id, $message);
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Could not send message: ' . $e->getMessage()];
        }

        Notification::createFromOrganization(
            $studentUserId,
            'New message from ' . $orgName,
            $orgName . ' sent you a message about ' . $roleTitle . '. Open Messages to reply.',
            (int) $organization->id,
            Yii::$app->urlManager->createUrl(['message/index', 'conversation_id' => $conversation->id]),
            'Open Messages',
            [
                'notification_type' => Notification::TYPE_NEW_MESSAGE,
                'category' => Notification::CATEGORY_MESSAGES,
                'conversation_id' => (int) $conversation->id,
                'related_id' => $applicationId,
            ]
        );

        return [
            'success' => true,
            'message' => 'Message delivered to student inbox',
            'notification' => [
                'title' => $title,
                'recipient_user_id' => $studentUserId,
                'application_id' => $applicationId,
                'conversation_id' => (int) $conversation->id,
            ],
        ];
    }
}


