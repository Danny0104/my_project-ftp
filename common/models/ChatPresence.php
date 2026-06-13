<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $user_id
 * @property int $is_online
 * @property int $last_seen_at
 * @property int $updated_at
 */
class ChatPresence extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%chat_presence}}';
    }

    public static function primaryKey()
    {
        return ['user_id'];
    }

    public static function touch(int $userId, bool $online = true): void
    {
        $now = time();
        $row = static::findOne($userId);
        if (!$row) {
            $row = new static([
                'user_id' => $userId,
                'is_online' => $online ? 1 : 0,
                'last_seen_at' => $now,
                'updated_at' => $now,
            ]);
            $row->save(false);
            return;
        }
        $row->is_online = $online ? 1 : 0;
        $row->last_seen_at = $now;
        $row->updated_at = $now;
        $row->save(false);
    }

    public static function setOffline(int $userId): void
    {
        static::touch($userId, false);
    }
}
