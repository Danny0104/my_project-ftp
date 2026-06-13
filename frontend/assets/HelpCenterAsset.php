<?php

namespace frontend\assets;

use yii\web\AssetBundle;

class HelpCenterAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/help-center.css',
    ];

    public $js = [
        'js/help-center.js',
    ];

    public $depends = [
        \yii\web\YiiAsset::class,
    ];
}
