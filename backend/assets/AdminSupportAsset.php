<?php

namespace backend\assets;

use yii\web\AssetBundle;

class AdminSupportAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/support-admin.css',
    ];

    public $js = [
        'js/support-admin.js',
    ];

    public $depends = [
        AdminPlatformAsset::class,
    ];
}

