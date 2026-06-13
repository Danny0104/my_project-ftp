<?php

namespace backend\assets;

use yii\web\AssetBundle;

/**
 * Admin dashboard shell — Bootstrap + layout stabilization (no duplicate CDN).
 */
class AdminShellAsset extends AssetBundle
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

    public function init(): void
    {
        parent::init();
        $this->css[] = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
    }

    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
}
