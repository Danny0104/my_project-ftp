<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $code
 * @property int $created_by_user_id
 * @property string $created_by_role student|organization
 * @property string $subject
 * @property string $status open|in_progress|resolved|closed
 * @property string $priority low|normal|high|urgent
 * @property int|null $assigned_admin_id
 * @property int|null $last_message_id
 * @property int|null $last_message_at
 * @property int|null $closed_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $creator
 * @property SupportMessage[] $messages
 */
class SupportTicket extends ActiveRecord
{
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public static function tableName(): string
    {
        return '{{%support_ticket}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['code', 'created_by_user_id', 'created_by_role', 'subject'], 'required'],
            [['created_by_user_id', 'assigned_admin_id', 'last_message_id', 'last_message_at', 'closed_at'], 'integer'],
            [['code'], 'string', 'max' => 32],
            [['subject'], 'string', 'max' => 255],
            [['created_by_role'], 'in', 'range' => ['student', 'organization']],
            [['status'], 'in', 'range' => [
                self::STATUS_OPEN,
                self::STATUS_IN_PROGRESS,
                self::STATUS_RESOLVED,
                self::STATUS_CLOSED,
            ]],
            [['priority'], 'in', 'range' => [
                self::PRIORITY_LOW,
                self::PRIORITY_NORMAL,
                self::PRIORITY_HIGH,
                self::PRIORITY_URGENT,
            ]],
        ];
    }

    public function getCreator(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by_user_id']);
    }

    public function getMessages(): ActiveQuery
    {
        return $this->hasMany(SupportMessage::class, ['ticket_id' => 'id'])->orderBy(['id' => SORT_ASC]);
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public static function generateCode(): string
    {
        return 'SUP-' . strtoupper(dechex(time())) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    public static function findVisibleToUser(int $userId, string $role): ActiveQuery
    {
        $q = static::find();
        if ($role === 'admin') {
            return $q;
        }
        return $q->where(['created_by_user_id' => $userId]);
    }
}

