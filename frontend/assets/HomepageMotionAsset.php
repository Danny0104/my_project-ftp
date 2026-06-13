<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Homepage animations and interactions (hero, stats, positions only).
 */
class HomepageMotionAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/homepage-motion.css',
    ];

    public $js = [
        'js/homepage-motion.js',
    ];

    public $depends = [
        AppAsset::class,
    ];

    public $jsOptions = ['position' => \yii\web\View::POS_END];
}
