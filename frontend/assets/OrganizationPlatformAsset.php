<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Organization panel — enterprise SaaS shell + pages.
 *
 * Frontend-only (keeps backend logic intact).
 */
class OrganizationPlatformAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/org/tokens.css',
        'css/org/components.css',
        'css/org/pages/dashboard.css',
        'css/org/pages/opportunities.css',
        'css/org/pages/position-form.css',
        'css/org/pages/ats.css',
        'css/theme-tokens.css',
        'css/theme-overrides.css',
        'css/enterprise-saas-system.css',
        'css/enterprise-saas-light-only.css',
        'css/button-contrast.css',
    ];

    public $js = [
        'js/layout-shell.js',
        'js/org/api-urls.js',
        'js/org/app-shell.js',
        'js/org/opportunities.js',
        'js/org/position-form.js',
        'js/org/ats.js',
    ];

    public $depends = [
        DashboardShellAsset::class,
        PlatformResponsiveAsset::class,
        ProfileAvatarAsset::class,
    ];
}

