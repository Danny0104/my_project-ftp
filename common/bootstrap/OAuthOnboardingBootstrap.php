<?php

namespace common\bootstrap;

use common\models\User;
use Yii;
use yii\base\ActionEvent;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\web\Controller;

/**
 * Registers a global controller filter for pending Google OAuth onboarding.
 */
class OAuthOnboardingBootstrap implements BootstrapInterface
{
    /** @var string[] */
    public array $allowedRoutes = [
        'site/complete-profile',
        'site/logout',
        'site/error',
        'site/login',
        'site/signup',
        'site/request-password-reset',
        'site/reset-password',
        'site/resend-verification-email',
        'site/verify-email',
        'session/heartbeat',
    ];

    public function bootstrap($app): void
    {
        if ($app->request->isConsoleRequest) {
            return;
        }

        Event::on(Controller::class, Controller::EVENT_BEFORE_ACTION, function (ActionEvent $event) {
            if (Yii::$app->user->isGuest) {
                return;
            }

            $identity = Yii::$app->user->identity;
            if (!$identity instanceof User || !$identity->needsOAuthProfileCompletion()) {
                return;
            }

            $route = $event->action->controller->id . '/' . $event->action->id;
            if (in_array($route, $this->allowedRoutes, true)) {
                return;
            }

            $event->isValid = false;
            Yii::$app->response->redirect(['site/complete-profile'])->send();
            Yii::$app->end();
        });
    }
}
