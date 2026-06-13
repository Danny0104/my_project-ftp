<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $conversation_id
 * @property int $user_id
 * @property int $expires_at
 */
class ChatTyping extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%chat_typing}}';
    }

    public static function primaryKey()
    {
        return ['conversation_id', 'user_id'];
    }

    public static function setTyping(int $conversationId, int $userId, int $ttlSeconds = 5): void
    {
        $expires = time() + $ttlSeconds;
        $row = static::findOne(['conversation_id' => $conversationId, 'user_id' => $userId]);
        if (!$row) {
            $row = new static([
                'conversation_id' => $conversationId,
                'user_id' => $userId,
                'expires_at' => $expires,
            ]);
        } else {
            $row->expires_at = $expires;
        }
        $row->save(false);
    }

    public static function clearTyping(int $conversationId, int $userId): void
    {
        static::deleteAll(['conversation_id' => $conversationId, 'user_id' => $userId]);
    }

    public static function activeTypers(int $conversationId, int $excludeUserId): array
    {
        static::deleteAll('conversation_id = :c AND expires_at < :t', [':c' => $conversationId, ':t' => time()]);
        return static::find()
            ->where(['conversation_id' => $conversationId])
            ->andWhere(['>', 'expires_at', time()])
            ->andWhere(['<>', 'user_id', $excludeUserId])
            ->all();
    }
}
