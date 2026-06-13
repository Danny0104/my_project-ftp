<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $to_email
 * @property string $subject
 * @property string $body_html
 * @property string|null $body_text
 * @property string $status
 * @property int $attempts
 * @property string|null $related_type
 * @property int|null $related_id
 * @property int $created_at
 * @property int|null $sent_at
 */
class EmailQueue extends ActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    public static function tableName()
    {
        return '{{%email_queue}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules()
    {
        return [
            [['to_email', 'subject', 'body_html'], 'required'],
            [['body_html', 'body_text'], 'string'],
            [['attempts', 'related_id', 'created_at', 'sent_at'], 'integer'],
            [['to_email', 'subject'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 20],
            [['related_type'], 'string', 'max' => 50],
        ];
    }
}
