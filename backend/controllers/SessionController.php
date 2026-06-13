<?php

namespace backend\controllers;

use common\components\SessionSecurity;
use common\models\SupportAdminPresence;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class SessionController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'heartbeat' => ['POST'],
                ],
            ],
        ];
    }

    public function actionHeartbeat()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->user->isGuest) {
            SupportAdminPresence::touch((int) Yii::$app->user->id);
        }

        return SessionSecurity::heartbeatResponse();
    }
}
