<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Compact student profile page assets.
 */
class StudentProfileAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/student-profile-compact.css',
    ];

    public $js = [
        'js/student-profile.js',
    ];

    public $depends = [
        StudentPlatformAsset::class,
    ];
}
