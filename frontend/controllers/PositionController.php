<?php
namespace frontend\controllers;

use common\traits\RoleDashboardLayoutTrait;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use common\models\Position;
use common\models\Application;
use common\models\Organization;
use common\models\Student;
use common\services\EligibilityResult;
use common\services\PublicPositionService;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;

class PositionController extends Controller
{
    use RoleDashboardLayoutTrait;

    /** @var string Default layout for authenticated/app views */
    public $layout = 'internal';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['create', 'edit', 'delete', 'toggle-status', 'validate', 'toggle-bookmark', 'bookmark-ids'],
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['toggle-bookmark', 'bookmark-ids'],
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            $user = Yii::$app->user->identity;
                            return $user && $user->role === 'student';
                        },
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            $user = Yii::$app->user->identity;
                            return $user && $user->role === 'organization';
                        },
                    ],
                ],
                'denyCallback' => function () {
                    if (Yii::$app->user->isGuest) {
                        Yii::$app->user->loginRequired();
                        return;
                    }
                    throw new ForbiddenHttpException('You do not have permission for this action.');
                },
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'simpleDelete' => ['POST'],
                    'create' => ['GET', 'POST'],
                    'edit' => ['GET', 'POST'],
                    'toggle-status' => ['POST'],
                    'validate' => ['POST'],
                    'toggle-bookmark' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $identity = Yii::$app->user->identity;
        $isOrganization = !Yii::$app->user->isGuest && $identity && $identity->role === 'organization';
        $isStudent = !Yii::$app->user->isGuest && $identity && $identity->role === 'student';

        if ($isOrganization) {
            $this->layout = 'organization';
            $this->view->params['orgNavActive'] = 'opportunities';

            $organization = Organization::findOrCreateForUserId((int) Yii::$app->user->id);
            if (!$organization) {
                Yii::$app->session->setFlash('error', 'Unable to load organization profile. Please try again or contact support.');
                return $this->redirect(['profile/organization']);
            }

            $query = Position::find()
                ->where(['organization_id' => $organization->id])
                ->orderBy(['created_at' => SORT_DESC]);

            $q = trim((string) Yii::$app->request->get('q', ''));
            $status = trim((string) Yii::$app->request->get('status', ''));
            $view = Yii::$app->request->get('view', 'grid') === 'list' ? 'list' : 'grid';

            if ($q !== '') {
                $query->andWhere(['or',
                    ['like', 'title', $q],
                    ['like', 'description', $q],
                    ['like', 'field_of_study', $q],
                    ['like', 'skills_required', $q],
                ]);
            }
            if ($status !== '' && Position::isValidStatus($status)) {
                $query->andWhere(['status' => $status]);
            }

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => ['pageSize' => 12],
            ]);

            return $this->render('organization', [
                'dataProvider' => $dataProvider,
                'organization' => $organization,
                'viewMode' => $view,
                'searchQuery' => $q,
                'statusFilter' => $status,
            ]);
        }

        if ($isStudent) {
            $this->layout = 'student';
            $this->view->params['ftpNavActive'] = 'opportunities';

            $studentField = null;
            $student = Student::findOne(['user_id' => Yii::$app->user->id]);
            $studentField = $student->field_of_study ?? null;

            $publicService = new PublicPositionService();
            $query = $publicService->applyOpenListingFilters(
                Position::find()->alias('position')->with('organization'),
                'position'
            )->orderBy(['position.created_at' => SORT_DESC]);

            if ($studentField && $student) {
                $query = Yii::$app->eligibility->applyListingFilter($query, $student);
            } elseif ($studentField) {
                $query->andWhere(['or',
                    ['like', 'position.field_of_study', $studentField],
                    ['position.field_of_study' => null],
                    ['position.field_of_study' => ''],
                ]);
            }

            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => ['pageSize' => 10],
            ]);

            $applicationsByPosition = \common\models\Application::find()
                ->where(['user_id' => (int) Yii::$app->user->id])
                ->andWhere(['not in', 'status', [\common\models\Application::STATUS_WITHDRAWN]])
                ->indexBy('position_id')
                ->all();

            $appliedPositionIds = array_map('intval', array_keys($applicationsByPosition));
            $recoService = new \common\services\OpportunityRecommendationService();

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'student' => $student,
                'applicationsByPosition' => $applicationsByPosition,
                'forYou' => $student ? $recoService->forYou($student, $appliedPositionIds) : [],
                'trending' => $recoService->trending(5),
                'closingSoonItems' => $recoService->closingSoon(3),
                'categories' => $recoService->distinctCategories(),
            ]);
        }

        $this->layout = 'main';

        return $this->render('index', $this->buildPublicMarketplaceParams());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPublicMarketplaceParams(): array
    {
        $request = Yii::$app->request;
        $publicService = new PublicPositionService();
        $title = trim((string) $request->get('title', ''));
        $location = trim((string) $request->get('location', ''));
        $field = trim((string) $request->get('field', ''));
        $organizationId = (int) $request->get('organization_id', 0);
        $duration = trim((string) $request->get('duration', ''));
        $sort = trim((string) $request->get('sort', 'newest'));
        if (!in_array($sort, ['newest', 'deadline', 'applicants', 'organization'], true)) {
            $sort = 'newest';
        }

        $query = $publicService->openListingQuery();

        if ($title !== '') {
            $query->andWhere(['or',
                ['like', 'p.title', $title],
                ['like', 'p.description', $title],
            ]);
        }
        if ($location !== '') {
            $query->andWhere(['like', 'p.location', $location]);
        }
        if ($field !== '') {
            $query->andWhere(['like', 'p.field_of_study', $field]);
        }
        if ($organizationId > 0) {
            $query->andWhere(['p.organization_id' => $organizationId]);
        }
        if ($duration !== '') {
            $query->andWhere(['like', 'p.duration', $duration]);
        }

        $applicantSub = (new Query())
            ->select(['position_id', 'cnt' => new Expression('COUNT(*)')])
            ->from(Application::tableName())
            ->where(['not in', 'status', [Application::STATUS_WITHDRAWN]])
            ->groupBy('position_id');

        switch ($sort) {
            case 'deadline':
                $query->orderBy([
                    new Expression('CASE WHEN p.application_deadline IS NULL THEN 1 ELSE 0 END'),
                    'p.application_deadline' => SORT_ASC,
                    'p.created_at' => SORT_DESC,
                ]);
                break;
            case 'organization':
                $query->joinWith(['organization o'])->orderBy(['o.name' => SORT_ASC, 'p.created_at' => SORT_DESC]);
                break;
            case 'applicants':
                $query->leftJoin(['ac' => $applicantSub], 'ac.position_id = p.id')
                    ->orderBy(['ac.cnt' => SORT_DESC, 'p.created_at' => SORT_DESC]);
                break;
            default:
                $query->orderBy(['p.created_at' => SORT_DESC]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 9,
                'pageParam' => 'page',
                'params' => array_filter([
                    'title' => $title,
                    'location' => $location,
                    'field' => $field,
                    'organization_id' => $organizationId > 0 ? $organizationId : null,
                    'duration' => $duration,
                    'sort' => $sort,
                ], static fn($v) => $v !== null && $v !== ''),
            ],
        ]);

        $organizations = $publicService->organizationsWithOpenPositions();

        $positionIds = array_map(static fn(Position $p) => (int) $p->id, $dataProvider->getModels());
        $applicantCounts = $publicService->applicantCountsForPositions($positionIds);

        return [
            'dataProvider' => $dataProvider,
            'searchParams' => [
                'title' => $title,
                'location' => $location,
                'field' => $field,
                'organization_id' => $organizationId,
                'duration' => $duration,
            ],
            'sort' => $sort,
            'organizations' => $organizations,
            'applicantCounts' => $applicantCounts,
            'totalActive' => $publicService->countOpenPositions(),
            'totalOrgs' => $publicService->countPartnerOrganizations(),
            'publicService' => $publicService,
        ];
    }

    public function actionView($id)
    {
        $model = Position::find()->where(['id' => $id])->with(['organization'])->one();
        if (!$model) {
            throw new NotFoundHttpException('Position not found.');
        }

        $publicService = new PublicPositionService();
        if (!$publicService->isPubliclyViewable($model, Yii::$app->user->isGuest)) {
            throw new NotFoundHttpException('This internship is not available.');
        }

        $identity = Yii::$app->user->identity;
        if (!Yii::$app->user->isGuest && $identity) {
            if ($identity->role === 'student') {
                $this->layout = 'student';
                $this->view->params['ftpNavActive'] = 'opportunities';
            } elseif ($identity->role === 'organization') {
                $this->layout = 'organization';
                $this->view->params['orgNavActive'] = 'opportunities';
            } else {
                $this->layout = 'main';
            }
        } else {
            $this->layout = 'main';
        }

        $application = null;
        $student = null;
        /** @var EligibilityResult|null $eligibility */
        $eligibility = null;
        $profileCompletion = null;
        $skillsOverlap = ['matched' => 0, 'total' => 0, 'percent' => 0];

        if (!Yii::$app->user->isGuest) {
            $application = Application::find()
                ->where(['user_id' => Yii::$app->user->id, 'position_id' => $model->id])
                ->andWhere(['not in', 'status', [Application::STATUS_WITHDRAWN]])
                ->one();

            $identity = Yii::$app->user->identity;
            if ($identity && $identity->role === 'student') {
                $student = Student::findOne(['user_id' => Yii::$app->user->id]);
                if ($student) {
                    $eligibility = Yii::$app->eligibility->evaluate($student, $model, 'browse');
                    $profileCompletion = Yii::$app->eligibility->profileCompletionPercent($student);
                    $skillsOverlap = $this->computeSkillsOverlap($student, $model);
                }
            }
        }

        $applicationCount = (int) Application::find()
            ->where(['position_id' => $model->id])
            ->andWhere(['not in', 'status', [Application::STATUS_WITHDRAWN]])
            ->count();

        $allowedFieldNames = Yii::$app->eligibility->getAllowedFieldNames($model);

        $similarPositions = $this->findSimilarPositions($model);

        $orgActiveCount = 0;
        $orgHireRate = null;
        if ($model->organization_id) {
            $orgActiveCount = (int) (new PublicPositionService())->openListingQuery()
                ->andWhere(['p.organization_id' => $model->organization_id])
                ->count();

            $orgAppTotal = (int) Application::find()
                ->alias('a')
                ->innerJoin(['p' => Position::tableName()], 'p.id = a.position_id')
                ->where(['p.organization_id' => $model->organization_id])
                ->count();
            $orgAppPositive = (int) Application::find()
                ->alias('a')
                ->innerJoin(['p' => Position::tableName()], 'p.id = a.position_id')
                ->where(['p.organization_id' => $model->organization_id])
                ->andWhere(['in', 'a.status', [
                    Application::STATUS_APPROVED,
                    Application::STATUS_UNIVERSITY_APPROVED,
                    Application::STATUS_ORG_APPROVED,
                    Application::STATUS_COMPLETED,
                ]])
                ->count();
            if ($orgAppTotal > 0) {
                $orgHireRate = (int) round(100 * $orgAppPositive / $orgAppTotal);
            }
        }

        return $this->render('view', [
            'model' => $model,
            'application' => $application,
            'student' => $student,
            'eligibility' => $eligibility,
            'profileCompletion' => $profileCompletion,
            'skillsOverlap' => $skillsOverlap,
            'applicationCount' => $applicationCount,
            'allowedFieldNames' => $allowedFieldNames,
            'similarPositions' => $similarPositions,
            'orgActiveCount' => $orgActiveCount,
            'orgHireRate' => $orgHireRate,
            'deadlineMeta' => $publicService->deadlineMeta($model),
            'acceptingApplications' => $publicService->isAcceptingApplications($model),
        ]);
    }

    /**
     * @return Position[]
     */
    private function findSimilarPositions(Position $model): array
    {
        $publicService = new PublicPositionService();
        $out = [];
        $usedIds = [$model->id];

        $sameOrg = $publicService->openListingQuery()
            ->with(['organization'])
            ->andWhere(['p.organization_id' => $model->organization_id])
            ->andWhere(['!=', 'p.id', $model->id])
            ->orderBy(['p.created_at' => SORT_DESC])
            ->limit(6)
            ->all();
        foreach ($sameOrg as $p) {
            $out[] = $p;
            $usedIds[] = $p->id;
        }

        if (count($out) < 6 && !empty($model->category)) {
            $more = $publicService->openListingQuery()
                ->with(['organization'])
                ->andWhere(['p.category' => $model->category])
                ->andWhere(['not in', 'p.id', $usedIds])
                ->orderBy(['p.created_at' => SORT_DESC])
                ->limit(6 - count($out))
                ->all();
            foreach ($more as $p) {
                $out[] = $p;
                $usedIds[] = $p->id;
            }
        }

        if (count($out) < 6) {
            $more = $publicService->openListingQuery()
                ->with(['organization'])
                ->andWhere(['not in', 'p.id', $usedIds])
                ->orderBy(['p.created_at' => SORT_DESC])
                ->limit(6 - count($out))
                ->all();
            foreach ($more as $p) {
                $out[] = $p;
            }
        }

        return array_slice($out, 0, 6);
    }

    /**
     * @return array{matched: int, total: int, percent: int}
     */
    private function computeSkillsOverlap(?Student $student, Position $position): array
    {
        if (!$student || empty($student->skills) || empty($position->skills_required)) {
            return ['matched' => 0, 'total' => 0, 'percent' => 0];
        }
        $studentSkills = array_filter(array_map('trim', explode(',', strtolower($student->skills))));
        $required = array_filter(array_map('trim', explode(',', strtolower($position->skills_required))));
        if (empty($required)) {
            return ['matched' => 0, 'total' => 0, 'percent' => 0];
        }
        $matched = count(array_intersect($studentSkills, $required));

        return [
            'matched' => $matched,
            'total' => count($required),
            'percent' => (int) round(100 * $matched / count($required)),
        ];
    }

    public function actionCreate()
    {
        $model = new Position();
        
        if (Yii::$app->request->isAjax && !Yii::$app->request->isPost) {
            return $this->renderAjax('_form', [
                'model' => $model,
                'isCreate' => true,
            ]);
        }
        
        if ($model->load(Yii::$app->request->post())) {
            // Get the organization ID for the logged-in user
            $user = Yii::$app->user->identity;
            
            if (!$user) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            
            if ($user->role !== 'organization') {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Only organizations can create positions'];
            }
            
            $organization = Organization::findOrCreateForUserId((int) $user->id);
            if ($organization && !$organization->isVerified()) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [
                    'success' => false,
                    'message' => 'Your organization is pending admin verification. You cannot post internships until approved.',
                ];
            }

            if (!$organization) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => false, 'message' => 'Unable to load organization profile.'];
            }
            
            $model->organization_id = $organization->id;
            $model->created_at = time();
            $model->status = 'Active';
            
            if ($model->save()) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => true, 'message' => 'Position created successfully'];
            }
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => false,
                'message' => reset($model->getFirstErrors()) ?: 'Failed to create position.',
                'errors' => $model->errors,
            ];
        }
        
        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionEdit($id)
    {
        $model = Position::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Position not found.');
        }
        
        // Check if user owns this position
        $user = Yii::$app->user->identity;
        $organization = Organization::findOrCreateForUserId((int) $user->id);

        if (!$organization || $model->organization_id !== $organization->id) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['success' => false, 'message' => 'You can only edit your own positions'];
        }
        
        if (Yii::$app->request->isAjax && !Yii::$app->request->isPost) {
            return $this->renderAjax('_form', [
                'model' => $model,
                'isCreate' => false,
            ]);
        }
        
        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ['success' => true, 'message' => 'Position updated successfully'];
            }
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'success' => false,
                'message' => reset($model->getFirstErrors()) ?: 'Failed to update position.',
                'errors' => $model->errors,
            ];
        }
        
        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            if (Yii::$app->user->isGuest) {
                return ['success' => false, 'message' => 'User not authenticated'];
            }
            $user = Yii::$app->user->identity;
            if ($user->role !== 'organization') {
                return ['success' => false, 'message' => 'Only organizations can delete positions'];
            }
            $model = Position::findOne($id);
            if (!$model) {
                return ['success' => false, 'message' => 'Position not found'];
            }
            $organization = Organization::findOrCreateForUserId((int) $user->id);
            if ($organization && !$organization->isVerified()) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return [
                    'success' => false,
                    'message' => 'Your organization is pending admin verification. You cannot post internships until approved.',
                ];
            }
            if (!$organization) {
                return ['success' => false, 'message' => 'Unable to load organization profile'];
            }
            if ($model->organization_id !== $organization->id) {
                return ['success' => false, 'message' => 'You can only delete your own positions'];
            }
            // No need to check for applications; DB will cascade delete
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->delete()) {
                    $transaction->commit();
                    return ['success' => true, 'message' => 'Position and all related applications deleted successfully'];
                } else {
                    $transaction->rollBack();
                    return ['success' => false, 'message' => 'Failed to delete position'];
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function actionToggleStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $user = Yii::$app->user->identity;
        if (!$user || $user->role !== 'organization') {
            return ['success' => false, 'message' => 'Only organizations can update internship status.'];
        }

        $id = (int) Yii::$app->request->post('id');
        $targetStatus = trim((string) Yii::$app->request->post('status', ''));

        $model = Position::findOne($id);
        if (!$model) {
            return ['success' => false, 'message' => 'Internship not found.'];
        }

        $organization = Organization::findOrCreateForUserId((int) $user->id);
        if (!$organization || (int) $model->organization_id !== (int) $organization->id) {
            return ['success' => false, 'message' => 'You can only update your own internships.'];
        }

        $currentStatus = Position::normalizeStatus((string) $model->status);
        if ($targetStatus === '') {
            $targetStatus = Position::getStatusToggleMeta($currentStatus)['next'];
        } else {
            $targetStatus = Position::normalizeStatus($targetStatus);
        }

        if (!Position::isValidStatus($targetStatus)) {
            return ['success' => false, 'message' => 'Invalid status selected.'];
        }

        $allowedTransitions = [
            Position::STATUS_DRAFT => [Position::STATUS_ACTIVE],
            Position::STATUS_ACTIVE => [Position::STATUS_PAUSED, Position::STATUS_CLOSED],
            Position::STATUS_PAUSED => [Position::STATUS_ACTIVE],
            Position::STATUS_CLOSED => [Position::STATUS_ACTIVE],
        ];

        if (!in_array($targetStatus, $allowedTransitions[$currentStatus] ?? [], true)) {
            return [
                'success' => false,
                'message' => 'Cannot change status from ' . $currentStatus . ' to ' . $targetStatus . '.',
            ];
        }

        $model->status = $targetStatus;
        if (!$model->save(false, ['status'])) {
            return ['success' => false, 'message' => 'Failed to update internship status.'];
        }

        \common\models\OrgTeamActivity::log($organization->id, 'position.status_updated', (int) $user->id, [
            'position_id' => $model->id,
            'from' => $currentStatus,
            'to' => $targetStatus,
        ]);
        \common\models\PlatformActivityLog::log('position.status_updated', 'position', (int) $model->id, [
            'organization_id' => $organization->id,
            'from' => $currentStatus,
            'to' => $targetStatus,
        ], (int) $user->id);

        return [
            'success' => true,
            'message' => 'Internship status updated to ' . $targetStatus . '.',
            'status' => $targetStatus,
            'toggle' => Position::getStatusToggleMeta($targetStatus),
        ];
    }

    public function actionValidate()
    {
        $model = new Position();
        if ($model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return \yii\widgets\ActiveForm::validate($model);
        }
    }

    public function actionToggleBookmark()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $user = Yii::$app->user->identity;
        if (!$user || $user->role !== 'student') {
            return ['success' => false, 'message' => 'Only students can bookmark internships.'];
        }

        $positionId = (int) Yii::$app->request->post('position_id');
        $position = $positionId > 0 ? Position::find()->where(['id' => $positionId])->with('organization')->one() : null;
        if (!$position) {
            return ['success' => false, 'message' => 'Internship not found.'];
        }

        $publicPositions = new \common\services\PublicPositionService();
        $isOpen = $publicPositions->isPubliclyListable($position);
        $saved = \common\models\PositionBookmark::isSaved((int) $user->id, $positionId);

        if (!$saved && !$isOpen) {
            return [
                'success' => false,
                'message' => 'This internship is closed and cannot be saved.',
            ];
        }

        $saved = \common\models\PositionBookmark::toggle((int) $user->id, $positionId);

        return [
            'success' => true,
            'saved' => $saved,
            'message' => $saved ? 'Internship saved.' : 'Internship removed from saved list.',
        ];
    }

    /**
     * Open bookmark IDs for syncing client saved list (expired roles excluded).
     */
    public function actionBookmarkIds()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $user = Yii::$app->user->identity;
        if (!$user || $user->role !== 'student') {
            return ['success' => false, 'ids' => []];
        }

        $ids = \common\models\PositionBookmark::positionIdsForUser((int) $user->id);
        if ($ids === []) {
            return ['success' => true, 'ids' => []];
        }

        $publicPositions = new \common\services\PublicPositionService();
        $openIds = [];
        foreach (Position::find()->where(['id' => $ids])->with('organization')->all() as $position) {
            if ($publicPositions->isPubliclyListable($position)) {
                $openIds[] = (int) $position->id;
            }
        }

        return ['success' => true, 'ids' => $openIds];
    }

} 