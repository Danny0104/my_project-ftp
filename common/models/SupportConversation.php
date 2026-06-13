<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $user_role
 * @property string $category
 * @property string $subject
 * @property int|null $last_message_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 * @property SupportMessage[] $messages
 */
class SupportConversation extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%support_conversation}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['user_id', 'user_role', 'category', 'subject'], 'required'],
            [['user_id', 'last_message_at', 'created_at', 'updated_at'], 'integer'],
            [['user_role'], 'string', 'max' => 20],
            [['category'], 'string', 'max' => 50],
            [['subject'], 'string', 'max' => 255],
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getMessages(): ActiveQuery
    {
        return $this->hasMany(SupportMessage::class, ['conversation_id' => 'id'])->orderBy(['id' => SORT_ASC]);
    }

    public static function categoryOptions(): array
    {
        return [
            'application' => 'Application Problem',
            'cv_upload' => 'CV Upload Problem',
            'interview' => 'Interview Issue',
            'messaging' => 'Messaging Issue',
            'notification' => 'Notification Issue',
            'account' => 'Account Issue',
            'technical' => 'Technical Problem',
            'other' => 'Other',
        ];
    }

    public static function categoryOptionsForRole(string $role): array
    {
        if ($role === 'organization') {
            return [
                'internship' => 'Internship Management Issue',
                'ats' => 'ATS / Applications Issue',
                'interview' => 'Interview Issue',
                'students' => 'Candidate / CV Issue',
                'messaging' => 'Messaging Issue',
                'analytics' => 'Analytics / Reports Issue',
                'account' => 'Organization Account Issue',
                'technical' => 'Technical Problem',
                'other' => 'Other',
            ];
        }

        return static::categoryOptions();
    }
}
