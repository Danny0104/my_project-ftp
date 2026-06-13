<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $message_id
 * @property int $user_id
 * @property int|null $delivered_at
 * @property int|null $read_at
 */
class ChatMessageStatus extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%chat_message_status}}';
    }

    public function rules()
    {
        return [
            [['message_id', 'user_id'], 'required'],
            [['message_id', 'user_id', 'delivered_at', 'read_at'], 'integer'],
        ];
    }
}
