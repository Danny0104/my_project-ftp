<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Global responsive layout rules for dashboards and public pages.
 */
class PlatformResponsiveAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/platform-responsive.css',
        'css/platform-layout.css',
    ];

    public $js = [
        'js/platform-responsive.js',
    ];
}
