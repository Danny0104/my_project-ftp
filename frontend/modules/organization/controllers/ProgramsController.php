<?php

namespace frontend\modules\organization\controllers;

use common\models\OrgInternshipProgram;
use common\models\OrgProgramStudent;
use common\models\Student;
use common\services\OrganizationScopeService;
use Yii;
use yii\web\NotFoundHttpException;

class ProgramsController extends BaseController
{
    protected function navKey(): string
    {
        return 'programs';
    }

    public function actionIndex()
    {
        $programs = OrgInternshipProgram::find()
            ->where(['organization_id' => $this->orgId()])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $this->view->title = 'Internship Programs';

        return $this->render('index', [
            'programs' => $programs,
        ]);
    }

    public function actionView($id)
    {
        $program = $this->findModel((int) $id);
        $enrollments = OrgProgramStudent::find()
            ->where(['program_id' => $program->id])
            ->with(['student.user'])
            ->all();

        $this->view->title = $program->title;

        return $this->render('view', [
            'program' => $program,
            'enrollments' => $enrollments,
            'students' => Student::find()
                ->where(['id' => (new OrganizationScopeService())->applicationQuery($this->orgId())->select('a.student_id')->distinct()->column()])
                ->with('user')
                ->limit(100)
                ->all(),
        ]);
    }

    public function actionSave()
    {
        $id = (int) Yii::$app->request->post('id');
        $model = $id ? $this->findModel($id) : new OrgInternshipProgram();
        $model->organization_id = $this->orgId();
        $model->load(Yii::$app->request->post());

        if (!$model->save()) {
            return $this->jsonError('Validation failed.', ['errors' => $model->errors]);
        }

        $this->audit($id ? 'program.updated' : 'program.created', ['id' => $model->id]);
        return $this->jsonSuccess(['id' => $model->id]);
    }

    public function actionEnroll()
    {
        $program = $this->findModel((int) Yii::$app->request->post('program_id'));
        $studentId = (int) Yii::$app->request->post('student_id');

        $exists = (new OrganizationScopeService())->applicationQuery($this->orgId())
            ->andWhere(['a.student_id' => $studentId])
            ->exists();
        if (!$exists) {
            return $this->jsonError('Student not in your applicant pool.');
        }

        $row = OrgProgramStudent::findOne(['program_id' => $program->id, 'student_id' => $studentId]);
        if (!$row) {
            $row = new OrgProgramStudent();
            $row->program_id = $program->id;
            $row->student_id = $studentId;
            $row->assigned_at = time();
        }
        $row->status = 'active';
        $row->progress_percent = (int) Yii::$app->request->post('progress_percent', $row->progress_percent ?? 0);

        if (!$row->save()) {
            return $this->jsonError('Enrollment failed.');
        }

        $enrolled = (int) OrgProgramStudent::find()->where(['program_id' => $program->id])->count();
        if ($program->capacity > 0) {
            $program->completion_percent = min(100, (int) round(100 * $enrolled / $program->capacity));
            $program->save(false);
        }

        $this->audit('program.enrolled', ['program_id' => $program->id, 'student_id' => $studentId]);
        return $this->jsonSuccess();
    }

    public function actionDelete()
    {
        $model = $this->findModel((int) Yii::$app->request->post('id'));
        $model->delete();
        $this->audit('program.deleted', ['id' => $model->id]);
        return $this->jsonSuccess();
    }

    private function findModel(int $id): OrgInternshipProgram
    {
        $model = OrgInternshipProgram::findOne(['id' => $id, 'organization_id' => $this->orgId()]);
        if (!$model) {
            throw new NotFoundHttpException('Program not found.');
        }
        return $model;
    }
}
