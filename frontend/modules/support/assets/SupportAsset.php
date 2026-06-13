<?php

namespace frontend\modules\support\assets;

use frontend\assets\OrganizationAsset;
use frontend\assets\StudentAsset;
use yii\web\AssetBundle;

class SupportAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/support-hub.css',
    ];

    public $js = [
        'js/support-hub.js',
    ];

    public $depends = [
        StudentAsset::class,
        OrganizationAsset::class,
    ];
}

