<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property int $position_id
 * @property int $created_at
 */
class PositionBookmark extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%position_bookmark}}';
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
            [['user_id', 'position_id'], 'required'],
            [['user_id', 'position_id', 'created_at'], 'integer'],
            [['user_id', 'position_id'], 'unique', 'targetAttribute' => ['user_id', 'position_id']],
        ];
    }

    public function getPosition()
    {
        return $this->hasOne(Position::class, ['id' => 'position_id']);
    }

    public static function isSaved(int $userId, int $positionId): bool
    {
        return static::find()
            ->where(['user_id' => $userId, 'position_id' => $positionId])
            ->exists();
    }

    public static function toggle(int $userId, int $positionId): bool
    {
        $existing = static::findOne(['user_id' => $userId, 'position_id' => $positionId]);
        if ($existing) {
            $existing->delete();
            return false;
        }

        $bookmark = new self();
        $bookmark->user_id = $userId;
        $bookmark->position_id = $positionId;

        return $bookmark->save() ? true : false;
    }

    /**
     * @return int[]
     */
    public static function positionIdsForUser(int $userId): array
    {
        return static::find()
            ->select('position_id')
            ->where(['user_id' => $userId])
            ->column();
    }
}
