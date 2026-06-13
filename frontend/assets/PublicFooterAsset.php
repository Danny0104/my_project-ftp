<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Compact premium public site footer.
 */
class PublicFooterAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/public-footer.css',
    ];

    public $js = [
        'js/public-footer.js',
    ];

    public $depends = [
        AppAsset::class,
    ];

    public $jsOptions = ['position' => \yii\web\View::POS_END];
}
