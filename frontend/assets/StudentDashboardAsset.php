<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Student home — next-gen command center (dashboard only).
 */
class StudentDashboardAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/student-command-center.css',
    ];

    public $js = [
        'js/student-command-center.js',
    ];

    public $depends = [
        StudentPlatformAsset::class,
    ];
}
