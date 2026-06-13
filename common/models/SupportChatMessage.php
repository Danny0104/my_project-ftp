<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property int $sender_id
 * @property int $receiver_id
 * @property string $sender_role
 * @property string $body
 * @property int $is_read
 * @property int $created_at
 *
 * @property User $user
 */
class SupportChatMessage extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%support_chat_message}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'sender_id', 'sender_role', 'body', 'created_at'], 'required'],
            [['user_id', 'sender_id', 'receiver_id', 'is_read', 'created_at'], 'integer'],
            [['body'], 'string'],
            [['sender_role'], 'string', 'max' => 20],
            [['is_read'], 'default', 'value' => 0],
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
