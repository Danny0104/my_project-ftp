<?php

/** @var \yii\web\View $this */
/** @var string $content */

use common\widgets\Alert;
use frontend\assets\AppAsset;
use yii\bootstrap5\Html;

AppAsset::register($this);
$this->registerJsFile('@web/js/auth-animation.js', [
    'depends' => [\frontend\assets\AppAsset::class],
    'position' => \yii\web\View::POS_END,
]);
$this->registerJsFile('@web/js/signup-wizard.js', [
    'depends' => [\frontend\assets\AppAsset::class],
    'position' => \yii\web\View::POS_END,
]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body class="auth-page">
<?php $this->beginBody() ?>

<?= Alert::widget() ?>

<?= $content ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
