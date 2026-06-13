<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $position_id
 * @property int $field_of_study_id
 */
class PositionAllowedField extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%position_allowed_field}}';
    }

    public static function primaryKey()
    {
        return ['position_id', 'field_of_study_id'];
    }

    public function rules()
    {
        return [
            [['position_id', 'field_of_study_id'], 'required'],
            [['position_id', 'field_of_study_id'], 'integer'],
        ];
    }

    public function getFieldOfStudy()
    {
        return $this->hasOne(FieldOfStudy::class, ['id' => 'field_of_study_id']);
    }

    public function getPosition()
    {
        return $this->hasOne(Position::class, ['id' => 'position_id']);
    }

    /**
     * Replace allowed fields for a position.
     * @param int[] $fieldIds
     */
    public static function sync(int $positionId, array $fieldIds): void
    {
        static::deleteAll(['position_id' => $positionId]);
        $fieldIds = array_unique(array_filter(array_map('intval', $fieldIds)));
        foreach ($fieldIds as $fieldId) {
            $row = new static([
                'position_id' => $positionId,
                'field_of_study_id' => $fieldId,
            ]);
            $row->save(false);
        }
    }
}
