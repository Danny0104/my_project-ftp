<?php

namespace backend\controllers;

use Yii;
use common\models\Student;
use common\services\StudentCvService;
use common\services\StudentIdDocumentService;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

class StudentController extends BaseController
{
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'download-cv' => ['GET'],
                    'view-cv' => ['GET'],
                    'view-id-document' => ['GET'],
                    'verify-id' => ['POST'],
                    'reject-id' => ['POST'],
                    'request-reupload' => ['POST'],
                ],
            ],
        ]);
    }

    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Student::find(),
        ]);
        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        $cvInfo = (new StudentCvService())->describe($model);
        $idDocInfo = (new StudentIdDocumentService())->resolveAbsolutePath($model);

        return $this->render('view', [
            'model' => $model,
            'cvInfo' => $cvInfo,
            'hasIdDocument' => $idDocInfo !== null,
        ]);
    }

    public function actionViewIdDocument($id)
    {
        return $this->sendIdDocumentResponse((int) $id, true);
    }

    public function actionVerifyId($id)
    {
        $student = $this->findModel($id);
        if (!$student->hasIdDocument()) {
            Yii::$app->session->setFlash('error', 'No student ID document on file.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $student->id_verification_status = Student::ID_VERIFICATION_APPROVED;
        $student->id_verified_at = time();
        $student->id_verified_by = (int) Yii::$app->user->id;
        $student->id_verification_method = Student::ID_METHOD_MANUAL;
        $student->id_rejection_reason = null;
        $student->id_fraud_flag = false;
        $student->save(false);
        Yii::$app->session->setFlash('success', 'Student ID verified.');

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionRejectId($id)
    {
        $student = $this->findModel($id);
        $reason = trim((string) Yii::$app->request->post('reason', ''));
        if (!$student->hasIdDocument()) {
            Yii::$app->session->setFlash('error', 'No student ID document on file.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $student->id_verification_status = Student::ID_VERIFICATION_REJECTED;
        $student->id_verified_at = null;
        $student->id_verified_by = (int) Yii::$app->user->id;
        $student->id_verification_method = Student::ID_METHOD_MANUAL;
        $student->id_rejection_reason = $reason !== '' ? $reason : 'Document could not be verified.';
        $student->save(false);
        Yii::$app->session->setFlash('warning', 'Student ID verification rejected.');

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionRequestReupload($id)
    {
        $student = $this->findModel($id);
        if (!$student->hasIdDocument()) {
            Yii::$app->session->setFlash('error', 'No student ID document on file.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $reason = trim((string) Yii::$app->request->post('reason', ''));
        $student->id_verification_status = Student::ID_VERIFICATION_REJECTED;
        $student->id_verified_at = time();
        $student->id_verified_by = (int) Yii::$app->user->id;
        $student->id_verification_method = Student::ID_METHOD_MANUAL;
        $student->id_rejection_reason = $reason !== ''
            ? $reason
            : 'Please upload a new, clearer student ID document.';
        $student->save(false);
        Yii::$app->session->setFlash('warning', 'Re-upload requested from student.');

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionViewCv($id)
    {
        return $this->sendCvResponse((int) $id, true);
    }

    public function actionDownloadCv($id)
    {
        return $this->sendCvResponse((int) $id, false);
    }

    private function sendCvResponse(int $id, bool $inline): Response
    {
        $student = $this->findModel($id);
        $service = new StudentCvService();
        $absolutePath = $service->resolveAbsolutePath($student);

        if ($absolutePath === null) {
            $stored = trim((string) $student->cv);
            $message = $stored !== ''
                ? 'The CV path is recorded in the profile but the file is missing or invalid on the server.'
                : 'This student has not uploaded a CV yet.';
            Yii::$app->session->setFlash('error', $message);
            return $this->redirect(['view', 'id' => $id]);
        }

        return Yii::$app->response->sendFile(
            $absolutePath,
            $service->downloadFilename($student),
            [
                'mimeType' => $service->mimeType($absolutePath),
                'inline' => $inline,
            ]
        );
    }

    public function actionCreate()
    {
        $model = new Student();
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
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
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

    private function sendIdDocumentResponse(int $id, bool $inline): Response
    {
        $student = $this->findModel($id);
        $service = new StudentIdDocumentService();
        $absolutePath = $service->resolveAbsolutePath($student);
        if ($absolutePath === null) {
            Yii::$app->session->setFlash('error', 'Student ID document not found.');
            return $this->redirect(['view', 'id' => $id]);
        }

        return Yii::$app->response->sendFile(
            $absolutePath,
            $service->downloadFilename($student),
            [
                'mimeType' => $service->mimeType($absolutePath),
                'inline' => $inline,
            ]
        );
    }

    protected function findModel($id)
    {
        if (($model = Student::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
} 