<?php

/**
 * Resolve DB connection settings from DB_* or Railway MySQL plugin variables.
 */
$dbDsn = getenv('DB_DSN') ?: '';
$dbUser = getenv('DB_USER') ?: '';
$dbPass = getenv('DB_PASS') ?: '';

if ($dbDsn === '') {
    $mysqlHost = getenv('MYSQLHOST') ?: getenv('MYSQL_HOST') ?: '';
    if ($mysqlHost !== '') {
        $mysqlPort = getenv('MYSQLPORT') ?: getenv('MYSQL_PORT') ?: '3306';
        $mysqlDb = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'railway';
        $dbDsn = "mysql:host={$mysqlHost};port={$mysqlPort};dbname={$mysqlDb}";
    } else {
        $dbDsn = 'mysql:host=localhost;dbname=yii2advanced';
    }
}

if ($dbUser === '') {
    $dbUser = getenv('MYSQLUSER') ?: getenv('MYSQL_USER') ?: 'root';
}

if ($dbPass === '') {
    $dbPass = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';
}

return [
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => $dbDsn,
            'username' => $dbUser,
            'password' => $dbPass,
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
