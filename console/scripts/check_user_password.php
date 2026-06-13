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

$username = $argv[1] ?? 'org1';
$password = $argv[2] ?? 'password123';

$user = common\models\User::find()->where(['username' => $username])->one();
if (!$user) {
    echo "User not found: {$username}\n";
    exit(1);
}

echo "id={$user->id}\n";
echo "username={$user->username}\n";
echo "role={$user->role}\n";
echo "status={$user->status}\n";
echo "hash={$user->password_hash}\n";
echo 'hash_len=' . strlen($user->password_hash) . "\n";

if (preg_match('/^\$2[axy]\$(\d\d)\$/', $user->password_hash, $m)) {
    echo "cost={$m[1]}\n";
} else {
    echo "cost=INVALID_FORMAT\n";
}

$start = microtime(true);
try {
    $ok = Yii::$app->security->validatePassword($password, $user->password_hash);
    $elapsed = round(microtime(true) - $start, 3);
    echo 'verify=' . ($ok ? 'true' : 'false') . " time={$elapsed}s\n";
} catch (Throwable $e) {
    echo 'error: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
}
