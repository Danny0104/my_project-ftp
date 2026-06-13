<?php

namespace backend\assets;

use yii\web\AssetBundle;

class AdminPlatformAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/admin-platform.css',
        'css/admin-pages.css',
        'css/theme-tokens.css',
        'css/theme-overrides.css',
        'css/enterprise-saas-system.css',
        'css/enterprise-saas-light-only.css',
        'css/button-contrast.css',
    ];
    public $js = [
        'js/layout-shell.js',
        'js/admin-platform.js',
        'js/admin-module.js',
        'js/admin-crud.js',
    ];
    public $depends = [
        AdminShellAsset::class,
    ];
}
