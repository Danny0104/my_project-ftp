<?php

namespace backend\controllers;

use Yii;
use common\models\Application;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class ApplicationController extends BaseController
{
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ]);
    }

    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Application::find()->with(['student.user', 'position.organization']),
            'pagination' => ['pageSize' => 50],
            'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]],
        ]);
        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new Application();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $oldStatus = $model->status;
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            if ($model->status !== $oldStatus && in_array($model->status, [Application::STATUS_APPROVED, Application::STATUS_REJECTED])) {
                // Create notification using the improved method
                \common\models\Notification::createFromAdmin(
                    $model->user_id,
                    'Application Status Update',
                    'Your application for "' . $model->position->title . '" has been ' . ucfirst($model->status) . '.',
                    Yii::$app->user->id,
                    '/application/my-applications',
                    'View Applications'
                );
            }
            return $this->redirect(['view', 'id' => $model->id]);
        }
        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = Application::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
} 