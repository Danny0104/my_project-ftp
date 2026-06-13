<?php

namespace common\models;

use Yii;
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;
    public $isAdmin = false;

    private $_user;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            ['username', 'string', 'max' => 50],
            ['password', 'string', 'max' => 128],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user) {
                $this->addError($attribute, 'Incorrect username or password.');
                return;
            }

            // Check if account is locked
            if (!$user->canLogin()) {
                if ($user->isAccountLocked()) {
                    $this->addError($attribute, 'Account is temporarily locked due to multiple failed login attempts. Please try again later.');
                } else {
                    $this->addError($attribute, 'Account is inactive. Please contact administrator.');
                }
                return;
            }

            // Validate password
            if (!$user->validatePassword($this->password)) {
                $user->incrementFailedLoginAttempts();
                $this->addError($attribute, 'Incorrect username or password.');
                return;
            }

            // Reset failed login attempts on successful login
            $user->resetFailedLoginAttempts();
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            $user->setLastLoginTime();
            
            // Log successful login
            \common\components\SecurityHelper::logSecurityEvent('login_success', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => Yii::$app->request->getUserIP()
            ]);
            
            return Yii::$app->user->login($user, $this->rememberMe ? 3600 * 24 * 30 : 0);
        }
        
        // Log failed login attempt
        \common\components\SecurityHelper::logSecurityEvent('login_failed', [
            'username' => $this->username,
            'ip' => Yii::$app->request->getUserIP()
        ]);
        
        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            if ($this->isAdmin) {
                $this->_user = \common\models\Admin::findByUsername($this->username);
            } else {
                $this->_user = User::findByUsername($this->username);
            }
        }
        return $this->_user;
    }
}
