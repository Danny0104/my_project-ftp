<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $admin_id
 * @property int $last_seen_at
 */
class SupportAdminPresence extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%support_admin_presence}}';
    }

    public static function primaryKey(): array
    {
        return ['admin_id'];
    }

    public function rules(): array
    {
        return [
            [['admin_id', 'last_seen_at'], 'required'],
            [['admin_id', 'last_seen_at'], 'integer'],
        ];
    }

    public static function touch(int $adminId): void
    {
        $row = static::findOne($adminId);
        if (!$row) {
            $row = new static(['admin_id' => $adminId]);
        }
        $row->last_seen_at = time();
        $row->save(false);
    }

    public static function isAnyAdminOnline(int $withinSeconds = 300): bool
    {
        $threshold = time() - $withinSeconds;

        return static::find()->where(['>=', 'last_seen_at', $threshold])->exists();
    }
}
