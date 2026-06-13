<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Shared premium styling and motion for public About & Contact pages.
 */
class PublicPagesAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/public-pages.css',
    ];

    public $js = [
        'js/public-pages.js',
    ];

    public $depends = [
        AppAsset::class,
    ];

    public $jsOptions = ['position' => \yii\web\View::POS_END];
}
