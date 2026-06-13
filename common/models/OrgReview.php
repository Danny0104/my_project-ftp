<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $student_id
 * @property int|null $application_id
 * @property int|null $program_id
 * @property int $rating
 * @property string $category
 * @property string $title
 * @property string|null $feedback
 * @property string|null $supervisor_comment
 * @property string $status
 * @property int|null $reviewer_user_id
 */
class OrgReview extends ActiveRecord
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_MODERATED = 'moderated';

    public static function tableName()
    {
        return '{{%org_review}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['organization_id', 'title'], 'required'],
            [['organization_id', 'student_id', 'application_id', 'program_id', 'rating', 'reviewer_user_id', 'created_at', 'updated_at'], 'integer'],
            [['feedback', 'supervisor_comment'], 'string'],
            [['title'], 'string', 'max' => 255],
            [['category', 'status'], 'string', 'max' => 50],
            [['rating'], 'integer', 'min' => 1, 'max' => 5],
            [['status'], 'in', 'range' => [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_MODERATED]],
        ];
    }

    public function getStudent()
    {
        return $this->hasOne(Student::class, ['id' => 'student_id']);
    }

    public static function categoryOptions(): array
    {
        return [
            'internship' => 'Internship',
            'student' => 'Student performance',
            'supervisor' => 'Supervisor',
            'satisfaction' => 'Satisfaction survey',
        ];
    }
}
