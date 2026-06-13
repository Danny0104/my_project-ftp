<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Student applications tracker — premium workspace UI.
 */
class StudentApplicationsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/student-applications.css',
    ];

    public $js = [
        'js/student-applications.js',
    ];

    public $depends = [
        StudentPlatformAsset::class,
    ];
}
