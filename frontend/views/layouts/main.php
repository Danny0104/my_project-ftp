<?php

/** @var \yii\web\View $this */
/** @var string $content */

use frontend\assets\AppAsset;
use frontend\assets\EnterpriseSaasFinalAsset;
use frontend\assets\PremiumLoadingAsset;
use frontend\assets\PublicFooterAsset;
use frontend\assets\PublicNavbarAsset;
use frontend\assets\PublicPageTransitionAsset;
use yii\bootstrap5\Html;
use yii\helpers\Url;

AppAsset::register($this);
PremiumLoadingAsset::register($this);
PublicNavbarAsset::register($this);
PublicFooterAsset::register($this);
PublicPageTransitionAsset::register($this);
EnterpriseSaasFinalAsset::register($this);
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
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
<body class="site-public-page">
<?php $this->beginBody() ?>

<?= $this->render('_publicLoader') ?>
<?= $this->render('_publicNav') ?>

<main class="site-public-main" role="main">
    <?= $content ?>
</main>

<?= $this->render('_publicFooter') ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
