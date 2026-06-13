<?php

define('YII_DEBUG', true);
define('YII_ENV', 'dev');

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../../frontend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../../frontend/config/main.php',
    require __DIR__ . '/../../frontend/config/main-local.php'
);

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/my_project/frontend/web/index.php';
$_SERVER['SCRIPT_FILENAME'] = 'C:/xampp/htdocs/my_project/frontend/web/index.php';
$_SERVER['REQUEST_URI'] = '/my_project/frontend/web/index.php?r=site/signup';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['HTTPS'] = '';

$config['components']['request']['baseUrl'] = '/my_project/frontend/web';
$config['components']['request']['scriptUrl'] = '/my_project/frontend/web/index.php';

$app = new yii\web\Application($config);
$client = $app->get('authClientCollection')->getClient('google');

echo 'Client returnUrl: ' . $client->returnUrl . PHP_EOL;
