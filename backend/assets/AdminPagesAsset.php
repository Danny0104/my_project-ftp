<?php

namespace backend\assets;

use yii\web\AssetBundle;

/**
 * Admin module pages — executive dashboard, KPI grids, module toolbars.
 */
class AdminPagesAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $js = [
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
        'js/admin-dash.js',
    ];

    public $depends = [
        AdminPlatformAsset::class,
    ];
}
