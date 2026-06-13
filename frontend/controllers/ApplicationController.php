<?php
namespace frontend\controllers;

use common\traits\RoleDashboardLayoutTrait;
use common\models\Application;
use common\models\Organization;
use common\models\Student;
use common\models\OrgTeamActivity;
use common\models\PlatformActivityLog;
use common\services\ApplicationQuestionService;
use common\services\ApplicationWizardService;
use common\services\EligibilityService;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use Yii;
use yii\db\IntegrityException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class ApplicationController extends Controller
{
    use RoleDashboardLayoutTrait;

    public $layout = 'internal';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'apply' => ['POST'],
                    'withdraw' => ['POST'],
                    'check-eligibility' => ['GET', 'POST'],
                    'update-stage' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        $user = Yii::$app->user->identity;
        if ($user && $user->role === 'student') {
            $this->layout = 'student';
            $this->view->params['ftpNavActive'] = 'applications';
        }

        if ($user && $user->role === 'organization') {
            $this->layout = 'organization';
            $this->view->params['orgNavActive'] = 'applications';

            $organization = Organization::findOrCreateForUserId((int) $user->id);
            if (!$organization) {
                Yii::$app->session->setFlash('error', 'Unable to load organization profile. Please try again or contact support.');
                return $this->redirect(['profile/organization']);
            }

            $applications = Application::find()
                ->alias('a')
                ->innerJoin(['p' => \common\models\Position::tableName()], 'p.id = a.position_id')
                ->where(['p.organization_id' => $organization->id])
                ->with(['student.user', 'position'])
                ->orderBy(['a.created_at' => SORT_DESC])
                ->all();

            return $this->render('ats', [
                'applications' => $applications,
                'organization' => $organization,
            ]);
        }

        $userId = Yii::$app->user->id;
        $applications = Application::find()
            ->where(['user_id' => $userId])
            ->with(['position', 'position.organization'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('index', ['applications' => $applications]);
    }

    public function actionUpdateStage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $user = Yii::$app->user->identity;

        if (!$user || $user->role !== 'organization') {
            return ['success' => false, 'message' => 'Only organizations can update applicant stages.'];
        }

        $organization = Organization::findOrCreateForUserId((int) $user->id);
        if (!$organization) {
            return ['success' => false, 'message' => 'Unable to load organization profile.'];
        }

        $id = (int) Yii::$app->request->post('id');
        $status = (string) Yii::$app->request->post('status');
        $allowed = [
            Application::STATUS_PENDING,
            Application::STATUS_UNDER_REVIEW,
            Application::STATUS_ORG_APPROVED,
            Application::STATUS_UNIVERSITY_APPROVED,
            Application::STATUS_APPROVED,
            Application::STATUS_REJECTED,
            Application::STATUS_COMPLETED,
        ];
        if (!in_array($status, $allowed, true)) {
            return ['success' => false, 'message' => 'Invalid stage selected.'];
        }

        $application = Application::find()
            ->alias('a')
            ->innerJoin(['p' => \common\models\Position::tableName()], 'p.id = a.position_id')
            ->where(['a.id' => $id, 'p.organization_id' => $organization->id])
            ->one();

        if (!$application) {
            return ['success' => false, 'message' => 'Application not found.'];
        }

        $workflow = new \common\services\ApplicationWorkflowService();
        $result = $workflow->updateStatus($application, $status, (int) $user->id, (int) $organization->id);
        if (!$result['success']) {
            return ['success' => false, 'message' => $result['message']];
        }

        OrgTeamActivity::log($organization->id, 'application.stage_updated', (int) $user->id, [
            'application_id' => $application->id,
            'to' => $status,
        ]);
        PlatformActivityLog::log('application.stage_updated', 'application', (int) $application->id, [
            'organization_id' => $organization->id,
            'to' => $status,
        ], (int) $user->id);

        return [
            'success' => true,
            'message' => $result['message'],
            'status' => $application->status,
        ];
    }

    public function actionApply($position_id)
    {
        $isAjax = Yii::$app->request->isAjax;
        if ($isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
        }

        $user = Yii::$app->user->identity;
        if (!$user || $user->role !== 'student') {
            if ($isAjax) {
                return ['success' => false, 'message' => 'Only students may submit applications.'];
            }
            throw new ForbiddenHttpException('Only students may submit applications.');
        }

        if ((string) Yii::$app->request->post('declaration', '') !== '1') {
            $message = 'Please confirm the declaration before submitting.';
            if ($isAjax) {
                return ['success' => false, 'message' => $message];
            }
            Yii::$app->session->setFlash('error', $message);
            return $this->redirect(['position/view', 'id' => (int) $position_id]);
        }

        $userId = (int) $user->id;
        $position = \common\models\Position::findOne($position_id);

        $publicService = new \common\services\PublicPositionService();
        if (!$position || !$publicService->isAcceptingApplications($position)) {
            $message = 'This internship is closed and no longer accepting applications.';
            if ($isAjax) {
                return ['success' => false, 'message' => $message];
            }
            Yii::$app->session->setFlash('error', $message);
            return $this->redirect(['position/index']);
        }

        $student = Student::findOne(['user_id' => $userId]);
        if (!$student) {
            $message = 'Please complete your student profile before applying.';
            if ($isAjax) {
                return ['success' => false, 'message' => $message];
            }
            Yii::$app->session->setFlash('error', $message);
            return $this->redirect(['profile/edit-profile']);
        }

        /** @var EligibilityService $eligibility */
        $eligibility = Yii::$app->eligibility;
        $result = $eligibility->evaluate($student, $position, 'apply_attempt');
        $profileCompletion = $eligibility->profileCompletionPercent($student);
        $wizardService = new ApplicationWizardService();
        $profileReadiness = $wizardService->buildProfileReadiness($student, $user, $result, $profileCompletion);

        if (!$profileReadiness['ready']) {
            $message = $profileReadiness['issues'][0] ?? $result->getPrimaryMessage();
            if ($isAjax) {
                return ['success' => false, 'message' => $message, 'errors' => $profileReadiness['issues']];
            }
            Yii::$app->session->setFlash('error', $message);
            return $this->redirect(['position/view', 'id' => (int) $position_id]);
        }

        if (!$result->eligible) {
            $message = $result->getPrimaryMessage();
            if ($isAjax) {
                return ['success' => false, 'message' => $message];
            }
            Yii::$app->session->setFlash('error', $message);
            return $this->redirect(['position/index']);
        }

        $questionService = new ApplicationQuestionService();
        $textAnswers = (array) Yii::$app->request->post('ApplicationAnswers', []);
        $questionFiles = $this->collectApplicationAnswerFiles($position, $questionService);
        $answerValidation = $questionService->validateAnswers($position, $textAnswers, $questionFiles);
        if (!$answerValidation['valid']) {
            if ($isAjax) {
                return [
                    'success' => false,
                    'message' => $answerValidation['errors'][0] ?? 'Please complete all required application questions.',
                    'errors' => $answerValidation['errors'],
                ];
            }
            Yii::$app->session->setFlash('error', $answerValidation['errors'][0] ?? 'Please complete all required application questions.');
            return $this->redirect(['position/view', 'id' => (int) $position_id]);
        }

        $positionId = (int) $position_id;
        $existing = Application::findForUserPosition($userId, $positionId);

        if ($existing && !$existing->isReapplyable()) {
            $message = 'You have already applied for this internship.';
            if ($isAjax) {
                return ['success' => false, 'message' => $message, 'applicationId' => (int) $existing->id];
            }
            Yii::$app->session->setFlash('warning', $message);
            return $this->redirect(['application/view', 'id' => $existing->id]);
        }

        if ($existing) {
            $application = $existing;
            $application->scenario = Application::SCENARIO_APPLY;
            $application->status = Application::STATUS_PENDING;
            $application->feedback = null;
        } else {
            $application = new Application();
            $application->scenario = Application::SCENARIO_APPLY;
            $application->user_id = $userId;
            $application->student_id = $student->id;
            $application->position_id = $positionId;
            $application->status = Application::STATUS_PENDING;
        }

        $answers = $answerValidation['answers'];
        if (!empty($student->cv)) {
            $application->resume_url = (string) $student->cv;
        }

        try {
            $saved = $application->save();
        } catch (IntegrityException $e) {
            $saved = false;
            $active = Application::findActiveForUserPosition($userId, $positionId);
            if ($active) {
                $message = 'You have already applied for this internship.';
                if ($isAjax) {
                    return ['success' => false, 'message' => $message, 'applicationId' => (int) $active->id];
                }
                Yii::$app->session->setFlash('warning', $message);
                return $this->redirect(['application/view', 'id' => $active->id]);
            }
            $message = 'Could not submit application. Please refresh and try again.';
            if ($isAjax) {
                return ['success' => false, 'message' => $message];
            }
            Yii::$app->session->setFlash('error', $message);
            return $this->redirect(['position/view', 'id' => $positionId]);
        }

        if ($saved) {
            $questionService->attachUploadedFiles((int) $application->id, $answers, $questionFiles);
            $application->application_answers = $questionService->encodeAnswers($answers);
            $application->save(false, ['application_answers']);

            $studentName = $student->user->username ?? 'A student';
            \common\models\Notification::createFromOrganization(
                $position->organization->user_id ?? 0,
                'New Application Received',
                sprintf('%s submitted an application for %s.', $studentName, $position->title),
                $position->organization_id,
                '/application/index',
                'View Application',
                [
                    'notification_type' => \common\models\Notification::TYPE_APPLICATION,
                    'category' => \common\models\Notification::CATEGORY_APPLICATIONS,
                    'related_id' => (int) $application->id,
                ]
            );

            PlatformActivityLog::log('application.submitted', 'application', (int) $application->id, [
                'position_id' => $positionId,
                'student_id' => $student->id,
            ], $userId);

            if ($isAjax) {
                return [
                    'success' => true,
                    'message' => 'Application submitted successfully!',
                    'applicationId' => (int) $application->id,
                    'applicationsUrl' => \yii\helpers\Url::to(['application/index']),
                ];
            }

            Yii::$app->session->setFlash('success', 'Application submitted successfully!');
        } else {
            $errors = array_values($application->getFirstErrors());
            $message = $errors[0] ?? 'Failed to submit application. Please try again.';
            if ($isAjax) {
                return ['success' => false, 'message' => $message, 'errors' => $errors];
            }
            Yii::$app->session->setFlash('error', $message);
        }

        return $this->redirect(['application/index']);
    }

    /**
     * @return array<string, UploadedFile>
     */
    private function collectApplicationAnswerFiles(\common\models\Position $position, ApplicationQuestionService $questionService): array
    {
        $files = [];
        foreach ($questionService->getQuestions($position) as $question) {
            if (($question['type'] ?? '') !== ApplicationQuestionService::TYPE_FILE) {
                continue;
            }
            $id = (string) $question['id'];
            $file = UploadedFile::getInstanceByName('ApplicationAnswers[' . $id . ']');
            if ($file instanceof UploadedFile) {
                $files[$id] = $file;
            }
        }

        return $files;
    }

    /**
     * AJAX eligibility check for opportunity cards (backend-validated).
     */
    public function actionCheckEligibility($position_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;
        if (!$user || $user->role !== 'student') {
            return ['success' => false, 'message' => 'Authentication required.'];
        }

        $student = Student::findOne(['user_id' => $user->id]);
        $position = \common\models\Position::findOne($position_id);
        if (!$student || !$position) {
            return ['success' => false, 'message' => 'Invalid request.'];
        }

        $publicService = new \common\services\PublicPositionService();
        if (!$publicService->isAcceptingApplications($position)) {
            return [
                'success' => true,
                'data' => [
                    'eligible' => false,
                    'matchScore' => 0,
                    'reasons' => [['code' => 'deadline_passed', 'message' => 'Applications are closed for this opportunity.']],
                ],
            ];
        }

        $result = Yii::$app->eligibility->evaluate($student, $position, 'ajax_check');
        return ['success' => true, 'data' => $result->toArray()];
    }

    public function actionMyApplications()
    {
        $userId = Yii::$app->user->id;
        $dataProvider = new \yii\data\ActiveDataProvider([
            'query' => Application::find()->where(['user_id' => $userId])->with(['position', 'position.organization']),
            'pagination' => [
                'pageSize' => 10,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
        ]);
        return $this->render('my-applications', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionWithdraw($id)
    {
        $userId = Yii::$app->user->id;
        $application = Application::findOne(['id' => $id, 'user_id' => $userId]);

        if (!$application) {
            throw new \yii\web\NotFoundHttpException('Application not found.');
        }

        if (!$application->canWithdraw()) {
            Yii::$app->session->setFlash('error', 'This application cannot be withdrawn.');
            return $this->redirect(['my-applications']);
        }

        $application->status = Application::STATUS_WITHDRAWN;
        if ($application->save()) {
            Yii::$app->session->setFlash('success', 'Application withdrawn successfully.');
        } else {
            Yii::$app->session->setFlash('error', 'Failed to withdraw application.');
        }

        return $this->redirect(['my-applications']);
    }

    public function actionView($id)
    {
        $userId = Yii::$app->user->id;
        $application = Application::find()
            ->where(['id' => $id, 'user_id' => $userId])
            ->with(['position.organization'])
            ->one();

        if (!$application) {
            throw new \yii\web\NotFoundHttpException('Application not found.');
        }

        $user = Yii::$app->user->identity;
        if ($user && $user->role === 'student') {
            $this->layout = 'student';
            $this->view->params['ftpNavActive'] = 'applications';
        }

        return $this->render('view', [
            'model' => $application,
        ]);
    }
}
