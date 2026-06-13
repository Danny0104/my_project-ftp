<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Student opportunities marketplace — isolated UI assets.
 */
class StudentOpportunitiesAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/student-opportunities.css',
    ];

    public $js = [
        'js/student-opportunities.js',
    ];

    public $depends = [
        StudentPlatformAsset::class,
        ProfileAvatarAsset::class,
    ];
}
