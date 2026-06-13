<?php

namespace frontend\controllers\api;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use common\models\Application;
use common\components\SecurityHelper;
use common\components\ErrorHandler;
use common\components\CacheHelper;

/**
 * Applications API Controller
 */
class ApplicationController extends Controller
{
    public $modelClass = 'common\models\Application';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Add authentication
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
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
                'update' => ['PUT', 'PATCH'],
                'withdraw' => ['POST'],
                'approve' => ['POST'],
                'reject' => ['POST'],
            ],
        ];
        
        return $behaviors;
    }

    /**
     * Get user's applications
     * @return array
     */
    public function actionIndex()
    {
        try {
            $user = Yii::$app->user->identity;
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not authenticated'
                ];
            }
            
            $query = Application::find()
                ->where(['user_id' => $user->id])
                ->with(['position.organization'])
                ->orderBy(['created_at' => SORT_DESC]);
            
            // Apply filters
            $filters = Yii::$app->request->get();
            if (isset($filters['status']) && !empty($filters['status'])) {
                $status = SecurityHelper::sanitizeInput($filters['status'], 'string');
                $query->andWhere(['status' => $status]);
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
            
            $applications = $dataProvider->getModels();
            $totalCount = $dataProvider->getTotalCount();
            
            return [
                'success' => true,
                'data' => [
                    'applications' => $this->serializeApplications($applications),
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
                'message' => 'An error occurred while fetching applications',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Get application details
     * @param int $id
     * @return array
     */
    public function actionView($id)
    {
        try {
            $user = Yii::$app->user->identity;
            $id = SecurityHelper::sanitizeInput($id, 'int');
            
            $application = Application::find()
                ->where(['id' => $id, 'user_id' => $user->id])
                ->with(['position.organization', 'student.user'])
                ->one();
            
            if (!$application) {
                return [
                    'success' => false,
                    'message' => 'Application not found'
                ];
            }
            
            return [
                'success' => true,
                'data' => $this->serializeApplication($application)
            ];
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred while fetching application',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Withdraw application
     * @param int $id
     * @return array
     */
    public function actionWithdraw($id)
    {
        try {
            $user = Yii::$app->user->identity;
            $id = SecurityHelper::sanitizeInput($id, 'int');
            
            $application = Application::find()
                ->where(['id' => $id, 'user_id' => $user->id])
                ->one();
            
            if (!$application) {
                return [
                    'success' => false,
                    'message' => 'Application not found'
                ];
            }
            
            if (!$application->canWithdraw()) {
                return [
                    'success' => false,
                    'message' => 'Application cannot be withdrawn in current status'
                ];
            }
            
            $application->status = Application::STATUS_WITHDRAWN;
            
            if ($application->save()) {
                return [
                    'success' => true,
                    'message' => 'Application withdrawn successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to withdraw application',
                    'errors' => $application->getErrors()
                ];
            }
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred while withdrawing application',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Approve application (for organizations)
     * @param int $id
     * @return array
     */
    public function actionApprove($id)
    {
        try {
            $user = Yii::$app->user->identity;
            
            if (!$user || $user->role !== 'organization') {
                return [
                    'success' => false,
                    'message' => 'Only organizations can approve applications'
                ];
            }
            
            $id = SecurityHelper::sanitizeInput($id, 'int');
            $application = Application::find()
                ->joinWith('position')
                ->where(['application.id' => $id, 'position.organization_id' => $user->organization->id ?? null])
                ->one();
            
            if (!$application) {
                return [
                    'success' => false,
                    'message' => 'Application not found'
                ];
            }
            
            if (!$application->canUpdateByAdmin()) {
                return [
                    'success' => false,
                    'message' => 'Application cannot be updated in current status'
                ];
            }
            
            $data = Yii::$app->request->post();
            $application->status = Application::STATUS_APPROVED;
            $application->feedback = SecurityHelper::sanitizeInput($data['feedback'] ?? '', 'string');
            
            if ($application->save()) {
                return [
                    'success' => true,
                    'message' => 'Application approved successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to approve application',
                    'errors' => $application->getErrors()
                ];
            }
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred while approving application',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Reject application (for organizations)
     * @param int $id
     * @return array
     */
    public function actionReject($id)
    {
        try {
            $user = Yii::$app->user->identity;
            
            if (!$user || $user->role !== 'organization') {
                return [
                    'success' => false,
                    'message' => 'Only organizations can reject applications'
                ];
            }
            
            $id = SecurityHelper::sanitizeInput($id, 'int');
            $application = Application::find()
                ->joinWith('position')
                ->where(['application.id' => $id, 'position.organization_id' => $user->organization->id ?? null])
                ->one();
            
            if (!$application) {
                return [
                    'success' => false,
                    'message' => 'Application not found'
                ];
            }
            
            if (!$application->canUpdateByAdmin()) {
                return [
                    'success' => false,
                    'message' => 'Application cannot be updated in current status'
                ];
            }
            
            $data = Yii::$app->request->post();
            $application->status = Application::STATUS_REJECTED;
            $application->feedback = SecurityHelper::sanitizeInput($data['feedback'] ?? '', 'string');
            
            if ($application->save()) {
                return [
                    'success' => true,
                    'message' => 'Application rejected'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to reject application',
                    'errors' => $application->getErrors()
                ];
            }
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred while rejecting application',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Serialize application data for API response
     * @param Application $application
     * @return array
     */
    protected function serializeApplication($application)
    {
        return [
            'id' => $application->id,
            'status' => $application->status,
            'status_label' => ucfirst(str_replace('_', ' ', $application->status)),
            'cover_letter' => $application->cover_letter,
            'resume_url' => $application->resume_url,
            'feedback' => $application->feedback,
            'position' => $application->position ? [
                'id' => $application->position->id,
                'title' => $application->position->title,
                'organization' => $application->position->organization ? [
                    'id' => $application->position->organization->id,
                    'name' => $application->position->organization->name,
                ] : null,
            ] : null,
            'student' => $application->student ? [
                'id' => $application->student->id,
                'first_name' => $application->student->first_name,
                'last_name' => $application->student->last_name,
                'user' => $application->student->user ? [
                    'id' => $application->student->user->id,
                    'username' => $application->student->user->username,
                    'email' => $application->student->user->email,
                ] : null,
            ] : null,
            'created_at' => date('Y-m-d H:i:s', $application->created_at),
            'updated_at' => date('Y-m-d H:i:s', $application->updated_at),
        ];
    }

    /**
     * Serialize multiple applications
     * @param array $applications
     * @return array
     */
    protected function serializeApplications($applications)
    {
        $result = [];
        foreach ($applications as $application) {
            $result[] = $this->serializeApplication($application);
        }
        return $result;
    }
}
