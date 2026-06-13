<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string|null $description
 * @property int $updated_at
 */
class PlatformRegulation extends ActiveRecord
{
    private static ?array $cache = null;

    public static function tableName()
    {
        return '{{%platform_regulation}}';
    }

    public function rules()
    {
        return [
            [['key', 'value', 'updated_at'], 'required'],
            [['value'], 'string'],
            [['updated_at'], 'integer'],
            [['key'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 500],
            [['key'], 'unique'],
        ];
    }

    public static function getValue(string $key, $default = null)
    {
        if (static::$cache === null) {
            static::$cache = static::find()->indexBy('key')->all();
        }
        return isset(static::$cache[$key]) ? static::$cache[$key]->value : $default;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $val = static::getValue($key, $default ? '1' : '0');
        return in_array((string) $val, ['1', 'true', 'yes', 'on'], true);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) static::getValue($key, (string) $default);
    }

    public static function clearCache(): void
    {
        static::$cache = null;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        static::clearCache();
    }
}
