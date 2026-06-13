<?php

namespace backend\controllers;

use backend\assets\AdminAnalyticsAsset;
use common\components\CacheHelper;
use common\components\SessionSecurity;
use common\models\AcademicFaculty;
use common\models\Admin;
use common\models\Application;
use common\models\EligibilityAuditLog;
use common\models\FieldOfStudy;
use common\models\PlatformActivityLog;
use common\models\LoginForm;
use common\models\Notification;
use common\models\Organization;
use common\models\PlatformRegulation;
use common\models\Position;
use common\models\Student;
use common\models\User;
use backend\assets\AdminPagesAsset;
use common\services\ApplicationWorkflowService;
use common\services\PlatformAdminDashboardService;
use common\services\PlatformAnalyticsExportService;
use common\services\PlatformAnalyticsService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SiteController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'send-announcement' => ['get', 'post'],
                    'analytics-data' => ['get'],
                    'analytics-export' => ['get'],
                    'save-faculty' => ['post'],
                    'delete-faculty' => ['post'],
                    'save-field' => ['post'],
                    'delete-field' => ['post'],
                    'save-regulation' => ['post'],
                    'delete-regulation' => ['post'],
                    'save-theme-preference' => ['post'],
                    'approve-organization' => ['post'],
                    'reject-organization' => ['post'],
                    'approve-user' => ['post'],
                    'reject-user' => ['post'],
                    'approve-application' => ['post'],
                    'reject-application' => ['post'],
                ],
            ],
        ];
    }

    private function requireAdminWrite(): void
    {
        $admin = Yii::$app->user->identity;
        if ($admin instanceof Admin && !$admin->canWrite()) {
            throw new ForbiddenHttpException('Your admin role is read-only.');
        }
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->redirect(['dash']);
    }

    public function actionLogin()
    {
        @set_time_limit(120);

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        if (Yii::$app->request->get('expired')) {
            Yii::$app->session->setFlash(
                'warning',
                'Your session has expired due to inactivity. Please log in again.'
            );
        }

        $this->layout = 'blank';

        $model = new LoginForm();
        $model->isAdmin = true;
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['dash']);
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    public function actionLogout()
    {
        $type = SessionSecurity::normalizeLogoutType(
            Yii::$app->request->post('type')
        );

        SessionSecurity::performFullLogout();

        if ($type === 'auto') {
            return $this->redirect(['site/login', 'expired' => 1]);
        }

        return $this->redirect(['site/index']);
    }

    public function actionDash()
    {
        $this->view->params['apNavActive'] = 'dashboard';
        AdminPagesAsset::register($this->view);

        $dashboard = (new PlatformAdminDashboardService())->getExecutiveDashboard();

        return $this->render('dash', $dashboard);
    }

    public function actionAnalytics()
    {
        $this->view->params['apNavActive'] = 'analytics';
        AdminAnalyticsAsset::register($this->view);

        [$fromTs, $toTs, $from, $to] = $this->resolveAnalyticsDateRange();
        $filters = $this->resolveAnalyticsFilters();

        $service = new PlatformAnalyticsService();
        $metrics = $service->getDashboardMetrics($fromTs, $toTs, $filters);

        return $this->render('analytics', [
            'metrics' => $metrics,
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'filterOptions' => $service->getFilterOptions(),
        ]);
    }

    public function actionAnalyticsData()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        [$fromTs, $toTs] = $this->resolveAnalyticsDateRange();
        $filters = $this->resolveAnalyticsFilters();
        $service = new PlatformAnalyticsService();

        return [
            'success' => true,
            'metrics' => $service->getDashboardMetrics($fromTs, $toTs, $filters),
        ];
    }

    public function actionAnalyticsExport()
    {
        $format = strtolower((string) Yii::$app->request->get('format', 'csv'));
        [$fromTs, $toTs] = $this->resolveAnalyticsDateRange();
        $filters = $this->resolveAnalyticsFilters();

        $exporter = new PlatformAnalyticsExportService();
        $dateSlug = date('Y-m-d');

        Yii::$app->response->format = Response::FORMAT_RAW;

        switch ($format) {
            case 'xlsx':
            case 'excel':
                Yii::$app->response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
                Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="platform-analytics-' . $dateSlug . '.xls"');
                return $exporter->exportExcel($fromTs, $toTs, $filters);

            case 'pdf':
                Yii::$app->response->headers->set('Content-Type', 'text/html; charset=UTF-8');
                Yii::$app->response->headers->set('Content-Disposition', 'inline; filename="platform-analytics-' . $dateSlug . '.html"');
                return $exporter->exportPdfHtml($fromTs, $toTs, $filters);

            case 'csv':
            default:
                Yii::$app->response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
                Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="platform-analytics-' . $dateSlug . '.csv"');
                return $exporter->exportCsv($fromTs, $toTs, $filters);
        }
    }

    /** @return array{0:int,1:int,2:string,3:string} */
    private function resolveAnalyticsDateRange(): array
    {
        $from = Yii::$app->request->get('from');
        $to = Yii::$app->request->get('to');

        if (!$from && !$to) {
            $fromTs = strtotime('first day of this month 00:00:00');
            $toTs = strtotime('last day of this month 23:59:59');
        } else {
            $toTs = $to ? strtotime($to . ' 23:59:59') : time();
            $fromTs = $from ? strtotime($from . ' 00:00:00') : strtotime('-1 month', $toTs);
        }

        return [$fromTs, $toTs, date('Y-m-d', $fromTs), date('Y-m-d', $toTs)];
    }

    /** @return array{department?:string,category?:string,status?:string,organization_id?:int} */
    private function resolveAnalyticsFilters(): array
    {
        $req = Yii::$app->request;
        $filters = [];
        $department = trim((string) $req->get('department', ''));
        $category = trim((string) $req->get('category', ''));
        $status = trim((string) $req->get('status', ''));
        $orgId = (int) $req->get('organization_id', 0);

        if ($department !== '') {
            $filters['department'] = $department;
        }
        if ($category !== '') {
            $filters['category'] = $category;
        }
        if ($status !== '') {
            $filters['status'] = $status;
        }
        if ($orgId > 0) {
            $filters['organization_id'] = $orgId;
        }
        return $filters;
    }

    public function actionFaculties()
    {
        $this->view->params['apNavActive'] = 'faculties';
        $fields = FieldOfStudy::find()->with(['academicFaculty'])->orderBy(['category' => SORT_ASC, 'name' => SORT_ASC])->all();
        $faculties = AcademicFaculty::find()->orderBy(['name' => SORT_ASC])->all();
        return $this->render('faculties', [
            'fields' => $fields,
            'faculties' => $faculties,
        ]);
    }

    public function actionRegulations()
    {
        $this->view->params['apNavActive'] = 'regulations';
        $regulations = PlatformRegulation::find()->orderBy(['key' => SORT_ASC])->all();
        return $this->render('regulations', ['regulations' => $regulations]);
    }

    public function actionSaveFaculty()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $post = Yii::$app->request->post('AcademicFaculty', []);
        $id = (int) ($post['id'] ?? 0);
        $model = $id ? AcademicFaculty::findOne($id) : new AcademicFaculty();
        if ($id && !$model) {
            return ['success' => false, 'message' => 'Faculty not found.'];
        }

        $model->load(Yii::$app->request->post());
        if (!$model->save()) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $model->errors];
        }

        PlatformActivityLog::log(
            $id ? 'faculty.updated' : 'faculty.created',
            'academic_faculty',
            (int) $model->id,
            ['name' => $model->name]
        );

        return ['success' => true, 'message' => $id ? 'Faculty updated.' : 'Faculty created.', 'id' => $model->id];
    }

    public function actionDeleteFaculty()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = AcademicFaculty::findOne((int) Yii::$app->request->post('id'));
        if (!$model) {
            return ['success' => false, 'message' => 'Faculty not found.'];
        }

        $linked = (int) FieldOfStudy::find()->where(['faculty_id' => $model->id])->count();
        if ($linked > 0) {
            return ['success' => false, 'message' => 'Cannot delete a faculty that still has linked fields.'];
        }

        $name = $model->name;
        $model->delete();
        PlatformActivityLog::log('faculty.deleted', 'academic_faculty', null, ['name' => $name]);

        return ['success' => true, 'message' => 'Faculty deleted.'];
    }

    public function actionSaveField()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $post = Yii::$app->request->post('FieldOfStudy', []);
        $id = (int) ($post['id'] ?? 0);
        $model = $id ? FieldOfStudy::findOne($id) : new FieldOfStudy();
        if ($id && !$model) {
            return ['success' => false, 'message' => 'Field not found.'];
        }

        $model->load(Yii::$app->request->post());
        if ($model->isNewRecord) {
            $model->created_at = time();
        }
        if (!$model->slug) {
            $model->slug = \yii\helpers\Inflector::slug($model->name);
        }
        if (!$model->save()) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $model->errors];
        }

        PlatformActivityLog::log(
            $id ? 'field.updated' : 'field.created',
            'field_of_study',
            (int) $model->id,
            ['name' => $model->name]
        );

        return ['success' => true, 'message' => $id ? 'Field updated.' : 'Field created.', 'id' => $model->id];
    }

    public function actionDeleteField()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = FieldOfStudy::findOne((int) Yii::$app->request->post('id'));
        if (!$model) {
            return ['success' => false, 'message' => 'Field not found.'];
        }

        $name = $model->name;
        $model->is_active = false;
        $model->save(false, ['is_active']);
        PlatformActivityLog::log('field.deactivated', 'field_of_study', (int) $model->id, ['name' => $name]);

        return ['success' => true, 'message' => 'Field deactivated.'];
    }

    public function actionSaveRegulation()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $post = Yii::$app->request->post('PlatformRegulation', []);
        $id = (int) ($post['id'] ?? 0);
        $model = $id ? PlatformRegulation::findOne($id) : new PlatformRegulation();
        if ($id && !$model) {
            return ['success' => false, 'message' => 'Regulation not found.'];
        }

        $model->load(Yii::$app->request->post());
        $model->updated_at = time();
        if (!$model->save()) {
            return ['success' => false, 'message' => 'Validation failed.', 'errors' => $model->errors];
        }

        PlatformRegulation::clearCache();
        PlatformActivityLog::log(
            $id ? 'regulation.updated' : 'regulation.created',
            'platform_regulation',
            (int) $model->id,
            ['key' => $model->key]
        );

        return ['success' => true, 'message' => $id ? 'Regulation updated.' : 'Regulation created.', 'id' => $model->id];
    }

    public function actionDeleteRegulation()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $model = PlatformRegulation::findOne((int) Yii::$app->request->post('id'));
        if (!$model) {
            return ['success' => false, 'message' => 'Regulation not found.'];
        }

        $key = $model->key;
        $model->delete();
        PlatformRegulation::clearCache();
        PlatformActivityLog::log('regulation.deleted', 'platform_regulation', null, ['key' => $key]);

        return ['success' => true, 'message' => 'Regulation deleted.'];
    }

    public function actionSaveThemePreference()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $theme = Yii::$app->request->post('theme');
        if (!in_array($theme, ['light', 'dark', 'system'], true)) {
            return ['success' => false, 'message' => 'Invalid theme selection.'];
        }

        $admin = Yii::$app->user->identity;
        if ($admin instanceof Admin) {
            $prefs = $admin->preferences ? json_decode($admin->preferences, true) : [];
            if (!is_array($prefs)) {
                $prefs = [];
            }
            $prefs['theme'] = $theme;
            $admin->preferences = json_encode($prefs);
            $admin->save(false, ['preferences']);
            PlatformActivityLog::log('admin.theme_updated', 'admin', (int) $admin->id, ['theme' => $theme]);
        }

        return ['success' => true, 'message' => 'Theme preference saved.', 'theme' => $theme];
    }

    public function actionAuditLogs()
    {
        $this->view->params['apNavActive'] = 'audit';

        if ((int) Yii::$app->request->get('export') === 1) {
            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="audit-logs.csv"');

            $rows = PlatformActivityLog::find()->orderBy(['created_at' => SORT_DESC])->limit(5000)->all();
            $out = fopen('php://temp', 'r+');
            fputcsv($out, ['id', 'action', 'entity_type', 'entity_id', 'user_id', 'created_at']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->id,
                    $row->action,
                    $row->entity_type,
                    $row->entity_id,
                    $row->user_id,
                    date('Y-m-d H:i:s', (int) $row->created_at),
                ]);
            }
            rewind($out);
            return stream_get_contents($out);
        }

        $eligibilityProvider = new \yii\data\ActiveDataProvider([
            'query' => EligibilityAuditLog::find()->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 25],
        ]);
        $activityProvider = new \yii\data\ActiveDataProvider([
            'query' => PlatformActivityLog::find()->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 25, 'pageParam' => 'activity-page'],
        ]);
        return $this->render('audit-logs', [
            'dataProvider' => $eligibilityProvider,
            'activityProvider' => $activityProvider,
        ]);
    }

    public function actionApprovals()
    {
        $this->view->params['apNavActive'] = 'approvals';
        $pendingApplications = Application::find()
            ->where(['status' => [Application::STATUS_PENDING, Application::STATUS_UNDER_REVIEW]])
            ->with(['student.user', 'position'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(20)
            ->all();
        $pendingUsers = User::find()
            ->where(['status' => [User::STATUS_PENDING, User::STATUS_INACTIVE]])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10)
            ->all();

        $pendingOrganizations = Organization::find()
            ->where(['verification_status' => Organization::VERIFICATION_PENDING])
            ->with('user')
            ->orderBy(['id' => SORT_DESC])
            ->limit(20)
            ->all();

        return $this->render('approvals', [
            'pendingApplications' => $pendingApplications,
            'pendingUsers' => $pendingUsers,
            'pendingOrganizations' => $pendingOrganizations,
        ]);
    }

    public function actionApproveOrganization()
    {
        $this->requireAdminWrite();
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = (int) Yii::$app->request->post('id');
        $organization = Organization::findOne($id);
        if (!$organization) {
            return ['success' => false, 'message' => 'Organization not found.'];
        }

        $organization->verification_status = Organization::VERIFICATION_APPROVED;
        $organization->save(false, ['verification_status']);

        $user = $organization->user;
        if ($user && (int) $user->status === User::STATUS_INACTIVE && $user->verification_token === null) {
            $user->status = User::STATUS_ACTIVE;
            $user->save(false, ['status']);
        }

        if ($user) {
            Notification::createFromAdmin(
                (int) $user->id,
                'Organization verified',
                'Your organization account has been verified. You can now manage internships and recruitment.',
                (int) Yii::$app->user->id,
                Yii::$app->urlManager->createUrl(['/dashboard']),
                'Go to dashboard'
            );
        }

        PlatformActivityLog::log('organization.verified', 'organization', (int) $organization->id, [], (int) Yii::$app->user->id);

        return ['success' => true, 'message' => 'Organization approved.'];
    }

    public function actionRejectOrganization()
    {
        $this->requireAdminWrite();
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = (int) Yii::$app->request->post('id');
        $reason = trim((string) Yii::$app->request->post('reason', ''));
        $organization = Organization::findOne($id);
        if (!$organization) {
            return ['success' => false, 'message' => 'Organization not found.'];
        }

        $organization->verification_status = Organization::VERIFICATION_REJECTED;
        $organization->save(false, ['verification_status']);

        $user = $organization->user;
        if ($user) {
            $message = 'Your organization verification was not approved.';
            if ($reason !== '') {
                $message .= ' Reason: ' . $reason;
            }
            Notification::createFromAdmin(
                (int) $user->id,
                'Organization verification declined',
                $message,
                (int) Yii::$app->user->id
            );
        }

        PlatformActivityLog::log('organization.verification_rejected', 'organization', (int) $organization->id, [
            'reason' => $reason,
        ], (int) Yii::$app->user->id);

        return ['success' => true, 'message' => 'Organization verification rejected.'];
    }

    public function actionApproveUser()
    {
        $this->requireAdminWrite();
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = (int) Yii::$app->request->post('id');
        $user = User::findOne($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        if (!in_array((int) $user->status, [User::STATUS_PENDING, User::STATUS_INACTIVE], true)) {
            return ['success' => false, 'message' => 'User is not pending approval.'];
        }

        $user->status = User::STATUS_ACTIVE;
        $user->verification_token = null;
        $user->save(false, ['status', 'verification_token']);

        Notification::createFromAdmin(
            (int) $user->id,
            'Account approved',
            'Your account has been approved. You can now sign in and use the platform.',
            (int) Yii::$app->user->id
        );

        PlatformActivityLog::log('user.approved', 'user', (int) $user->id, [], (int) Yii::$app->user->id);

        return ['success' => true, 'message' => 'User activated.'];
    }

    public function actionRejectUser()
    {
        $this->requireAdminWrite();
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = (int) Yii::$app->request->post('id');
        $reason = trim((string) Yii::$app->request->post('reason', ''));
        $user = User::findOne($id);
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        $user->status = User::STATUS_INACTIVE;
        $user->save(false, ['status']);

        $message = 'Your registration was not approved.';
        if ($reason !== '') {
            $message .= ' Reason: ' . $reason;
        }
        Notification::createFromAdmin((int) $user->id, 'Registration declined', $message, (int) Yii::$app->user->id);

        PlatformActivityLog::log('user.rejected', 'user', (int) $user->id, ['reason' => $reason], (int) Yii::$app->user->id);

        return ['success' => true, 'message' => 'User registration rejected.'];
    }

    public function actionApproveApplication()
    {
        $this->requireAdminWrite();
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = (int) Yii::$app->request->post('id');
        $application = Application::findOne($id);
        if (!$application) {
            return ['success' => false, 'message' => 'Application not found.'];
        }

        $nextStatus = match ($application->status) {
            Application::STATUS_PENDING => Application::STATUS_UNDER_REVIEW,
            Application::STATUS_UNDER_REVIEW => Application::STATUS_ORG_APPROVED,
            Application::STATUS_ORG_APPROVED => Application::STATUS_UNIVERSITY_APPROVED,
            default => null,
        };

        if ($nextStatus === null) {
            return ['success' => false, 'message' => 'Application is not in an approvable state.'];
        }

        $result = (new ApplicationWorkflowService())->updateStatus(
            $application,
            $nextStatus,
            (int) Yii::$app->user->id
        );

        return [
            'success' => $result['success'],
            'message' => $result['message'],
        ];
    }

    public function actionRejectApplication()
    {
        $this->requireAdminWrite();
        Yii::$app->response->format = Response::FORMAT_JSON;
        $id = (int) Yii::$app->request->post('id');
        $reason = trim((string) Yii::$app->request->post('reason', ''));
        $application = Application::findOne($id);
        if (!$application) {
            return ['success' => false, 'message' => 'Application not found.'];
        }

        $result = (new ApplicationWorkflowService())->updateStatus(
            $application,
            Application::STATUS_REJECTED,
            (int) Yii::$app->user->id
        );

        if ($result['success'] && $reason !== '') {
            PlatformActivityLog::log('application.rejected', 'application', (int) $application->id, [
                'reason' => $reason,
            ], (int) Yii::$app->user->id);
        }

        return [
            'success' => $result['success'],
            'message' => $result['message'] ?: ($result['success'] ? 'Application rejected.' : 'Could not reject application.'),
        ];
    }

    public function actionSettings()
    {
        $this->view->params['apNavActive'] = 'settings';
        return $this->render('settings');
    }

    public function actionApiStats()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $stats = $this->getDashboardStats();
        return [
            'success' => true,
            'data' => $stats['counts'],
        ];
    }

    public function actionSendAnnouncement()
    {
        $this->view->params['apNavActive'] = 'announcements';

        if (Yii::$app->request->isPost) {
            $title = Yii::$app->request->post('title');
            $message = Yii::$app->request->post('message');
            $targetRole = Yii::$app->request->post('target_role', 'all');

            $users = User::find()->where(['status' => User::STATUS_ACTIVE]);
            if ($targetRole !== 'all') {
                $users->andWhere(['role' => $targetRole]);
            }
            $users = $users->all();

            $successCount = 0;
            foreach ($users as $user) {
                $notification = new \common\models\Notification();
                $notification->user_id = $user->id;
                $notification->title = $title;
                $notification->message = $message;
                $notification->sender_type = 'admin';
                $notification->sender_id = Yii::$app->user->id;
                $notification->created_at = time();
                $notification->updated_at = time();

                if ($notification->save()) {
                    $successCount++;
                }
            }

            Yii::$app->session->setFlash('success', "Announcement sent to {$successCount} users successfully.");
            return $this->redirect(['send-announcement']);
        }

        return $this->render('send-announcement');
    }

    /**
     * Centralized dashboard statistics.
     */
    protected function getDashboardStats(): array
    {
        $cache = new CacheHelper();
        $counts = $cache->cacheStats('dashboard', 900);

        $pendingApplications = (int) Application::find()
            ->where(['status' => [Application::STATUS_PENDING, Application::STATUS_UNDER_REVIEW]])
            ->count();
        $approvedApplications = (int) Application::find()
            ->where(['status' => [Application::STATUS_APPROVED, Application::STATUS_ORG_APPROVED, Application::STATUS_UNIVERSITY_APPROVED, Application::STATUS_COMPLETED]])
            ->count();
        $rejectedApplications = (int) Application::find()
            ->where(['status' => Application::STATUS_REJECTED])
            ->count();
        $activePositions = (int) Position::find()
            ->where(['or', ['status' => 'Active'], ['status' => 'active']])
            ->count();
        $pendingUsers = (int) User::find()->where(['status' => User::STATUS_PENDING])->count();

        $recentApplications = $cache->cacheStats('recent_applications', 600);
        $recentUsers = $cache->cacheStats('recent_users', 600);

        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthStart = strtotime($month . '-01');
            $monthEnd = strtotime($month . '-01 +1 month');
            $monthlyStats[$month] = [
                'applications' => (int) Application::find()
                    ->where(['>=', 'created_at', $monthStart])
                    ->andWhere(['<', 'created_at', $monthEnd])
                    ->count(),
                'users' => (int) User::find()
                    ->where(['>=', 'created_at', $monthStart])
                    ->andWhere(['<', 'created_at', $monthEnd])
                    ->count(),
            ];
        }

        $pipeline = [
            'pending' => (int) Application::find()->where(['status' => Application::STATUS_PENDING])->count(),
            'review' => (int) Application::find()->where(['status' => Application::STATUS_UNDER_REVIEW])->count(),
            'org_approved' => (int) Application::find()->where(['status' => Application::STATUS_ORG_APPROVED])->count(),
            'university_approved' => (int) Application::find()->where(['status' => Application::STATUS_UNIVERSITY_APPROVED])->count(),
            'completed' => (int) Application::find()->where(['status' => Application::STATUS_COMPLETED])->count(),
        ];

        return [
            'counts' => array_merge($counts, [
                'pending_applications' => $pendingApplications,
                'approved_applications' => $approvedApplications,
                'rejected_applications' => $rejectedApplications,
                'active_positions' => $activePositions,
                'pending_users' => $pendingUsers,
                'total_admins' => (int) Admin::find()->count(),
            ]),
            'recentApplications' => $recentApplications,
            'recentUsers' => $recentUsers,
            'monthlyStats' => $monthlyStats,
            'pipeline' => $pipeline,
        ];
    }
}
