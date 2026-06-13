<?php

namespace frontend\controllers\api;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use common\models\Position;
use common\services\PublicPositionService;
use common\components\SecurityHelper;
use common\components\ErrorHandler;
use common\components\CacheHelper;

/**
 * Positions API Controller
 */
class PositionController extends Controller
{
    public $modelClass = 'common\models\Position';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Add authentication
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['index', 'view'],
        ];
        
        // Add CORS support
        $behaviors['cors'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => true,
                'Access-Control-Max-Age' => 86400,
            ],
        ];
        
        // Add verb filter
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index' => ['GET'],
                'view' => ['GET'],
                'create' => ['POST'],
                'update' => ['PUT', 'PATCH'],
                'delete' => ['DELETE'],
                'apply' => ['POST'],
            ],
        ];
        
        return $behaviors;
    }

    /**
     * Get list of positions
     * @return array
     */
    public function actionIndex()
    {
        try {
            $cacheHelper = new CacheHelper();
            
            $publicService = new PublicPositionService();
            $query = $publicService->openListingQuery()
                ->orderBy(['p.created_at' => SORT_DESC]);
            
            // Apply filters
            $filters = Yii::$app->request->get();
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = SecurityHelper::sanitizeInput($filters['search'], 'string');
                $query->andWhere(['or',
                    ['like', 'p.title', $search],
                    ['like', 'p.description', $search],
                ]);
            }
            
            if (isset($filters['category']) && !empty($filters['category'])) {
                $category = SecurityHelper::sanitizeInput($filters['category'], 'string');
                $query->andWhere(['or',
                    ['like', 'p.category', $category],
                    ['like', 'p.field_of_study', $category],
                ]);
            }
            
            if (isset($filters['organization_id']) && !empty($filters['organization_id'])) {
                $orgId = SecurityHelper::sanitizeInput($filters['organization_id'], 'int');
                $query->andWhere(['organization_id' => $orgId]);
            }
            
            // Pagination
            $page = (int)Yii::$app->request->get('page', 1);
            $pageSize = (int)Yii::$app->request->get('page_size', 20);
            $pageSize = min($pageSize, 100); // Limit max page size
            
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => [
                    'page' => $page - 1,
                    'pageSize' => $pageSize,
                ],
            ]);
            
            $positions = $dataProvider->getModels();
            $totalCount = $dataProvider->getTotalCount();
            
            return [
                'success' => true,
                'data' => [
                    'positions' => $this->serializePositions($positions),
                    'pagination' => [
                        'page' => $page,
                        'page_size' => $pageSize,
                        'total_count' => $totalCount,
                        'total_pages' => ceil($totalCount / $pageSize),
                    ]
                ]
            ];
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred while fetching positions',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Get position details
     * @param int $id
     * @return array
     */
    public function actionView($id)
    {
        try {
            $id = SecurityHelper::sanitizeInput($id, 'int');
            
            $cacheHelper = new CacheHelper();
            $position = $cacheHelper->cacheModelWithRelations(
                Position::class,
                $id,
                ['organization'],
                1800 // Cache for 30 minutes
            );
            
            if (!$position) {
                return [
                    'success' => false,
                    'message' => 'Position not found'
                ];
            }

            $publicService = new PublicPositionService();
            if (!$publicService->isPubliclyViewable($position, true)) {
                return [
                    'success' => false,
                    'message' => 'Position not found'
                ];
            }
            
            return [
                'success' => true,
                'data' => $this->serializePosition($position)
            ];
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred while fetching position',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Create new position (for organizations)
     * @return array
     */
    public function actionCreate()
    {
        try {
            $user = Yii::$app->user->identity;
            
            if (!$user || $user->role !== 'organization') {
                return [
                    'success' => false,
                    'message' => 'Only organizations can create positions'
                ];
            }
            
            $data = Yii::$app->request->post();
            
            // Sanitize input
            $data['title'] = SecurityHelper::sanitizeInput($data['title'] ?? '', 'string');
            $data['description'] = SecurityHelper::sanitizeInput($data['description'] ?? '', 'string');
            $data['type'] = SecurityHelper::sanitizeInput($data['type'] ?? '', 'string');
            $data['requirements'] = SecurityHelper::sanitizeInput($data['requirements'] ?? '', 'string');
            $data['benefits'] = SecurityHelper::sanitizeInput($data['benefits'] ?? '', 'string');
            
            $position = new Position();
            $position->load($data, '');
            $position->organization_id = $user->organization->id ?? null;
            $position->status = 'active';
            
            if ($position->save()) {
                return [
                    'success' => true,
                    'message' => 'Position created successfully',
                    'data' => $this->serializePosition($position)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create position',
                    'errors' => $position->getErrors()
                ];
            }
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred while creating position',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Apply for position
     * @param int $id
     * @return array
     */
    public function actionApply($id)
    {
        try {
            $user = Yii::$app->user->identity;
            
            if (!$user || $user->role !== 'student') {
                return [
                    'success' => false,
                    'message' => 'Only students can apply for positions'
                ];
            }
            
            $id = SecurityHelper::sanitizeInput($id, 'int');
            $position = Position::findOne($id);
            
            if (!$position) {
                return [
                    'success' => false,
                    'message' => 'Position not found'
                ];
            }
            
            $publicService = new PublicPositionService();
            if (!$publicService->isAcceptingApplications($position)) {
                return [
                    'success' => false,
                    'message' => 'This internship is closed and no longer accepting applications'
                ];
            }

            $student = \common\models\Student::findOne(['user_id' => $user->id]);
            if (!$student) {
                return [
                    'success' => false,
                    'message' => 'Student profile not found. Please complete your profile first.'
                ];
            }

            $eligibility = Yii::$app->eligibility->evaluate($student, $position, 'api_apply_attempt');
            if (!$eligibility->eligible) {
                return [
                    'success' => false,
                    'message' => $eligibility->getPrimaryMessage(),
                    'reasons' => $eligibility->reasons,
                ];
            }

            $existingApplication = \common\models\Application::findForUserPosition((int) $user->id, $id);

            if ($existingApplication && !$existingApplication->isReapplyable()) {
                return [
                    'success' => false,
                    'message' => 'You have already applied for this position',
                    'data' => ['application_id' => $existingApplication->id],
                ];
            }

            $data = Yii::$app->request->post();

            // Sanitize input
            $data['cover_letter'] = SecurityHelper::sanitizeInput($data['cover_letter'] ?? '', 'string');
            $data['resume_url'] = SecurityHelper::sanitizeInput($data['resume_url'] ?? '', 'url');

            if ($existingApplication) {
                $application = $existingApplication;
                $application->scenario = \common\models\Application::SCENARIO_APPLY;
                $application->status = \common\models\Application::STATUS_PENDING;
                $application->feedback = null;
            } else {
                $application = new \common\models\Application();
                $application->scenario = \common\models\Application::SCENARIO_APPLY;
                $application->user_id = $user->id;
                $application->student_id = $student->id;
                $application->position_id = $id;
                $application->status = \common\models\Application::STATUS_PENDING;
            }
            $application->cover_letter = $data['cover_letter'];
            $application->resume_url = $data['resume_url'];
            
            if ($application->save()) {
                return [
                    'success' => true,
                    'message' => 'Application submitted successfully',
                    'data' => [
                        'application_id' => $application->id,
                        'status' => $application->status,
                        'applied_at' => date('Y-m-d H:i:s', $application->created_at)
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to submit application',
                    'errors' => $application->getErrors()
                ];
            }
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred while submitting application',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Serialize position data for API response
     * @param Position $position
     * @return array
     */
    protected function serializePosition($position)
    {
        return [
            'id' => $position->id,
            'title' => $position->title,
            'description' => $position->description,
            'type' => $position->type,
            'requirements' => $position->requirements,
            'benefits' => $position->benefits,
            'status' => $position->status,
            'organization' => $position->organization ? [
                'id' => $position->organization->id,
                'name' => $position->organization->name,
                'description' => $position->organization->description,
            ] : null,
            'created_at' => date('Y-m-d H:i:s', $position->created_at),
            'updated_at' => date('Y-m-d H:i:s', $position->updated_at),
        ];
    }

    /**
     * Serialize multiple positions
     * @param array $positions
     * @return array
     */
    protected function serializePositions($positions)
    {
        $result = [];
        foreach ($positions as $position) {
            $result[] = $this->serializePosition($position);
        }
        return $result;
    }
}
