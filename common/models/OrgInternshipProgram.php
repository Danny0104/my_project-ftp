<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $title
 * @property string|null $description
 * @property string|null $category
 * @property string $status
 * @property string|null $start_date
 * @property string|null $end_date
 * @property int $capacity
 * @property int $completion_percent
 * @property int $created_at
 * @property int $updated_at
 */
class OrgInternshipProgram extends ActiveRecord
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED = 'archived';

    public static function tableName()
    {
        return '{{%org_internship_program}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['organization_id', 'title'], 'required'],
            [['organization_id', 'capacity', 'completion_percent', 'created_at', 'updated_at'], 'integer'],
            [['description'], 'string'],
            [['start_date', 'end_date'], 'date', 'format' => 'php:Y-m-d'],
            [['title', 'category'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 30],
            [['status'], 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_ACTIVE, self::STATUS_COMPLETED, self::STATUS_ARCHIVED]],
            [['completion_percent'], 'integer', 'min' => 0, 'max' => 100],
        ];
    }

    public function getOrganization()
    {
        return $this->hasOne(Organization::class, ['id' => 'organization_id']);
    }

    public function getEnrollments()
    {
        return $this->hasMany(OrgProgramStudent::class, ['program_id' => 'id']);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }
}
