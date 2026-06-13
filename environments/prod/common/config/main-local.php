<?php

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => getenv('DB_DSN') ?: 'mysql:host=localhost;dbname=yii2advanced',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'charset' => 'utf8mb4',
            'enableSchemaCache' => true,
            'schemaCacheDuration' => 3600,
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'useFileTransport' => false,
            'transport' => [
                'scheme' => getenv('SMTP_ENCRYPTION') === 'ssl' ? 'smtps' : 'smtp',
                'host' => getenv('SMTP_HOST') ?: 'smtp.example.com',
                'username' => getenv('SMTP_USER') ?: '',
                'password' => getenv('SMTP_PASS') ?: '',
                'port' => (int) (getenv('SMTP_PORT') ?: 587),
            ],
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
    ],
];
