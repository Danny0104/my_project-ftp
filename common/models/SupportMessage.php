<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $sender_id
 * @property int $receiver_id
 * @property string $sender_role student|organization|admin
 * @property string $body
 * @property int $is_read
 * @property int $created_at
 *
 * @property SupportConversation $conversation
 */
class SupportMessage extends ActiveRecord
{
    public const ROLE_STUDENT = 'student';
    public const ROLE_ORGANIZATION = 'organization';
    public const ROLE_ADMIN = 'admin';

    public static function tableName(): string
    {
        return '{{%support_message}}';
    }

    public function rules(): array
    {
        return [
            [['conversation_id', 'sender_id', 'sender_role', 'body', 'created_at'], 'required'],
            [['conversation_id', 'sender_id', 'receiver_id', 'is_read', 'created_at'], 'integer'],
            [['body'], 'string'],
            [['sender_role'], 'string', 'max' => 20],
            [['is_read'], 'default', 'value' => 0],
        ];
    }

    public function getConversation(): ActiveQuery
    {
        return $this->hasOne(SupportConversation::class, ['id' => 'conversation_id']);
    }
}
