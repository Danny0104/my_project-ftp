<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $user_id
 * @property string $email
 * @property string $name
 * @property string $role
 * @property string $status
 * @property string|null $permissions_json
 * @property int|null $last_active_at
 */
class OrgTeamMember extends ActiveRecord
{
    public const ROLE_HR = 'hr_manager';
    public const ROLE_RECRUITER = 'recruiter';
    public const ROLE_COORDINATOR = 'coordinator';
    public const ROLE_INTERVIEWER = 'interviewer';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INVITED = 'invited';
    public const STATUS_SUSPENDED = 'suspended';

    public static function tableName()
    {
        return '{{%org_team_member}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['organization_id', 'email', 'name'], 'required'],
            [['organization_id', 'user_id', 'last_active_at', 'created_at', 'updated_at'], 'integer'],
            [['permissions_json'], 'string'],
            [['email'], 'email'],
            [['email', 'name'], 'string', 'max' => 255],
            [['role'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 30],
            [['role'], 'in', 'range' => array_keys(self::roleOptions())],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INVITED, self::STATUS_SUSPENDED]],
        ];
    }

    public static function roleOptions(): array
    {
        return [
            self::ROLE_HR => 'HR Manager',
            self::ROLE_RECRUITER => 'Recruiter',
            self::ROLE_COORDINATOR => 'Internship Coordinator',
            self::ROLE_INTERVIEWER => 'Interviewer',
        ];
    }

    public function getPermissions(): array
    {
        if (!$this->permissions_json) {
            return self::defaultPermissionsForRole($this->role);
        }
        $decoded = json_decode($this->permissions_json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function defaultPermissionsForRole(string $role): array
    {
        $matrix = [
            self::ROLE_HR => ['analytics', 'students', 'interviews', 'programs', 'coordination', 'reviews', 'team', 'applications'],
            self::ROLE_RECRUITER => ['students', 'interviews', 'applications', 'messages'],
            self::ROLE_COORDINATOR => ['programs', 'coordination', 'reviews', 'students'],
            self::ROLE_INTERVIEWER => ['interviews', 'students'],
        ];
        return $matrix[$role] ?? ['students'];
    }
}
