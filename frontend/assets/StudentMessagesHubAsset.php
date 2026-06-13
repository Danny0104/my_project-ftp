<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Student messages hub UI enhancements.
 */
class StudentMessagesHubAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/student-messages-hub.css',
    ];

    public $js = [
        'js/student-messages-hub.js',
    ];

    public $depends = [
        MessagingCoreAsset::class,
        NotificationHubAsset::class,
    ];
}
