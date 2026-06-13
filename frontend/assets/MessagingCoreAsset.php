<?php

namespace frontend\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Live messaging core (HTTP + optional Socket.IO).
 */
class MessagingCoreAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/messaging-core.css',
        'css/messaging-premium.css',
    ];

    public $js = [
        'https://cdn.socket.io/4.7.5/socket.io.min.js',
        'js/messaging/event-bus.js',
        'js/messaging/status-policy.js',
        'js/messaging/transport.js',
        'js/messaging/realtime-socket.js',
        'js/messaging/message-renderer.js',
        'js/messaging/composer.js',
        'js/messaging/conversation-store.js',
        'js/messaging/messaging-features.js',
        'js/messaging/hub.js',
        'js/messaging/bootstrap.js',
    ];

    public $depends = [
        JqueryAsset::class,
    ];
}
