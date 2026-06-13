<?php

namespace frontend\controllers;

use frontend\models\ResendVerificationEmailForm;
use frontend\models\VerifyEmailForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\components\SecurityHelper;
use common\components\SessionSecurity;
use common\models\LoginForm;
use common\models\User;
use common\services\GoogleAuthService;
use yii\authclient\AuthAction;
use common\components\EmailRoleDetector;
use frontend\models\OAuthCompleteProfileForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\StudentSignupForm;
use frontend\models\OrganizationSignupForm;
use frontend\models\ContactForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => [
                    'logout',
                    'signup',
                    'auth',
                    'complete-profile',
                    'request-password-reset',
                    'reset-password',
                    'resend-verification-email',
                    'verify-email',
                ],
                'rules' => [
                    [
                        'actions' => ['auth'],
                        'allow' => true,
                    ],
                    [
                        'actions' => [
                            'signup',
                            'request-password-reset',
                            'reset-password',
                            'resend-verification-email',
                            'verify-email',
                        ],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'actions' => ['complete-profile'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'complete-profile' => ['GET', 'POST'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
            'auth' => [
                'class' => AuthAction::class,
                'successCallback' => [$this, 'onAuthSuccess'],
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Shows the OAuth redirect URI to register in Google Cloud Console (dev only).
     *
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionGoogleOauthUri()
    {
        if (!YII_DEBUG) {
            throw new NotFoundHttpException();
        }

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

        $client = Yii::$app->get('authClientCollection')->getClient('google');
        $host = Yii::$app->request->hostInfo;

        return implode("\n", [
            'Add this EXACT line to Google Cloud Console → Credentials → your OAuth client',
            '→ Authorized redirect URIs:',
            '',
            $client->returnUrl,
            '',
            'Also add under Authorized JavaScript origins:',
            $host,
            '',
            'If you use both localhost and 127.0.0.1, open this page in each browser host',
            'and register both redirect URIs shown.',
        ]);
    }

    /**
     * Public pages use main layout; auth/app pages use internal layout.
     */
    public function beforeAction($action)
    {
        $publicActions = ['index', 'about', 'contact'];
        $authActions = [
            'login',
            'signup',
            'complete-profile',
            'request-password-reset',
            'reset-password',
            'resend-verification-email',
            'verify-email',
        ];

        if ($action->id === 'contact'
            && !Yii::$app->user->isGuest
            && Yii::$app->user->identity
        ) {
            $role = (string) Yii::$app->user->identity->role;
            if ($role === 'student') {
                $this->layout = 'student';
                $this->view->params['ftpNavActive'] = 'help';
            } elseif ($role === 'organization') {
                $this->layout = 'organization';
                $this->view->params['orgNavActive'] = 'help';
            }
        } elseif (in_array($action->id, $publicActions, true)) {
            $this->layout = 'main';
        } elseif (in_array($action->id, $authActions, true)) {
            $this->layout = 'auth';
        } else {
            $this->layout = 'internal';
        }

        return parent::beforeAction($action);
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
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

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            if (Yii::$app->user->identity->role === 'student') {
                return $this->redirect(['/dashboard/student']);
            }
            return $this->redirect(['/dashboard']);
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Google OAuth success callback.
     *
     * @param \yii\authclient\ClientInterface $client
     * @return \yii\web\Response
     */
    public function onAuthSuccess($client)
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $service = new GoogleAuthService();
        $result = $service->authenticate($client);

        if (!$result['success']) {
            Yii::$app->session->setFlash('error', $result['message'] ?? 'Google sign-in failed.');
            return $this->redirect(['site/login']);
        }

        /** @var User $user */
        $user = $result['user'];
        $user->setLastLoginTime();
        $user->resetFailedLoginAttempts();

        SecurityHelper::logSecurityEvent('login_success', [
            'user_id' => $user->id,
            'username' => $user->username,
            'provider' => 'google',
            'ip' => Yii::$app->request->getUserIP(),
        ]);

        Yii::$app->user->login($user, 0);

        if (!empty($result['needsProfileCompletion'])) {
            Yii::$app->session->setFlash(
                'info',
                'Welcome! Please confirm your account type to finish setting up your profile.'
            );
            return $this->redirect(['site/complete-profile']);
        }

        return $this->redirect($this->resolvePostLoginUrl($user));
    }

    /**
     * Google OAuth role confirmation and profile completion.
     *
     * @return mixed
     */
    public function actionCompleteProfile()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        /** @var User $user */
        $user = Yii::$app->user->identity;

        if (!$user->needsOAuthProfileCompletion()) {
            return $this->redirect($this->resolvePostLoginUrl($user));
        }

        $model = new OAuthCompleteProfileForm();
        $model->loadFromUser($user);

        if ($model->load(Yii::$app->request->post()) && $model->complete($user)) {
            $user->refresh();
            Yii::$app->user->setIdentity($user);

            SecurityHelper::logSecurityEvent('oauth_profile_completed', [
                'user_id' => $user->id,
                'role' => $user->role,
                'ip' => Yii::$app->request->getUserIP(),
            ]);

            Yii::$app->session->setFlash('success', 'Your profile is ready. Welcome aboard!');
            return $this->redirect($this->resolvePostLoginUrl($user));
        }

        return $this->render('complete-profile', [
            'model' => $model,
            'detectionSummary' => EmailRoleDetector::detectionSummary($user->email),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function resolvePostLoginUrl(User $user): array
    {
        if ($user->role === 'student') {
            return ['/dashboard/student'];
        }

        return ['/dashboard'];
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
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

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        }

        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $role = Yii::$app->request->get('role');
        if (Yii::$app->request->isPost) {
            $role = Yii::$app->request->post('role', $role);
        }

        $studentModel = new StudentSignupForm();
        $orgModel = new OrganizationSignupForm();

        if ($role === 'student') {
            if ($studentModel->load(Yii::$app->request->post()) && $studentModel->signup()) {
                Yii::$app->session->setFlash('success', 'Thank you for registration. Please check your inbox for verification email.');
                return $this->goHome();
            }

            return $this->render('signup', [
                'step' => 'student',
                'studentModel' => $studentModel,
                'orgModel' => $orgModel,
            ]);
        }

        if ($role === 'organization') {
            if ($orgModel->load(Yii::$app->request->post()) && $orgModel->signup()) {
                Yii::$app->session->setFlash('success', 'Thank you for registration. Please check your inbox for verification email.');
                return $this->goHome();
            }

            return $this->render('signup', [
                'step' => 'organization',
                'studentModel' => $studentModel,
                'orgModel' => $orgModel,
            ]);
        }

        return $this->render('signup', [
            'step' => 'role',
            'studentModel' => $studentModel,
            'orgModel' => $orgModel,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                SecurityHelper::logSecurityEvent('password_reset_requested', [
                    'email' => $model->email,
                    'ip' => Yii::$app->request->getUserIP(),
                ]);
                Yii::$app->session->setFlash(
                    'success',
                    'If an account exists for that email, you will receive password reset instructions shortly.'
                );

                return $this->redirect(['site/login']);
            }

            SecurityHelper::logSecurityEvent('password_reset_request_failed', [
                'email' => $model->email,
                'ip' => Yii::$app->request->getUserIP(),
            ]);
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            SecurityHelper::logSecurityEvent('password_reset_invalid_token', [
                'token_prefix' => is_string($token) ? substr($token, 0, 12) : '',
                'ip' => Yii::$app->request->getUserIP(),
                'message' => $e->getMessage(),
            ]);
            Yii::$app->session->setFlash(
                'error',
                'This password reset link is invalid or has expired. Please request a new one.'
            );

            return $this->render('resetPasswordInvalid');
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            SecurityHelper::logSecurityEvent('password_reset_completed', [
                'user_id' => $model->getUser()->id ?? null,
                'ip' => Yii::$app->request->getUserIP(),
            ]);
            Yii::$app->session->setFlash('success', 'Your password has been saved. You can sign in now.');

            return $this->redirect(['site/login']);
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if (($user = $model->verifyEmail()) && Yii::$app->user->login($user)) {
            Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
            return $this->goHome();
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model
        ]);
    }

    public function actionTerms()
    {
        $this->layout = 'main';
        return $this->render('terms');
    }

    public function actionPrivacy()
    {
        $this->layout = 'main';
        return $this->render('privacy');
    }
}
