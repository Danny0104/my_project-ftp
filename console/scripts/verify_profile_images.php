<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../../console/config/bootstrap.php';

$web = dirname(__DIR__, 2) . '/frontend/web';
$checks = [
    'org logos dir' => is_dir($web . '/uploads/organizations/logos'),
    'student photos dir' => is_dir($web . '/uploads/students/photos'),
];

foreach ($checks as $label => $ok) {
    echo ($ok ? '[OK]' : '[FAIL]') . ' ' . $label . PHP_EOL;
}
