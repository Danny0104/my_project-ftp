<?php

namespace backend\assets;

use yii\web\AssetBundle;

class AdminAnalyticsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/pages/analytics.css',
    ];

    public $js = [
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
        'js/admin-analytics.js',
    ];

    public $depends = [
        AdminPlatformAsset::class,
    ];
}
