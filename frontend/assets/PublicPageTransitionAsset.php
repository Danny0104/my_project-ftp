<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Subtle fade transitions between public pages.
 */
class PublicPageTransitionAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/public-page-transitions.css',
    ];

    public $js = [
        'js/public-page-transitions.js',
    ];

    public $depends = [
        PremiumLoadingAsset::class,
    ];

    public $jsOptions = ['position' => \yii\web\View::POS_END];
}
