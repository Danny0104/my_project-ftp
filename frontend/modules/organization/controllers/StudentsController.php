<?php

namespace frontend\modules\organization\controllers;

use common\models\Application;
use common\models\OrgCandidateNote;
use common\models\Student;
use common\services\ApplicationWorkflowService;
use common\services\OrgInterviewScheduleService;
use common\services\OrganizationScopeService;
use common\services\StudentCvService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;

class StudentsController extends BaseController
{
    protected function navKey(): string
    {
        return 'students';
    }

    public function actionIndex()
    {
        $q = trim((string) Yii::$app->request->get('q', ''));
        $status = trim((string) Yii::$app->request->get('status', ''));

        $studentIds = (new OrganizationScopeService())->applicationQuery($this->orgId())
            ->select('a.student_id')
            ->distinct()
            ->column();

        $query = Student::find()
            ->where(['id' => $studentIds ?: [0]])
            ->with(['user'])
            ->orderBy(['id' => SORT_DESC]);

        if ($q !== '') {
            $query->andWhere(['or',
                ['like', 'university', $q],
                ['like', 'field_of_study', $q],
                ['like', 'student_id', $q],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 12],
        ]);

        $this->view->title = 'Students';

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function actionView($id)
    {
        $student = $this->findStudentForOrg((int) $id);
        $applications = (new OrganizationScopeService())->applicationQuery($this->orgId())
            ->andWhere(['a.student_id' => $student->id])
            ->with(['position'])
            ->orderBy(['a.created_at' => SORT_DESC])
            ->all();

        $notes = OrgCandidateNote::find()
            ->where(['organization_id' => $this->orgId(), 'student_id' => $student->id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $matchScore = $this->computeMatchScore($student, $applications);

        $this->view->title = $student->user->username ?? 'Candidate';

        return $this->render('view', [
            'student' => $student,
            'applications' => $applications,
            'notes' => $notes,
            'matchScore' => $matchScore,
        ]);
    }

    public function actionAddNote()
    {
        $studentId = (int) Yii::$app->request->post('student_id');
        $this->findStudentForOrg($studentId);

        $note = new OrgCandidateNote();
        $note->organization_id = $this->orgId();
        $note->student_id = $studentId;
        $note->application_id = (int) Yii::$app->request->post('application_id') ?: null;
        $note->author_user_id = Yii::$app->user->id;
        $note->note = trim((string) Yii::$app->request->post('note', ''));

        if ($note->note === '' || !$note->save()) {
            return $this->jsonError('Could not save note.', ['errors' => $note->errors]);
        }

        $this->audit('student.note_added', ['student_id' => $studentId]);
        return $this->jsonSuccess(['id' => $note->id]);
    }

    public function actionUpdateStatus()
    {
        $applicationId = (int) Yii::$app->request->post('application_id');
        $status = (string) Yii::$app->request->post('status');
        $app = $this->ensureApplicationBelongsToOrg($applicationId);

        $workflow = new ApplicationWorkflowService();
        $result = $workflow->updateStatus($app, $status, (int) Yii::$app->user->id, $this->orgId());
        if (!$result['success']) {
            return $this->jsonError($result['message']);
        }

        $this->audit('student.status_updated', [
            'application_id' => $applicationId,
            'status' => $status,
        ]);
        \common\models\PlatformActivityLog::log('application.status_updated', 'application', $applicationId, [
            'organization_id' => $this->orgId(),
            'to' => $status,
        ]);

        return $this->jsonSuccess([], $result['message']);
    }

    public function actionDownloadCv($id)
    {
        $student = $this->findStudentForOrg((int) $id);
        $service = new StudentCvService();
        $absolutePath = $service->resolveAbsolutePath($student);

        if ($absolutePath === null) {
            throw new NotFoundHttpException('CV file not found for this candidate.');
        }

        $this->audit('student.cv_download', ['student_id' => (int) $student->id]);

        return Yii::$app->response->sendFile(
            $absolutePath,
            $service->downloadFilename($student),
            [
                'mimeType' => $service->mimeType($absolutePath),
                'inline' => false,
            ]
        );
    }

    public function actionScheduleInterview()
    {
        $applicationId = (int) Yii::$app->request->post('application_id');
        $app = $this->ensureApplicationBelongsToOrg($applicationId);
        $app->populateRelation('position', $app->position);

        $scheduledAt = strtotime((string) Yii::$app->request->post('scheduled_at', '+3 days'));
        if ($scheduledAt === false) {
            $scheduledAt = strtotime('+3 days');
        }

        $result = (new OrgInterviewScheduleService())->scheduleForApplication($app, $this->orgId(), [
            'scheduled_at' => $scheduledAt,
            'meeting_link' => trim((string) Yii::$app->request->post('meeting_link', '')),
            'interviewer_name' => trim((string) Yii::$app->request->post('interviewer_name', '')),
            'interview_stage' => (string) Yii::$app->request->post('interview_stage', OrgInterviewScheduleService::STAGE_DEFAULT),
        ]);

        if (!$result['success'] || !$result['interview']) {
            return $this->jsonError($result['message']);
        }

        if (!$result['already_exists'] && $app->status === Application::STATUS_ORG_APPROVED) {
            $app->status = Application::STATUS_UNIVERSITY_APPROVED;
            $app->save(false);
        }

        $this->audit('student.interview_scheduled', [
            'interview_id' => $result['interview']->id,
            'already_exists' => $result['already_exists'],
        ]);

        return $this->jsonSuccess([
            'interview_id' => $result['interview']->id,
            'already_exists' => $result['already_exists'],
        ], $result['message']);
    }

    private function findStudentForOrg(int $id): Student
    {
        $exists = (new OrganizationScopeService())->applicationQuery($this->orgId())
            ->andWhere(['a.student_id' => $id])
            ->exists();
        if (!$exists) {
            throw new NotFoundHttpException('Student not found.');
        }
        $student = Student::find()->where(['id' => $id])->with('user')->one();
        if (!$student) {
            throw new NotFoundHttpException('Student not found.');
        }
        return $student;
    }

    private function computeMatchScore(Student $student, array $applications): int
    {
        $score = 40;
        if (!empty($student->cv)) {
            $score += 15;
        }
        if (!empty($student->field_of_study)) {
            $score += 15;
        }
        if ($student->gpa !== null && $student->gpa >= 3.0) {
            $score += 10;
        }
        if (count($applications) > 0) {
            $score += min(20, count($applications) * 4);
        }
        return min(98, $score);
    }
}
