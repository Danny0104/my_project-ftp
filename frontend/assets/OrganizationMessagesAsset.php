<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Organization recruitment messaging workspace — premium 3-panel UI.
 */
class OrganizationMessagesAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/org-messages-hub.css',
        'css/org-messages-hub-layout.css',
    ];

    public $js = [
        'js/org-messages-hub.js',
    ];

    public $depends = [
        MessagingCoreAsset::class,
        NotificationHubAsset::class,
    ];
}
