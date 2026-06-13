<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Premium opportunity / position detail page (student + public).
 */
class PositionDetailAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/position-detail.css',
    ];

    public $js = [
        'js/position-detail.js',
    ];

    public $depends = [
        SharedAsset::class,
        ProfileAvatarAsset::class,
    ];
}
