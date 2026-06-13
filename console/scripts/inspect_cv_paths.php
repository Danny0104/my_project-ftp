<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';

$config = require __DIR__ . '/../../common/config/main-local.php';
$db = $config['components']['db'] ?? null;
if (!$db) {
    fwrite(STDERR, "No db config\n");
    exit(1);
}

$dsn = $db['dsn'] ?? '';
preg_match('/dbname=([^;]+)/', $dsn, $m);
$dbName = $m[1] ?? 'yii2advanced';

$pdo = new PDO($dsn, $db['username'] ?? 'root', $db['password'] ?? '');
$rows = $pdo->query("SELECT id, user_id, cv FROM student WHERE cv IS NOT NULL AND cv <> ''")->fetchAll(PDO::FETCH_ASSOC);

echo "CV rows: " . count($rows) . PHP_EOL;
foreach ($rows as $row) {
    echo "student#{$row['id']} user#{$row['user_id']}: {$row['cv']}" . PHP_EOL;
    $abs = __DIR__ . '/../../frontend/web/' . ltrim($row['cv'], '/');
    echo "  exists: " . (is_file($abs) ? 'yes' : 'no') . " ($abs)" . PHP_EOL;
}
