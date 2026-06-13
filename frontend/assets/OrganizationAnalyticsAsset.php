<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Analytics & Reports page — layout, charts, filters.
 */
class OrganizationAnalyticsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/org/pages/analytics.css',
    ];

    public $js = [
        'js/org/analytics.js',
    ];

    public $depends = [
        OrganizationModulesAsset::class,
    ];
}
