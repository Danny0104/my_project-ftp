<?php

namespace frontend\assets;

use yii\bootstrap5\BootstrapAsset;
use yii\bootstrap5\BootstrapPluginAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

/**
 * Shared design system for all dashboard panels (Bootstrap 5, tokens, layout reset).
 * Does not load site.css or panel-specific styles.
 */
class SharedAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/design-tokens.css',
        'css/layout-shell-reset.css',
        'css/dashboard-scopes.css',
        'css/modal-stack.css',
        'css/theme-tokens.css',
        'css/button-contrast.css',
    ];

    public $js = [
        'js/theme-bridge.js',
        'js/modal-stack.js',
        'js/session-monitor.js',
    ];

    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
        BootstrapPluginAsset::class,
    ];

    public function init(): void
    {
        parent::init();
        $this->css[] = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
    }
}
