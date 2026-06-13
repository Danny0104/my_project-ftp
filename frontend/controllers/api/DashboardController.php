<?php

namespace frontend\controllers\api;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use common\components\SecurityHelper;
use common\components\ErrorHandler;
use common\components\CacheHelper;

/**
 * Dashboard API Controller
 */
class DashboardController extends Controller
{
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
                'stats' => ['GET'],
            ],
        ];
        
        return $behaviors;
    }

    /**
     * Get dashboard data
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
            
            $cacheHelper = new CacheHelper();
            
            if ($user->role === 'student') {
                $data = $this->getStudentDashboard($user, $cacheHelper);
            } elseif ($user->role === 'organization') {
                $data = $this->getOrganizationDashboard($user, $cacheHelper);
            } else {
                $data = $this->getAdminDashboard($user, $cacheHelper);
            }
            
            return [
                'success' => true,
                'data' => $data
            ];
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred while fetching dashboard data',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Get dashboard statistics
     * @return array
     */
    public function actionStats()
    {
        try {
            $user = Yii::$app->user->identity;
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not authenticated'
                ];
            }
            
            $cacheHelper = new CacheHelper();
            
            if ($user->role === 'student') {
                $stats = $this->getStudentStats($user, $cacheHelper);
            } elseif ($user->role === 'organization') {
                $stats = $this->getOrganizationStats($user, $cacheHelper);
            } else {
                $stats = $this->getAdminStats($user, $cacheHelper);
            }
            
            return [
                'success' => true,
                'data' => $stats
            ];
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred while fetching statistics',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Get student dashboard data
     * @param User $user
     * @param CacheHelper $cacheHelper
     * @return array
     */
    protected function getStudentDashboard($user, $cacheHelper)
    {
        $applications = $cacheHelper->cacheQuery("student_applications_{$user->id}", function() use ($user) {
            return \common\models\Application::find()
                ->where(['user_id' => $user->id])
                ->with(['position.organization'])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(10)
                ->all();
        }, 900); // Cache for 15 minutes
        
        $notifications = $cacheHelper->cacheQuery("student_notifications_{$user->id}", function() use ($user) {
            return \common\models\Notification::find()
                ->where(['user_id' => $user->id])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(10)
                ->all();
        }, 300); // Cache for 5 minutes
        
        return [
            'user' => $this->serializeUser($user),
            'applications' => $this->serializeApplications($applications),
            'notifications' => $this->serializeNotifications($notifications),
            'stats' => $this->getStudentStats($user, $cacheHelper),
        ];
    }

    /**
     * Get organization dashboard data
     * @param User $user
     * @param CacheHelper $cacheHelper
     * @return array
     */
    protected function getOrganizationDashboard($user, $cacheHelper)
    {
        $organization = $user->organization;
        
        $positions = $cacheHelper->cacheQuery("org_positions_{$organization->id}", function() use ($organization) {
            return \common\models\Position::find()
                ->where(['organization_id' => $organization->id])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(10)
                ->all();
        }, 1800); // Cache for 30 minutes
        
        $applications = $cacheHelper->cacheQuery("org_applications_{$organization->id}", function() use ($organization) {
            return \common\models\Application::find()
                ->joinWith('position')
                ->where(['position.organization_id' => $organization->id])
                ->with(['student.user', 'position'])
                ->orderBy(['application.created_at' => SORT_DESC])
                ->limit(10)
                ->all();
        }, 900); // Cache for 15 minutes
        
        return [
            'user' => $this->serializeUser($user),
            'organization' => $this->serializeOrganization($organization),
            'positions' => $this->serializePositions($positions),
            'applications' => $this->serializeApplications($applications),
            'stats' => $this->getOrganizationStats($user, $cacheHelper),
        ];
    }

    /**
     * Get admin dashboard data
     * @param User $user
     * @param CacheHelper $cacheHelper
     * @return array
     */
    protected function getAdminDashboard($user, $cacheHelper)
    {
        $stats = $cacheHelper->cacheStats('dashboard', 1800);
        $recentApplications = $cacheHelper->cacheStats('recent_applications', 900);
        $recentUsers = $cacheHelper->cacheStats('recent_users', 900);
        
        return [
            'user' => $this->serializeUser($user),
            'stats' => $stats,
            'recent_applications' => $this->serializeApplications($recentApplications),
            'recent_users' => $this->serializeUsers($recentUsers),
        ];
    }

    /**
     * Get student statistics
     * @param User $user
     * @param CacheHelper $cacheHelper
     * @return array
     */
    protected function getStudentStats($user, $cacheHelper)
    {
        return $cacheHelper->cacheQuery("student_stats_{$user->id}", function() use ($user) {
            $applications = \common\models\Application::find()->where(['user_id' => $user->id])->all();
            
            $stats = [
                'total_applications' => count($applications),
                'pending' => 0,
                'under_review' => 0,
                'approved' => 0,
                'rejected' => 0,
                'withdrawn' => 0,
            ];
            
            foreach ($applications as $app) {
                switch ($app->status) {
                    case \common\models\Application::STATUS_PENDING:
                        $stats['pending']++;
                        break;
                    case \common\models\Application::STATUS_UNDER_REVIEW:
                        $stats['under_review']++;
                        break;
                    case \common\models\Application::STATUS_APPROVED:
                        $stats['approved']++;
                        break;
                    case \common\models\Application::STATUS_REJECTED:
                        $stats['rejected']++;
                        break;
                    case \common\models\Application::STATUS_WITHDRAWN:
                        $stats['withdrawn']++;
                        break;
                }
            }
            
            return $stats;
        }, 1800); // Cache for 30 minutes
    }

    /**
     * Get organization statistics
     * @param User $user
     * @param CacheHelper $cacheHelper
     * @return array
     */
    protected function getOrganizationStats($user, $cacheHelper)
    {
        $organization = $user->organization;
        
        return $cacheHelper->cacheQuery("org_stats_{$organization->id}", function() use ($organization) {
            $positions = \common\models\Position::find()->where(['organization_id' => $organization->id])->all();
            $applications = \common\models\Application::find()
                ->joinWith('position')
                ->where(['position.organization_id' => $organization->id])
                ->all();
            
            return [
                'total_positions' => count($positions),
                'active_positions' => count(array_filter($positions, function($p) { return $p->status === 'active'; })),
                'total_applications' => count($applications),
                'pending_applications' => count(array_filter($applications, function($a) { return $a->status === 'pending'; })),
                'approved_applications' => count(array_filter($applications, function($a) { return $a->status === 'approved'; })),
            ];
        }, 1800); // Cache for 30 minutes
    }

    /**
     * Get admin statistics
     * @param User $user
     * @param CacheHelper $cacheHelper
     * @return array
     */
    protected function getAdminStats($user, $cacheHelper)
    {
        return $cacheHelper->cacheStats('dashboard', 1800);
    }

    /**
     * Serialize user data
     * @param User $user
     * @return array
     */
    protected function serializeUser($user)
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'last_login' => $user->getLastLoginTime(),
        ];
    }

    /**
     * Serialize organization data
     * @param Organization $organization
     * @return array
     */
    protected function serializeOrganization($organization)
    {
        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'description' => $organization->description,
            'website' => $organization->website,
            'email' => $organization->email,
            'phone' => $organization->phone,
        ];
    }

    /**
     * Serialize applications
     * @param array $applications
     * @return array
     */
    protected function serializeApplications($applications)
    {
        $result = [];
        foreach ($applications as $app) {
            $result[] = [
                'id' => $app->id,
                'status' => $app->status,
                'position_title' => $app->position->title ?? 'N/A',
                'organization_name' => $app->position->organization->name ?? 'N/A',
                'created_at' => date('Y-m-d H:i:s', $app->created_at),
            ];
        }
        return $result;
    }

    /**
     * Serialize positions
     * @param array $positions
     * @return array
     */
    protected function serializePositions($positions)
    {
        $result = [];
        foreach ($positions as $position) {
            $result[] = [
                'id' => $position->id,
                'title' => $position->title,
                'type' => $position->type,
                'status' => $position->status,
                'created_at' => date('Y-m-d H:i:s', $position->created_at),
            ];
        }
        return $result;
    }

    /**
     * Serialize notifications
     * @param array $notifications
     * @return array
     */
    protected function serializeNotifications($notifications)
    {
        $result = [];
        foreach ($notifications as $notification) {
            $result[] = [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'is_read' => $notification->is_read,
                'created_at' => date('Y-m-d H:i:s', $notification->created_at),
            ];
        }
        return $result;
    }

    /**
     * Serialize users
     * @param array $users
     * @return array
     */
    protected function serializeUsers($users)
    {
        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'status' => $user['status'],
                'created_at' => date('Y-m-d H:i:s', $user['created_at']),
            ];
        }
        return $result;
    }
}
