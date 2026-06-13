<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Student platform pages — Opportunities, Applications, Messages
 */
class StudentPlatformAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/student-dashboard.css',
        'css/student-shell-premium.css',
        'css/student-platform.css',
        'css/premium-cards.css',
        'css/theme-tokens.css',
        'css/theme-overrides.css',
        'css/enterprise-saas-system.css',
        'css/enterprise-saas-light-only.css',
        'css/button-contrast.css',
    ];

    public $js = [
        'js/layout-shell.js',
        'js/student-dashboard.js',
        'js/student-platform.js',
    ];

    public $depends = [
        DashboardShellAsset::class,
        PlatformResponsiveAsset::class,
        ProfileAvatarAsset::class,
    ];
}
