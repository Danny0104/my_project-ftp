<?php

namespace frontend\modules\support\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

abstract class BaseController extends Controller
{
    public function beforeAction($action)
    {
        Yii::$app->response->redirect(['/site/contact']);
        return false;

        if (!parent::beforeAction($action)) {
            return false;
        }

        $role = Yii::$app->user->identity->role ?? '';
        $this->layout = $role === 'organization'
            ? '@frontend/views/layouts/organization.php'
            : '@frontend/views/layouts/student.php';

        return true;
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => static function (): bool {
                            $role = Yii::$app->user->identity->role ?? '';
                            return in_array($role, ['student', 'organization'], true);
                        },
                    ],
                ],
                'denyCallback' => static function () {
                    throw new ForbiddenHttpException('Authentication required.');
                },
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['GET', 'POST'],
                    'send' => ['POST'],
                    'poll' => ['GET'],
                    'unread-count' => ['GET'],
                    'mark-read' => ['POST'],
                ],
            ],
        ];
    }

    protected function requireCan(string $permission): void
    {
        if (!Yii::$app->user->can($permission)) {
            throw new ForbiddenHttpException('Permission denied.');
        }
    }

    protected function jsonSuccess(array $data = [], string $message = 'OK'): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return array_merge(['success' => true, 'message' => $message], $data);
    }

    protected function jsonError(string $message, array $data = []): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return array_merge(['success' => false, 'message' => $message], $data);
    }
}

