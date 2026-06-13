<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property int $created_at
 * @property int $updated_at
 */
class AcademicFaculty extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%academic_faculty}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['description'], 'string'],
            [['is_active'], 'boolean'],
            [['name'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 120],
            [['slug'], 'unique'],
            [['name'], 'unique'],
        ];
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }
        if ($this->name && !$this->slug) {
            $this->slug = Inflector::slug($this->name);
        }
        return true;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if (!$insert && isset($changedAttributes['name']) && $changedAttributes['name'] !== $this->name) {
            FieldOfStudy::updateAll(
                ['faculty' => $this->name],
                ['faculty_id' => $this->id]
            );
        }
    }

    public static function getDropdownOptions(): array
    {
        $options = [];
        foreach (static::find()->where(['is_active' => true])->orderBy(['name' => SORT_ASC])->all() as $faculty) {
            $options[$faculty->id] = $faculty->name;
        }
        return $options;
    }
}
