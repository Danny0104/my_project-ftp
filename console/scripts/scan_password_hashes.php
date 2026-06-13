<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../../console/config/main.php',
    require __DIR__ . '/../../console/config/main-local.php'
);

new yii\console\Application($config);

foreach (common\models\User::find()->all() as $user) {
    $cost = '?';
    if (preg_match('/^\$2[axy]\$(\d\d)\$/', $user->password_hash, $m)) {
        $cost = $m[1];
    }
    $valid = 'ok';
    $time = 0;
    $start = microtime(true);
    try {
        Yii::$app->security->validatePassword('password123', $user->password_hash);
        $time = round(microtime(true) - $start, 3);
    } catch (Throwable $e) {
        $valid = $e->getMessage();
        $time = round(microtime(true) - $start, 3);
    }
    echo sprintf(
        "user=%-12s id=%-3d cost=%-3s len=%-3d verify=%-8s time=%ss hash=%s\n",
        $user->username,
        $user->id,
        $cost,
        strlen($user->password_hash),
        $valid,
        $time,
        substr($user->password_hash, 0, 20) . '...'
    );
}

foreach (common\models\Admin::find()->all() as $admin) {
    $cost = '?';
    if (preg_match('/^\$2[axy]\$(\d\d)\$/', $admin->password_hash, $m)) {
        $cost = $m[1];
    }
    echo sprintf("admin=%-12s id=%-3d cost=%-3s len=%-3d hash=%s\n", $admin->username, $admin->id, $cost, strlen($admin->password_hash), substr($admin->password_hash, 0, 30) . '...');
}
