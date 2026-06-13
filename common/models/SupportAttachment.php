<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $message_id
 * @property string $path
 * @property string $name
 * @property string $mime
 * @property int $size
 * @property int $created_at
 *
 * @property SupportTicket $ticket
 * @property SupportMessage $message
 */
class SupportAttachment extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%support_attachment}}';
    }

    public function rules(): array
    {
        return [
            [['ticket_id', 'message_id', 'path', 'name', 'mime', 'created_at'], 'required'],
            [['ticket_id', 'message_id', 'size', 'created_at'], 'integer'],
            [['path'], 'string', 'max' => 500],
            [['name'], 'string', 'max' => 255],
            [['mime'], 'string', 'max' => 128],
        ];
    }

    public function getTicket(): ActiveQuery
    {
        return $this->hasOne(SupportTicket::class, ['id' => 'ticket_id']);
    }

    public function getMessage(): ActiveQuery
    {
        return $this->hasOne(SupportMessage::class, ['id' => 'message_id']);
    }
}

