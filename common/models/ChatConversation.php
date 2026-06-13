<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int|null $application_id
 * @property int $organization_id
 * @property int $student_user_id
 * @property string|null $title
 * @property int|null $last_message_id
 * @property int|null $last_message_at
 * @property int $created_at
 * @property int $updated_at
 */
class ChatConversation extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%chat_conversation}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['organization_id', 'student_user_id'], 'required'],
            [['application_id', 'organization_id', 'student_user_id', 'last_message_id', 'last_message_at'], 'integer'],
            [['title'], 'string', 'max' => 255],
        ];
    }

    public function getParticipants()
    {
        return $this->hasMany(ChatParticipant::class, ['conversation_id' => 'id']);
    }

    public function getMessages()
    {
        return $this->hasMany(ChatMessage::class, ['conversation_id' => 'id'])->orderBy(['id' => SORT_ASC]);
    }

    public function getOrganization()
    {
        return $this->hasOne(Organization::class, ['id' => 'organization_id']);
    }

    public function getStudentUser()
    {
        return $this->hasOne(User::class, ['id' => 'student_user_id']);
    }

    public function getApplication()
    {
        return $this->hasOne(Application::class, ['id' => 'application_id']);
    }

    public function getLastMessage()
    {
        return $this->hasOne(ChatMessage::class, ['id' => 'last_message_id']);
    }
}
