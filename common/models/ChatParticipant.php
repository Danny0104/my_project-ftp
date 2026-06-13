<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $user_id
 * @property string $role
 * @property int|null $last_read_message_id
 * @property int|null $last_read_at
 * @property int $created_at
 * @property int $is_archived
 */
class ChatParticipant extends ActiveRecord
{
    public const ROLE_STUDENT = 'student';
    public const ROLE_ORGANIZATION = 'organization';

    public static function tableName()
    {
        return '{{%chat_participant}}';
    }

    public function rules()
    {
        return [
            [['conversation_id', 'user_id', 'created_at'], 'required'],
            [['conversation_id', 'user_id', 'last_read_message_id', 'last_read_at', 'created_at', 'is_archived'], 'integer'],
            [['is_archived'], 'default', 'value' => 0],
            [['role'], 'string', 'max' => 20],
        ];
    }

    public function getConversation()
    {
        return $this->hasOne(ChatConversation::class, ['id' => 'conversation_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
