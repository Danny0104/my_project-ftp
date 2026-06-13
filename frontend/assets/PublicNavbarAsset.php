<?php

namespace frontend\assets;

use yii\web\AssetBundle;
use yii\bootstrap5\BootstrapPluginAsset;

/**
 * Premium public site navigation bar (glass header, scroll effects, mobile drawer).
 */
class PublicNavbarAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/public-navbar.css',
    ];

    public $js = [
        'js/public-navbar.js',
    ];

    public $depends = [
        AppAsset::class,
        BootstrapPluginAsset::class,
    ];

    public $jsOptions = ['position' => \yii\web\View::POS_END];
}
