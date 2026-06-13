<?php

namespace frontend\controllers;

use common\components\SessionSecurity;
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

        return SessionSecurity::heartbeatResponse();
    }
}
