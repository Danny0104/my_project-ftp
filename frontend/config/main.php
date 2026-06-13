<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

$sessionAuthTimeout = (int) ($params['session.authTimeout'] ?? 600);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'oauthOnboarding'],
    'controllerNamespace' => 'frontend\\controllers',
    'modules' => [
        'organization' => [
            'class' => \frontend\modules\organization\Module::class,
        ],
        'support' => [
            'class' => \frontend\modules\support\Module::class,
        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-frontend',
        ],
        'user' => [
            'identityClass' => 'common\\models\\User',
            'enableAutoLogin' => false,
            'authTimeout' => $sessionAuthTimeout,
            'loginUrl' => ['site/login'],
            'identityCookie' => [
                'name' => '_identity-frontend',
                'httpOnly' => true,
                'secure' => (bool) ($params['session.cookieSecure'] ?? false),
                'sameSite' => 'Lax',
            ],
        ],
        'session' => [
            'name' => 'advanced-frontend',
            'timeout' => $sessionAuthTimeout,
            'cookieParams' => [
                'lifetime' => 0,
                'httponly' => true,
                'secure' => (bool) ($params['session.cookieSecure'] ?? false),
                'sameSite' => 'Lax',
            ],
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'oauthOnboarding' => [
            'class' => \common\bootstrap\OAuthOnboardingBootstrap::class,
        ],
        'authClientCollection' => [
            'class' => 'yii\authclient\Collection',
            'clients' => [
                'google' => [
                    'class' => \common\components\auth\GoogleAuthClient::class,
                    'clientId' => $params['googleOAuth.clientId'] ?? '',
                    'clientSecret' => $params['googleOAuth.clientSecret'] ?? '',
                    'scope' => 'email profile openid',
                ],
            ],
        ],
        'assetManager' => [
            'appendTimestamp' => true,
            'bundles' => [
                'yii\bootstrap5\BootstrapAsset' => [
                    'css' => ['dist/css/bootstrap.min.css'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'help-api/<action:\w+>' => 'help-api/<action>',
                'support/<path:.*>' => 'site/contact',
                'api/auth/<action:\w+>' => 'api/auth/<action>',
                'api/positions' => 'api/position/index',
                'api/positions/<id:\d+>' => 'api/position/view',
                'api/positions/<id:\d+>/apply' => 'api/position/apply',
                'api/positions/create' => 'api/position/create',
                'api/applications' => 'api/application/index',
                'api/applications/<id:\d+>' => 'api/application/view',
                'api/applications/<id:\d+>/withdraw' => 'api/application/withdraw',
                'api/applications/<id:\d+>/approve' => 'api/application/approve',
                'api/applications/<id:\d+>/reject' => 'api/application/reject',
                'api/notifications' => 'api/notification/index',
                'api/notifications/<id:\d+>/read' => 'api/notification/mark-read',
                'api/notifications/<id:\d+>/unread' => 'api/notification/mark-unread',
                'api/notifications/<id:\d+>/delete' => 'api/notification/delete',
                'api/dashboard' => 'api/dashboard/index',
                'api/stats' => 'api/dashboard/stats',
            ],
        ],
    ],
    'controllerMap' => [
        'api/auth' => 'frontend\controllers\api\AuthController',
        'api/position' => 'frontend\controllers\api\PositionController',
        'api/application' => 'frontend\controllers\api\ApplicationController',
        'api/dashboard' => 'frontend\controllers\api\DashboardController',
        'api/notification' => 'frontend\controllers\api\NotificationController',
    ],
    'params' => $params,
    'defaultRoute' => 'site/index',
];

?>
