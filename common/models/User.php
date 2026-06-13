<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $verification_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $phone
 * @property int $oauth_profile_completed
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;
    const STATUS_PENDING = 5;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_INACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED, self::STATUS_PENDING]],
            ['role', 'required'],
            ['role', 'in', 'range' => ['student', 'organization', 'admin']],
            ['oauth_profile_completed', 'boolean'],
            ['oauth_profile_completed', 'default', 'value' => 1],
            ['username', 'required'],
            ['username', 'string', 'min' => 3, 'max' => 50],
            ['username', 'match', 'pattern' => '/^[a-zA-Z0-9_]+$/', 'message' => 'Username can only contain letters, numbers, and underscores'],
            ['username', 'unique', 'targetClass' => self::class, 'message' => 'This username has already been taken'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => self::class, 'message' => 'This email address has already been taken'],
            ['password_hash', 'required'],
            ['password_hash', 'string', 'min' => 6],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        if ($token === null || $token === '') {
            return null;
        }

        $userId = Yii::$app->cache->get('access_token_' . $token);
        if (!$userId) {
            return null;
        }

        return static::findIdentity((int) $userId);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by email (any status).
     *
     * @param string $email
     * @return static|null
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => strtolower(trim($email))]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds user by verification email token
     *
     * @param string $token verify email token
     * @return static|null
     */
    public static function findByVerificationToken($token) {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        $hash = trim((string) $this->password_hash);
        if ($hash === '' || !preg_match('/^\$2[axy]\$(\d{2})\$/', $hash, $matches)) {
            return false;
        }

        $cost = (int) $matches[1];
        if ($cost < 4 || $cost > 15) {
            Yii::warning("Rejecting password hash with unsafe cost {$cost} for user #{$this->id}", __METHOD__);
            return false;
        }

        try {
            return Yii::$app->security->validatePassword($password, $hash);
        } catch (\Throwable $e) {
            Yii::warning('Password validation failed: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Generates new token for email verification
     */
    public function generateEmailVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'role' => 'Role',
            'username' => 'Username',
            'email' => 'Email',
            'password_hash' => 'Password',
        ];
    }

    /**
     * Check if password meets security requirements
     * @param string $password
     * @return array
     */
    public static function validatePasswordStrength($password)
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }

    /**
     * Check if user account is locked
     * @return bool
     */
    public function isAccountLocked()
    {
        $cache = Yii::$app->cache;
        $lockKey = "account_locked_{$this->id}";
        return $cache->get($lockKey) !== false;
    }

    /**
     * Lock user account
     * @param int $duration Duration in seconds
     */
    public function lockAccount($duration = 900) // 15 minutes
    {
        $cache = Yii::$app->cache;
        $lockKey = "account_locked_{$this->id}";
        $cache->set($lockKey, true, $duration);
    }

    /**
     * Unlock user account
     */
    public function unlockAccount()
    {
        $cache = Yii::$app->cache;
        $lockKey = "account_locked_{$this->id}";
        $cache->delete($lockKey);
    }

    /**
     * Check failed login attempts
     * @return int
     */
    public function getFailedLoginAttempts()
    {
        $cache = Yii::$app->cache;
        $attemptKey = "failed_login_{$this->id}";
        return $cache->get($attemptKey) ?: 0;
    }

    /**
     * Increment failed login attempts
     */
    public function incrementFailedLoginAttempts()
    {
        $cache = Yii::$app->cache;
        $attemptKey = "failed_login_{$this->id}";
        $attempts = $this->getFailedLoginAttempts() + 1;
        $cache->set($attemptKey, $attempts, 900); // 15 minutes
        
        // Lock account after 5 failed attempts
        if ($attempts >= 5) {
            $this->lockAccount();
        }
    }

    /**
     * Reset failed login attempts
     */
    public function resetFailedLoginAttempts()
    {
        $cache = Yii::$app->cache;
        $attemptKey = "failed_login_{$this->id}";
        $cache->delete($attemptKey);
    }

    /**
     * Check if user can login
     * @return bool
     */
    public function canLogin()
    {
        return $this->status === self::STATUS_ACTIVE && !$this->isAccountLocked();
    }

    /**
     * Get user's last login time
     * @return string|null
     */
    public function getLastLoginTime()
    {
        $cache = Yii::$app->cache;
        $lastLoginKey = "last_login_{$this->id}";
        return $cache->get($lastLoginKey);
    }

    /**
     * Set user's last login time
     */
    public function setLastLoginTime()
    {
        $cache = Yii::$app->cache;
        $lastLoginKey = "last_login_{$this->id}";
        $cache->set($lastLoginKey, date('Y-m-d H:i:s'), 86400 * 30); // 30 days
    }

    public function getStudent()
    {
        return $this->hasOne(Student::class, ['user_id' => 'id']);
    }

    public function getOrganization()
    {
        return $this->hasOne(Organization::class, ['user_id' => 'id']);
    }

    public function needsOAuthProfileCompletion(): bool
    {
        return !(bool) ($this->oauth_profile_completed ?? true);
    }

    public function isOAuthProfileCompleted(): bool
    {
        return !$this->needsOAuthProfileCompletion();
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if ($this->role === 'organization' && $this->isOAuthProfileCompleted()) {
            Organization::findOrCreateForUserId((int) $this->id, [], false);
        }
    }
} 