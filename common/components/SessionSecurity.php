<?php

namespace common\components;

use Yii;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;

/**
 * Central session security settings for auth timeout and inactivity monitoring.
 */
class SessionSecurity
{
    public static function authTimeout(): int
    {
        return (int) (Yii::$app->params['session.authTimeout'] ?? 600);
    }

    public static function inactivityTimeoutMs(): int
    {
        return self::authTimeout() * 1000;
    }

    public static function warningBeforeSeconds(): int
    {
        return (int) (Yii::$app->params['session.warningBefore'] ?? 300);
    }

    public static function warningBeforeMs(): int
    {
        return self::warningBeforeSeconds() * 1000;
    }

    public static function heartbeatIntervalSeconds(): int
    {
        return max(30, (int) (Yii::$app->params['session.heartbeatInterval'] ?? 60));
    }

    public static function heartbeatIntervalMs(): int
    {
        return self::heartbeatIntervalSeconds() * 1000;
    }

    /**
     * Secure session cookie defaults (secure flag follows HTTPS when available).
     *
     * @return array<string, mixed>
     */
    public static function cookieParams(): array
    {
        $secure = (bool) (Yii::$app->params['session.cookieSecure'] ?? false);
        if (Yii::$app->has('request')) {
            $secure = $secure || Yii::$app->request->isSecureConnection;
        }

        return [
            'httponly' => true,
            'secure' => $secure,
            'sameSite' => 'Lax',
        ];
    }

    /**
     * Frontend inactivity monitor configuration payload.
     *
     * @return array<string, mixed>
     */
    public static function monitorConfig(bool $protectedRoute = true): array
    {
        $app = Yii::$app;

        return [
            'enabled' => true,
            'protectedRoute' => $protectedRoute,
            'isAuthenticated' => !$app->user->isGuest,
            'loginUrl' => Url::to(['/site/login']),
            'logoutUrl' => Url::to(['/site/logout']),
            'csrfParam' => $app->request->csrfParam,
            'csrfToken' => $app->request->getCsrfToken(),
            'inactivityTimeoutMs' => self::inactivityTimeoutMs(),
            'warningBeforeMs' => self::warningBeforeMs(),
            'heartbeatUrl' => Url::to(['/session/heartbeat']),
            'heartbeatIntervalMs' => self::heartbeatIntervalMs(),
        ];
    }

    /**
     * Renew server auth/session timers and return heartbeat payload.
     *
     * @return array<string, mixed>
     */
    public static function heartbeatResponse(): array
    {
        $user = Yii::$app->user;
        if ($user->isGuest) {
            Yii::$app->response->statusCode = 401;
            return ['ok' => false, 'message' => 'Session expired'];
        }

        $user->getIdentity();
        Yii::$app->session->set('__ft_last_heartbeat', time());

        $payload = [
            'ok' => true,
            'expiresAt' => (int) Yii::$app->session->get($user->authTimeoutParam, 0),
            'serverTime' => time(),
            'timeout' => self::authTimeout(),
        ];

        // Release the session lock quickly so parallel requests (e.g. login) are not blocked.
        if (Yii::$app->session->getIsActive()) {
            Yii::$app->session->close();
        }

        return $payload;
    }

    public static function registerMonitor(View $view, bool $protectedRoute = true): void
    {
        $view->registerJs(
            'window.ftSessionConfig = ' . Json::htmlEncode(self::monitorConfig($protectedRoute)) . ';',
            View::POS_HEAD
        );
    }

    public static function isExpiredLogoutReason(?string $reason): bool
    {
        return in_array($reason, ['inactivity', 'expired', 'timeout'], true);
    }

    /**
     * Normalize logout type from POST body (manual vs auto).
     */
    public static function normalizeLogoutType(?string $type): string
    {
        return $type === 'auto' ? 'auto' : 'manual';
    }

    /**
     * Fully invalidate the authenticated session (identity + session data).
     */
    public static function performFullLogout(): void
    {
        Yii::$app->user->logout(false);
        if (Yii::$app->session->getIsActive()) {
            Yii::$app->session->destroy();
        }
    }
}
