<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Error Log Model
 * @property int $id
 * @property string $category
 * @property string $severity
 * @property string $message
 * @property string $data
 * @property int $user_id
 * @property string $ip_address
 * @property string $user_agent
 * @property int $created_at
 */
class ErrorLog extends ActiveRecord
{
    public static function tableName()
    {
        return 'error_log';
    }

    public function rules()
    {
        return [
            [['category', 'severity', 'message'], 'required'],
            [['data'], 'string'],
            [['user_id', 'created_at'], 'integer'],
            [['category', 'severity'], 'string', 'max' => 50],
            [['message'], 'string', 'max' => 500],
            [['ip_address'], 'string', 'max' => 45],
            [['user_agent'], 'string', 'max' => 500],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category' => 'Category',
            'severity' => 'Severity',
            'message' => 'Message',
            'data' => 'Data',
            'user_id' => 'User ID',
            'ip_address' => 'IP Address',
            'user_agent' => 'User Agent',
            'created_at' => 'Created At',
        ];
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
            ],
        ];
    }

    /**
     * Get severity badge class
     * @return string
     */
    public function getSeverityBadgeClass()
    {
        switch ($this->severity) {
            case 'emergency':
            case 'alert':
            case 'critical':
                return 'danger';
            case 'error':
                return 'danger';
            case 'warning':
                return 'warning';
            case 'notice':
            case 'info':
                return 'info';
            case 'debug':
                return 'secondary';
            default:
                return 'secondary';
        }
    }

    /**
     * Get formatted data
     * @return array
     */
    public function getFormattedData()
    {
        return json_decode($this->data, true) ?: [];
    }
}
