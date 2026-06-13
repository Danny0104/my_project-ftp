<?php

namespace common\components\auth;

use Yii;
use yii\authclient\clients\Google;
use yii\helpers\Url;

/**
 * Google OAuth client with a callback URL that matches the current app host/path.
 */
class GoogleAuthClient extends Google
{
    public function init()
    {
        parent::init();

        $override = trim((string) (Yii::$app->params['googleOAuth.returnUrl'] ?? ''));
        if ($override !== '') {
            $this->returnUrl = $override;
            return;
        }

        $this->returnUrl = Url::to(['/site/auth', 'authclient' => 'google'], true);
    }
}
