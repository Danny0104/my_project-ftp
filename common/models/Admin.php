<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "admin".
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property string $auth_key
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 * @property string|null $preferences
 * @property string $admin_role
 */
class Admin extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;
    const STATUS_REJECTED = 5;

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_MODERATOR = 'moderator';
    public const ROLE_VIEWER = 'viewer';

    public $password; // For form input

    public static function tableName()
    {
        return 'admin';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['username', 'email', 'auth_key'], 'required'],
            [['preferences'], 'string'],
            [['status', 'created_at', 'updated_at'], 'integer'],
            [['username', 'email', 'password_hash', 'auth_key'], 'string', 'max' => 255],
            [['admin_role'], 'string', 'max' => 30],
            [['admin_role'], 'default', 'value' => self::ROLE_SUPER_ADMIN],
            [['admin_role'], 'in', 'range' => [self::ROLE_SUPER_ADMIN, self::ROLE_MODERATOR, self::ROLE_VIEWER]],
            [['username'], 'unique'],
            [['email'], 'unique'],
            [['email'], 'email'],
            [['password'], 'safe'], // allow password to be set optionally
            ['status', 'integer'], // ensure status is always integer
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_REJECTED]],
        ];
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->status = (int)$this->status;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'email' => 'Email',
            'password_hash' => 'Password Hash',
            'auth_key' => 'Auth Key',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'password' => 'Password',
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (!empty($this->password)) {
                $this->setPassword($this->password);
            }
            if ($this->isNewRecord && empty($this->auth_key)) {
                $this->generateAuthKey();
            }
            return true;
        }
        return false;
    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function validatePassword($password)
    {
        $hash = trim((string) $this->password_hash);
        if ($hash === '' || !preg_match('/^\$2[axy]\$(\d{2})\$/', $hash, $matches)) {
            return false;
        }

        $cost = (int) $matches[1];
        if ($cost < 4 || $cost > 15) {
            return false;
        }

        try {
            return Yii::$app->security->validatePassword($password, $hash);
        } catch (\Throwable $e) {
            Yii::warning('Admin password validation failed: ' . $e->getMessage(), __METHOD__);
            return false;
        }
    }

    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_key' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Check if admin account is locked
     * @return bool
     */
    public function isAccountLocked()
    {
        $cache = Yii::$app->cache;
        $lockKey = "account_locked_{$this->id}";
        return $cache->get($lockKey) !== false;
    }

    /**
     * Lock admin account
     * @param int $duration Duration in seconds
     */
    public function lockAccount($duration = 900) // 15 minutes
    {
        $cache = Yii::$app->cache;
        $lockKey = "account_locked_{$this->id}";
        $cache->set($lockKey, true, $duration);
    }

    /**
     * Unlock admin account
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
     * Check if admin can login
     * @return bool
     */
    public function canLogin()
    {
        return $this->status === self::STATUS_ACTIVE && !$this->isAccountLocked();
    }

    /**
     * Get admin's last login time
     * @return string|null
     */
    public function getLastLoginTime()
    {
        $cache = Yii::$app->cache;
        $lastLoginKey = "last_login_{$this->id}";
        return $cache->get($lastLoginKey);
    }

    /**
     * Set admin's last login time
     */
    public function setLastLoginTime()
    {
        $cache = Yii::$app->cache;
        $lastLoginKey = "last_login_{$this->id}";
        $cache->set($lastLoginKey, date('Y-m-d H:i:s'), 86400 * 30); // 30 days
    }

    public static function roleOptions(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_MODERATOR => 'Moderator',
            self::ROLE_VIEWER => 'Viewer',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->admin_role ?? self::ROLE_SUPER_ADMIN, $roles, true);
    }

    public function canWrite(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN, self::ROLE_MODERATOR);
    }

    public function canManageAdmins(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }
} 