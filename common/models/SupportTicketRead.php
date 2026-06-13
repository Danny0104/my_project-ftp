<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $ticket_id
 * @property int $user_id
 * @property int|null $last_read_message_id
 * @property int|null $last_read_at
 *
 * @property SupportTicket $ticket
 * @property User $user
 */
class SupportTicketRead extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%support_ticket_read}}';
    }

    public static function primaryKey(): array
    {
        return ['ticket_id', 'user_id'];
    }

    public function rules(): array
    {
        return [
            [['ticket_id', 'user_id'], 'required'],
            [['ticket_id', 'user_id', 'last_read_message_id', 'last_read_at'], 'integer'],
        ];
    }

    public function getTicket(): ActiveQuery
    {
        return $this->hasOne(SupportTicket::class, ['id' => 'ticket_id']);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}

