<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $sender_user_id
 * @property string $body
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property string|null $attachment_mime
 * @property int $created_at
 * @property int $updated_at
 */
class ChatMessage extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%chat_message}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['conversation_id', 'sender_user_id', 'body'], 'required'],
            [['conversation_id', 'sender_user_id'], 'integer'],
            [['body'], 'string'],
            [['attachment_path'], 'string', 'max' => 500],
            [['attachment_name'], 'string', 'max' => 255],
            [['attachment_mime'], 'string', 'max' => 128],
        ];
    }

    public function getSender()
    {
        return $this->hasOne(User::class, ['id' => 'sender_user_id']);
    }

    public function getStatuses()
    {
        return $this->hasMany(ChatMessageStatus::class, ['message_id' => 'id']);
    }

    public function getConversation()
    {
        return $this->hasOne(ChatConversation::class, ['id' => 'conversation_id']);
    }
}
