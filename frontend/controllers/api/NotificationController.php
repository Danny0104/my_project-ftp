<?php

namespace frontend\controllers\api;

use common\models\Notification;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\rest\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class NotificationController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];
        $behaviors['cors'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Max-Age' => 86400,
            ],
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index' => ['GET'],
                'mark-read' => ['POST'],
                'mark-unread' => ['POST'],
                'delete' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    public function actionIndex()
    {
        $userId = (int) Yii::$app->user->id;
        $provider = new ActiveDataProvider([
            'query' => Notification::find()
                ->where(['user_id' => $userId, 'is_archived' => 0])
                ->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        $items = [];
        foreach ($provider->getModels() as $n) {
            $items[] = $this->serialize($n);
        }

        return [
            'success' => true,
            'data' => $items,
            'meta' => [
                'total' => $provider->totalCount,
                'page' => $provider->pagination->page + 1,
                'pageSize' => $provider->pagination->pageSize,
            ],
        ];
    }

    public function actionMarkRead($id)
    {
        $model = $this->findOwned((int) $id);
        $model->is_read = 1;
        $model->save(false, ['is_read', 'updated_at']);

        return ['success' => true, 'message' => 'Marked as read'];
    }

    public function actionMarkUnread($id)
    {
        $model = $this->findOwned((int) $id);
        $model->is_read = 0;
        $model->save(false, ['is_read', 'updated_at']);

        return ['success' => true, 'message' => 'Marked as unread'];
    }

    public function actionDelete($id)
    {
        $model = $this->findOwned((int) $id);
        $model->delete();

        return ['success' => true, 'message' => 'Notification deleted'];
    }

    private function findOwned(int $id): Notification
    {
        if (Yii::$app->user->isGuest) {
            throw new ForbiddenHttpException('Authentication required.');
        }

        $model = Notification::findOne(['id' => $id, 'user_id' => (int) Yii::$app->user->id]);
        if (!$model) {
            throw new NotFoundHttpException('Notification not found.');
        }

        return $model;
    }

    private function serialize(Notification $n): array
    {
        return [
            'id' => (int) $n->id,
            'title' => $n->title,
            'message' => $n->message,
            'is_read' => (int) $n->is_read,
            'notification_type' => $n->notification_type,
            'category' => $n->category,
            'priority' => $n->priority,
            'action_url' => $n->action_url,
            'action_text' => $n->action_text,
            'created_at' => date('c', (int) $n->created_at),
        ];
    }
}
