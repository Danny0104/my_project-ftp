<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Student settings workspace assets.
 */
class StudentSettingsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/student-settings.css',
    ];

    public $js = [
        'js/student-settings.js',
    ];

    public $depends = [
        StudentPlatformAsset::class,
    ];
}
