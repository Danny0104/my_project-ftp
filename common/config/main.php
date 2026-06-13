<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'security' => [
            // Cost 10 keeps logins responsive on XAMPP/Windows (cost 13+ can hit the 30s Apache limit under load).
            'passwordHashCost' => 10,
        ],
        'assetManager' => [
            'appendTimestamp' => true,
            'linkAssets' => false,
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'eligibility' => [
            'class' => \common\services\EligibilityService::class,
        ],
        'authManager' => [
            'class' => \yii\rbac\DbManager::class,
            'db' => 'db',
        ],
    ],
];
