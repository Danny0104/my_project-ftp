<?php

namespace frontend\modules\organization\controllers;

use common\models\OrgInterview;
use common\models\Student;
use common\services\OrgInterviewScheduleService;
use common\services\OrganizationScopeService;
use Yii;
use yii\web\NotFoundHttpException;

class InterviewsController extends BaseController
{
    protected function navKey(): string
    {
        return 'interviews';
    }

    public function actionIndex()
    {
        $view = Yii::$app->request->get('view', 'list');
        if (!in_array($view, ['list', 'calendar', 'kanban'], true)) {
            $view = 'list';
        }

        $interviews = OrgInterviewScheduleService::listForOrganization($this->orgId());

        $upcoming = array_filter($interviews, static function (OrgInterview $i) {
            return $i->status === OrgInterview::STATUS_SCHEDULED && $i->scheduled_at >= time();
        });

        $this->view->title = 'Interviews';

        return $this->render('index', [
            'interviews' => $interviews,
            'upcoming' => $upcoming,
            'viewMode' => $view,
            'students' => Student::find()
                ->where(['id' => (new OrganizationScopeService())->applicationQuery($this->orgId())->select('a.student_id')->distinct()->column()])
                ->with('user')
                ->limit(50)
                ->all(),
        ]);
    }

    public function actionSchedule()
    {
        $model = new OrgInterview();
        $model->load(Yii::$app->request->post());

        $rawSchedule = Yii::$app->request->post('scheduled_at');
        $result = (new OrgInterviewScheduleService())->scheduleFromForm(
            $this->orgId(),
            $model,
            is_string($rawSchedule) ? $rawSchedule : null
        );

        if (!$result['success'] || !$result['interview']) {
            return $this->jsonError($result['message']);
        }

        $this->audit('interview.scheduled', [
            'id' => $result['interview']->id,
            'already_exists' => $result['already_exists'],
        ]);

        return $this->jsonSuccess([
            'id' => $result['interview']->id,
            'already_exists' => $result['already_exists'],
        ], $result['message']);
    }

    public function actionEvaluate()
    {
        $model = $this->findModel((int) Yii::$app->request->post('id'));
        $model->evaluation_score = (int) Yii::$app->request->post('evaluation_score');
        $model->evaluation_notes = trim((string) Yii::$app->request->post('evaluation_notes', ''));
        $model->status = OrgInterview::STATUS_COMPLETED;

        if (!$model->save()) {
            return $this->jsonError('Could not save evaluation.');
        }

        $this->audit('interview.evaluated', ['id' => $model->id]);
        return $this->jsonSuccess();
    }

    public function actionUpdateStatus()
    {
        $model = $this->findModel((int) Yii::$app->request->post('id'));
        $status = (string) Yii::$app->request->post('status');
        if (!isset(OrgInterview::statusOptions()[$status])) {
            return $this->jsonError('Invalid status.');
        }

        $previousStatus = $model->status;
        $model->status = $status;
        $model->save(false);

        if ($status === OrgInterview::STATUS_CANCELLED && $previousStatus !== OrgInterview::STATUS_CANCELLED) {
            OrgInterviewScheduleService::notifyStudentInterview($model, $this->orgId(), true);
        }

        $this->audit('interview.status', ['id' => $model->id, 'status' => $status]);
        return $this->jsonSuccess([], 'Interview status updated.');
    }

    public function actionUpdate()
    {
        $model = $this->findModel((int) Yii::$app->request->post('id'));
        $previousTime = (int) $model->scheduled_at;

        $model->load(Yii::$app->request->post());
        $rawSchedule = Yii::$app->request->post('scheduled_at');
        if (is_string($rawSchedule) && $rawSchedule !== '') {
            $model->scheduled_at = strtotime(str_replace('T', ' ', $rawSchedule)) ?: $model->scheduled_at;
        }

        if (!$model->save()) {
            return $this->jsonError('Could not update interview.', ['errors' => $model->errors]);
        }

        if ((int) $model->scheduled_at !== $previousTime) {
            OrgInterviewScheduleService::notifyStudentInterview($model, $this->orgId(), false, true);
        }

        $this->audit('interview.updated', ['id' => $model->id]);
        return $this->jsonSuccess(['id' => $model->id], 'Interview updated.');
    }

    public function actionDelete()
    {
        $model = $this->findModel((int) Yii::$app->request->post('id'));
        $model->delete();
        $this->audit('interview.deleted', ['id' => $model->id]);
        return $this->jsonSuccess();
    }

    private function findModel(int $id): OrgInterview
    {
        $model = OrgInterview::findOne(['id' => $id, 'organization_id' => $this->orgId()]);
        if (!$model) {
            throw new NotFoundHttpException('Interview not found.');
        }
        return $model;
    }
}
