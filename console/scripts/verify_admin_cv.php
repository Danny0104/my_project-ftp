<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../../common/config/bootstrap.php';
require __DIR__ . '/../../backend/config/bootstrap.php';

$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../../common/config/main-local.php',
    require __DIR__ . '/../../backend/config/main.php',
    require __DIR__ . '/../../backend/config/main-local.php'
);

$app = new yii\web\Application($config);
$controller = new backend\controllers\StudentController('student', $app);
$app->controller = $controller;

$student = common\models\Student::findOne(13);
if ($student === null) {
    echo "student#13 not found\n";
    exit(1);
}

$service = new common\services\StudentCvService();
$info = $service->describe($student);

echo "URLs:\n";
echo '  view-cv:     ' . yii\helpers\Url::to(['/student/view-cv', 'id' => $student->id]) . "\n";
echo '  download-cv: ' . yii\helpers\Url::to(['/student/download-cv', 'id' => $student->id]) . "\n";
echo "\nCV info:\n";
echo '  db path:   ' . $student->cv . "\n";
echo '  relative:  ' . ($info['relative'] ?? 'null') . "\n";
echo '  absolute:  ' . ($info['absolute'] ?? 'null') . "\n";
echo '  available: ' . ($info['available'] ? 'yes' : 'no') . "\n";
echo '  file ok:   ' . (is_file($info['absolute'] ?? '') ? 'yes' : 'no') . "\n";
