<?php

namespace frontend\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Organization module pages — charts, tables, module interactions.
 */
class OrganizationModulesAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/org/pages/modules.css',
    ];

    public $js = [
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
        'js/org/modules.js',
    ];

    public $depends = [
        OrganizationAsset::class,
    ];
}
