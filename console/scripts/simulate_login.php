<?php

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

$app = new yii\web\Application($config);

$model = new common\models\LoginForm();
$model->username = $argv[1] ?? 'org1';
$model->password = $argv[2] ?? 'password123';
$model->rememberMe = true;

$start = microtime(true);
echo "Validating...\n";
$valid = $model->validate();
$elapsed = round(microtime(true) - $start, 3);
echo 'validate=' . ($valid ? 'true' : 'false') . " time={$elapsed}s\n";
if (!$valid) {
    print_r($model->getErrors());
    exit(1);
}

$start = microtime(true);
$loggedIn = $model->login();
$elapsed = round(microtime(true) - $start, 3);
echo 'login=' . ($loggedIn ? 'true' : 'false') . " time={$elapsed}s\n";
