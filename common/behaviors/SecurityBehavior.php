<?php

namespace common\behaviors;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use common\components\SecurityHelper;

/**
 * Security Behavior for Controllers
 * Provides security features like rate limiting, input validation, and logging
 */
class SecurityBehavior extends Behavior
{
    /**
     * @var array Rate limiting configuration
     */
    public $rateLimit = [
        'enabled' => true,
        'maxAttempts' => 10,
        'timeWindow' => 300, // 5 minutes
    ];

    /**
     * @var array Actions to apply rate limiting
     */
    public $rateLimitActions = ['login', 'signup', 'forgot-password'];

    /**
     * @var bool Enable security logging
     */
    public $enableLogging = true;

    /**
     * @var array Actions to log
     */
    public $logActions = ['login', 'signup', 'logout', 'password-reset'];

    /**
     * @var bool Enable input sanitization
     */
    public $enableInputSanitization = true;

    /**
     * @var array Actions to sanitize input
     */
    public $sanitizeActions = ['create', 'update', 'signup'];

    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
            Controller::EVENT_AFTER_ACTION => 'afterAction',
        ];
    }

    /**
     * Before action security checks
     * @param \yii\base\ActionEvent $event
     * @throws BadRequestHttpException
     */
    public function beforeAction($event)
    {
        $action = $event->action;
        $actionId = $action->id;

        // Rate limiting check
        if ($this->rateLimit['enabled'] && in_array($actionId, $this->rateLimitActions)) {
            $this->checkRateLimit($actionId);
        }

        // Input sanitization
        if ($this->enableInputSanitization && in_array($actionId, $this->sanitizeActions)) {
            $this->sanitizeInput();
        }

        // CSRF protection for POST requests
        if (Yii::$app->request->isPost && !Yii::$app->request->isAjax) {
            $this->validateCsrfToken();
        }

        // Log security events
        if ($this->enableLogging && in_array($actionId, $this->logActions)) {
            $this->logSecurityEvent("action_started", [
                'action' => $actionId,
                'controller' => $action->controller->id,
                'method' => Yii::$app->request->method
            ]);
        }
    }

    /**
     * After action security logging
     * @param \yii\base\ActionEvent $event
     */
    public function afterAction($event)
    {
        $action = $event->action;
        $actionId = $action->id;

        // Log successful actions
        if ($this->enableLogging && in_array($actionId, $this->logActions)) {
            $this->logSecurityEvent("action_completed", [
                'action' => $actionId,
                'controller' => $action->controller->id,
                'status' => 'success'
            ]);
        }
    }

    /**
     * Check rate limiting
     * @param string $actionId
     * @throws BadRequestHttpException
     */
    protected function checkRateLimit($actionId)
    {
        $userIp = Yii::$app->request->getUserIP();
        $userId = Yii::$app->user->id ?? 'guest';
        $key = "{$actionId}_{$userId}_{$userIp}";

        if (!SecurityHelper::checkRateLimit(
            $key,
            $this->rateLimit['maxAttempts'],
            $this->rateLimit['timeWindow']
        )) {
            $this->logSecurityEvent("rate_limit_exceeded", [
                'action' => $actionId,
                'user_id' => $userId,
                'ip' => $userIp
            ]);

            throw new BadRequestHttpException('Too many requests. Please try again later.');
        }
    }

    /**
     * Sanitize input data
     */
    protected function sanitizeInput()
    {
        $request = Yii::$app->request;
        
        if ($request->isPost) {
            $postData = $request->post();
            $sanitizedData = $this->recursiveSanitize($postData);
            $_POST = $sanitizedData;
        }

        if ($request->isGet) {
            $getData = $request->get();
            $sanitizedData = $this->recursiveSanitize($getData);
            $_GET = $sanitizedData;
        }
    }

    /**
     * Recursively sanitize array data
     * @param array $data
     * @return array
     */
    protected function recursiveSanitize($data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveSanitize($value);
            } else {
                $data[$key] = SecurityHelper::sanitizeInput($value);
            }
        }
        return $data;
    }

    /**
     * Validate CSRF token
     * @throws BadRequestHttpException
     */
    protected function validateCsrfToken()
    {
        $token = Yii::$app->request->post('_csrf');
        if (!$token || !SecurityHelper::validateCsrfToken($token)) {
            $this->logSecurityEvent("csrf_token_invalid", [
                'ip' => Yii::$app->request->getUserIP(),
                'user_agent' => Yii::$app->request->getUserAgent()
            ]);

            throw new BadRequestHttpException('Invalid CSRF token');
        }
    }

    /**
     * Log security event
     * @param string $event
     * @param array $data
     */
    protected function logSecurityEvent($event, $data = [])
    {
        SecurityHelper::logSecurityEvent($event, $data);
    }
}
