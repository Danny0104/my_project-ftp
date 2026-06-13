<?php

namespace common\services;

use Yii;

/**
 * Pushes events to optional Socket.IO bridge (see realtime/chat-server).
 */
class ChatRealtimeBroadcaster
{
    public static function emit(int $conversationId, string $event, array $payload): void
    {
        $url = Yii::$app->params['chat.broadcastUrl'] ?? null;
        if (!$url) {
            return;
        }

        $body = json_encode([
            'room' => 'conversation:' . $conversationId,
            'event' => $event,
            'payload' => $payload,
        ], JSON_THROW_ON_ERROR);

        self::postJson($url, $body);
    }

    public static function emitUser(int $userId, string $event, array $payload): void
    {
        $url = Yii::$app->params['chat.broadcastUrl'] ?? null;
        if (!$url) {
            return;
        }

        $body = json_encode([
            'room' => 'user:' . $userId,
            'event' => $event,
            'payload' => $payload,
        ], JSON_THROW_ON_ERROR);

        self::postJson($url, $body);
    }

    private static function postJson(string $url, string $body): void
    {
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 2,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable $e) {
            Yii::warning($e->getMessage(), 'chat.realtime');
        }
    }
}
