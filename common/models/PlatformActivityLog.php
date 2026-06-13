<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property string|null $meta_json
 * @property int $created_at
 */
class PlatformActivityLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%platform_activity_log}}';
    }

    public function rules()
    {
        return [
            [['action', 'created_at'], 'required'],
            [['user_id', 'entity_id', 'created_at'], 'integer'],
            [['meta_json'], 'string'],
            [['action'], 'string', 'max' => 120],
            [['entity_type'], 'string', 'max' => 80],
        ];
    }

    public static function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        array $meta = [],
        ?int $userId = null
    ): void {
        $row = new self();
        $row->user_id = $userId ?? (Yii::$app->user->id ?? null);
        $row->action = $action;
        $row->entity_type = $entityType;
        $row->entity_id = $entityId;
        $row->meta_json = $meta ? json_encode($meta) : null;
        $row->created_at = time();
        $row->save(false);
    }
}
