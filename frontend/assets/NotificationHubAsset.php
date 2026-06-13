<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Messages + notifications UI (shared markup uses sp-* classes from student-platform).
 * Registered by NotificationController — required for organization panel; safe on student panel.
 */
class NotificationHubAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/student-platform.css',
        'css/notification-hub-org.css',
    ];

    public $js = [
        'js/student-platform.js',
    ];

    /** Panel layouts already load SharedAsset / shell bundles. */
    public $depends = [];
}
