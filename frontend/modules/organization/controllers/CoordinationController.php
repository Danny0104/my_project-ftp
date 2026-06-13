<?php

namespace frontend\modules\organization\controllers;

use common\models\OrgCoordination;
use common\models\Student;
use common\services\OrganizationScopeService;
use Yii;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class CoordinationController extends BaseController
{
    protected function navKey(): string
    {
        return 'university';
    }

    public function actionIndex()
    {
        $records = OrgCoordination::find()
            ->where(['organization_id' => $this->orgId()])
            ->with(['student.user'])
            ->orderBy(['updated_at' => SORT_DESC])
            ->all();

        $this->view->title = 'University Coordination';

        return $this->render('index', [
            'records' => $records,
            'students' => Student::find()
                ->where(['id' => (new OrganizationScopeService())->applicationQuery($this->orgId())->select('a.student_id')->distinct()->column()])
                ->with('user')
                ->limit(50)
                ->all(),
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel((int) $id);
        $this->view->title = 'Coordination — ' . ($model->student->user->username ?? 'Student');

        return $this->render('view', ['model' => $model]);
    }

    public function actionSave()
    {
        $id = (int) Yii::$app->request->post('id');
        $model = $id ? $this->findModel($id) : new OrgCoordination();
        $model->organization_id = $this->orgId();
        $model->load(Yii::$app->request->post());

        if (!$model->student_id) {
            return $this->jsonError('Student is required.');
        }

        $file = UploadedFile::getInstanceByName('document');
        if ($file) {
            $dir = Yii::getAlias('@frontend/web/uploads/coordination');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $name = 'coord_' . $this->orgId() . '_' . time() . '.' . $file->extension;
            if ($file->saveAs($dir . '/' . $name)) {
                $model->document_path = 'uploads/coordination/' . $name;
            }
        }

        if (!$model->save()) {
            return $this->jsonError('Validation failed.', ['errors' => $model->errors]);
        }

        $this->audit($id ? 'coordination.updated' : 'coordination.created', ['id' => $model->id]);
        return $this->jsonSuccess(['id' => $model->id]);
    }

    public function actionApprove()
    {
        $model = $this->findModel((int) Yii::$app->request->post('id'));
        $model->approval_status = (string) Yii::$app->request->post('approval_status', 'approved');
        $model->workflow_status = (string) Yii::$app->request->post('workflow_status', $model->workflow_status);
        $model->save(false);

        $this->audit('coordination.approval', ['id' => $model->id, 'status' => $model->approval_status]);
        return $this->jsonSuccess();
    }

    private function findModel(int $id): OrgCoordination
    {
        $model = OrgCoordination::findOne(['id' => $id, 'organization_id' => $this->orgId()]);
        if (!$model) {
            throw new NotFoundHttpException('Record not found.');
        }
        return $model;
    }
}
