<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Public internship discovery marketplace (Positions index for guests).
 */
class PositionsMarketplaceAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/positions-marketplace.css',
    ];

    public $js = [
        'js/positions-marketplace.js',
    ];

    public $depends = [
        AppAsset::class,
        ProfileAvatarAsset::class,
    ];

    public $jsOptions = ['position' => \yii\web\View::POS_END];
}
