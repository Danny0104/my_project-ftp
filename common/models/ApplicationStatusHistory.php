<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $application_id
 * @property string $from_status
 * @property string $to_status
 * @property int|null $changed_by_user_id
 * @property int|null $organization_id
 * @property int $created_at
 */
class ApplicationStatusHistory extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%application_status_history}}';
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
            [['application_id', 'from_status', 'to_status'], 'required'],
            [['application_id', 'changed_by_user_id', 'organization_id', 'created_at'], 'integer'],
            [['from_status', 'to_status'], 'string', 'max' => 30],
        ];
    }

    public function getApplication()
    {
        return $this->hasOne(Application::class, ['id' => 'application_id']);
    }

    public static function record(
        int $applicationId,
        string $fromStatus,
        string $toStatus,
        ?int $changedByUserId = null,
        ?int $organizationId = null
    ): void {
        if ($fromStatus === $toStatus) {
            return;
        }

        $row = new self();
        $row->application_id = $applicationId;
        $row->from_status = $fromStatus;
        $row->to_status = $toStatus;
        $row->changed_by_user_id = $changedByUserId;
        $row->organization_id = $organizationId;
        $row->save(false);
    }
}
