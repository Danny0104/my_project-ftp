<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Chart.js for student dashboard analytics widgets.
 */
class StudentChartsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $js = [
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
        'js/student-charts.js',
    ];

    public $depends = [
        StudentPlatformAsset::class,
    ];
}
