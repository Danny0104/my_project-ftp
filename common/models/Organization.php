<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "organization".
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property string|null $location
 * @property string|null $website
 * @property string|null $logo
 * @property string $verification_status
 *
 * @property User $user
 */
class Organization extends \yii\db\ActiveRecord
{
    public const VERIFICATION_PENDING = 'pending';
    public const VERIFICATION_APPROVED = 'approved';
    public const VERIFICATION_REJECTED = 'rejected';

    public static function tableName()
    {
        return 'organization';
    }

    public function rules()
    {
        return [
            [['user_id', 'name'], 'required'],
            [['user_id'], 'integer'],
            [['description'], 'string'],
            [['name', 'location', 'website', 'contact_person', 'registration_number', 'industry', 'organization_type', 'country', 'region', 'city', 'phone'], 'string', 'max' => 255],
            [['address'], 'string', 'max' => 500],
            [['logo', 'registration_certificate'], 'string', 'max' => 500],
            [['verification_status'], 'string', 'max' => 20],
            [['verification_status'], 'default', 'value' => self::VERIFICATION_PENDING],
            [['verification_status'], 'in', 'range' => [
                self::VERIFICATION_PENDING,
                self::VERIFICATION_APPROVED,
                self::VERIFICATION_REJECTED,
            ]],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public static function verificationOptions(): array
    {
        return [
            self::VERIFICATION_PENDING => 'Pending verification',
            self::VERIFICATION_APPROVED => 'Verified',
            self::VERIFICATION_REJECTED => 'Rejected',
        ];
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_APPROVED;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'name' => 'Name',
            'description' => 'Description',
            'location' => 'Location',
            'website' => 'Website',
            'logo' => 'Logo',
        ];
    }

    public function getLogoUrl(string $size = 'md'): ?string
    {
        return (new \common\services\ProfileImageService())->organizationLogoUrl($this, $size);
    }

    public function hasLogo(): bool
    {
        return $this->getLogoUrl() !== null;
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getPositions()
    {
        return $this->hasMany(Position::class, ['organization_id' => 'id']);
    }

    /**
     * Returns the organization row for a user, creating a starter profile if missing.
     *
     * @param int $userId
     * @param array<string, mixed> $attributes Optional name, description, location, website
     */
    public static function findOrCreateForUserId(int $userId, array $attributes = [], bool $notifyOnCreate = true): ?self
    {
        $organization = static::findOne(['user_id' => $userId]);
        if ($organization !== null) {
            return $organization;
        }

        $user = User::findOne($userId);
        if ($user === null || $user->role !== 'organization') {
            return null;
        }

        $name = trim((string) ($attributes['name'] ?? $user->username ?? ''));
        if ($name === '') {
            $name = 'My Organization';
        }

        $organization = new static([
            'user_id' => $userId,
            'name' => $name,
            'description' => $attributes['description'] ?? null,
            'location' => $attributes['location'] ?? null,
            'website' => $attributes['website'] ?? null,
        ]);

        if (!$organization->save(false)) {
            Yii::error([
                'message' => 'Failed to auto-create organization profile',
                'user_id' => $userId,
                'errors' => $organization->getErrors(),
            ], 'organization.onboarding');

            return null;
        }

        Yii::warning([
            'message' => 'Auto-created organization profile for existing organization user',
            'user_id' => $userId,
            'organization_id' => $organization->id,
        ], 'organization.onboarding');

        if ($notifyOnCreate && !Yii::$app->session->get('org_profile_auto_created_notified')) {
            Yii::$app->session->setFlash(
                'info',
                'We set up a starter company profile for your account. You can update your details anytime under Company Profile.'
            );
            Yii::$app->session->set('org_profile_auto_created_notified', true);
        }

        return $organization;
    }

    /**
     * Whether the org should complete company details (soft onboarding hint).
     */
    public function needsProfileCompletion(): bool
    {
        $name = trim((string) $this->name);
        if ($name === '' || $name === 'My Organization') {
            return true;
        }

        $user = $this->user;
        if ($user && strcasecmp($name, (string) $user->username) === 0) {
            return true;
        }

        return trim((string) ($this->description ?? '')) === ''
            && trim((string) ($this->location ?? '')) === '';
    }
} 