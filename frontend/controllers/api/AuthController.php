<?php

namespace frontend\controllers\api;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use common\models\LoginForm;
use common\models\User;
use common\components\SecurityHelper;
use common\components\ErrorHandler;

/**
 * Authentication API Controller
 */
class AuthController extends Controller
{
    public $modelClass = 'common\models\User';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        // Remove authentication for login/signup actions
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['login', 'signup', 'refresh-token'],
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
                'login' => ['POST'],
                'signup' => ['POST'],
                'logout' => ['POST'],
                'refresh-token' => ['POST'],
                'profile' => ['GET', 'PUT'],
            ],
        ];
        
        return $behaviors;
    }

    /**
     * User login
     * @return array
     */
    public function actionLogin()
    {
        try {
            $ip = Yii::$app->request->getUserIP() ?: 'unknown';
            if (!SecurityHelper::checkRateLimit('api_login_' . $ip, 10, 300)) {
                return [
                    'success' => false,
                    'message' => 'Too many login attempts. Please try again later.',
                ];
            }

            $model = new LoginForm();
            $model->load(Yii::$app->request->post(), '');
            
            if ($model->login()) {
                $user = $model->getUser();
                $token = $this->generateAccessToken($user);
                
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user' => $this->serializeUser($user),
                        'access_token' => $token,
                        'token_type' => 'Bearer',
                        'expires_in' => 3600,
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'errors' => $model->getErrors()
                ];
            }
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred during login',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * User signup
     * @return array
     */
    public function actionSignup()
    {
        try {
            $ip = Yii::$app->request->getUserIP() ?: 'unknown';
            if (!SecurityHelper::checkRateLimit('api_signup_' . $ip, 5, 600)) {
                return [
                    'success' => false,
                    'message' => 'Too many signup attempts. Please try again later.',
                ];
            }

            $data = Yii::$app->request->post();
            
            // Validate required fields
            $requiredFields = ['username', 'email', 'password', 'role'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field '{$field}' is required",
                        'errors' => [$field => ["Field '{$field}' is required"]]
                    ];
                }
            }
            
            // Sanitize input
            $data['username'] = SecurityHelper::sanitizeInput($data['username'], 'alphanumeric');
            $data['email'] = SecurityHelper::sanitizeInput($data['email'], 'email');
            $data['role'] = SecurityHelper::sanitizeInput($data['role'], 'string');
            
            // Validate role
            if (!in_array($data['role'], ['student', 'organization'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid role. Must be student or organization',
                    'errors' => ['role' => ['Invalid role']]
                ];
            }
            
            // Check if user already exists
            if (User::find()->where(['username' => $data['username']])->exists()) {
                return [
                    'success' => false,
                    'message' => 'Username already exists',
                    'errors' => ['username' => ['Username already exists']]
                ];
            }
            
            if (User::find()->where(['email' => $data['email']])->exists()) {
                return [
                    'success' => false,
                    'message' => 'Email already exists',
                    'errors' => ['email' => ['Email already exists']]
                ];
            }
            
            // Validate password strength
            $passwordErrors = User::validatePasswordStrength($data['password']);
            if (!empty($passwordErrors)) {
                return [
                    'success' => false,
                    'message' => 'Password does not meet requirements',
                    'errors' => ['password' => $passwordErrors]
                ];
            }
            
            // Create user
            $user = new User();
            $user->username = $data['username'];
            $user->email = $data['email'];
            $user->role = $data['role'];
            $user->setPassword($data['password']);
            $user->generateAuthKey();
            $user->generateEmailVerificationToken();
            $user->status = User::STATUS_INACTIVE;
            
            if ($user->save()) {
                if ($user->role === 'organization') {
                    $orgName = trim((string) ($data['organization_name'] ?? $data['username'] ?? ''));
                    \common\models\Organization::findOrCreateForUserId((int) $user->id, [
                        'name' => $orgName !== '' ? $orgName : $user->username,
                    ], false);
                } elseif ($user->role === 'student') {
                    \common\models\Student::findOrCreateForUserId((int) $user->id);
                }

                if (Yii::$app->has('authManager')) {
                    $auth = Yii::$app->authManager;
                    $role = $auth->getRole($user->role);
                    if ($role) {
                        $auth->assign($role, (int) $user->id);
                    }
                }

                try {
                    Yii::$app->mailer->compose(
                        ['html' => 'emailVerify-html', 'text' => 'emailVerify-text'],
                        ['user' => $user]
                    )
                        ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
                        ->setTo($user->email)
                        ->setSubject('Account registration at ' . Yii::$app->name)
                        ->send();
                } catch (\Throwable $e) {
                    Yii::warning('Verification email failed for API signup: ' . $e->getMessage(), __METHOD__);
                }
                
                return [
                    'success' => true,
                    'message' => 'Account created. Please verify your email before signing in.',
                    'data' => [
                        'user' => $this->serializeUser($user),
                        'verification_required' => true,
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create user',
                    'errors' => $user->getErrors()
                ];
            }
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred during signup',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * User logout
     * @return array
     */
    public function actionLogout()
    {
        try {
            Yii::$app->user->logout();
            
            return [
                'success' => true,
                'message' => 'Logout successful'
            ];
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred during logout',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Get user profile
     * @return array
     */
    public function actionProfile()
    {
        try {
            $user = Yii::$app->user->identity;
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not authenticated'
                ];
            }
            
            return [
                'success' => true,
                'data' => $this->serializeUser($user)
            ];
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred while fetching profile',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Update user profile
     * @return array
     */
    public function actionUpdateProfile()
    {
        try {
            $user = Yii::$app->user->identity;
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not authenticated'
                ];
            }
            
            $data = Yii::$app->request->post();
            
            // Sanitize input
            if (isset($data['email'])) {
                $data['email'] = SecurityHelper::sanitizeInput($data['email'], 'email');
            }
            
            $user->load($data, '');
            
            if ($user->save()) {
                return [
                    'success' => true,
                    'message' => 'Profile updated successfully',
                    'data' => $this->serializeUser($user)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update profile',
                    'errors' => $user->getErrors()
                ];
            }
        } catch (\Exception $e) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleError($e, 'api', 'error');
            
            return [
                'success' => false,
                'message' => 'An error occurred while updating profile',
                'error' => YII_DEBUG ? $e->getMessage() : 'Internal server error'
            ];
        }
    }

    /**
     * Generate access token
     * @param User $user
     * @return string
     */
    protected function generateAccessToken($user)
    {
        $token = SecurityHelper::generateSecureToken(32);
        
        // Store token in cache with expiration
        $cache = Yii::$app->cache;
        $cache->set("access_token_{$token}", $user->id, 3600);
        
        return $token;
    }

    /**
     * Serialize user data for API response
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
            'created_at' => date('Y-m-d H:i:s', $user->created_at),
            'updated_at' => date('Y-m-d H:i:s', $user->updated_at),
        ];
    }
}
