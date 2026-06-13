<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Premium loading — global overlay, route progress, skeletons, hero sequence.
 */
class PremiumLoadingAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/premium-loading.css',
    ];

    public $js = [
        'js/premium-loading.js',
    ];

    public $depends = [
        AppAsset::class,
    ];

    public $jsOptions = ['position' => \yii\web\View::POS_END];
}
