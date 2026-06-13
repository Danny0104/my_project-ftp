<?php

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

$sessionAuthTimeout = (int) ($params['session.authTimeout'] ?? 600);

return [
    'id' => 'app-backend',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
        ],
        'user' => [
            'identityClass' => 'common\models\Admin',
            'enableAutoLogin' => false,
            'authTimeout' => $sessionAuthTimeout,
            'loginUrl' => ['site/login'],
            'identityCookie' => [
                'name' => '_identity-backend',
                'httpOnly' => true,
                'secure' => (bool) ($params['session.cookieSecure'] ?? false),
                'sameSite' => 'Lax',
            ],
        ],
        'session' => [
            'name' => 'advanced-backend',
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
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [],
        ],
    ],
    'params' => $params,
];
