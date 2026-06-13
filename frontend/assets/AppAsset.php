<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Main frontend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
        'css/premium-cards.css',
        'css/theme-tokens.css',
        'css/enterprise-saas-system.css',
        'css/button-contrast.css',
    ];
    public $js = [
        'js/session-monitor.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        PlatformResponsiveAsset::class,
    ];
}
