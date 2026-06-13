<?php

namespace frontend\assets;

use yii\web\AssetBundle;

class ProfileAvatarAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/profile-avatars.css',
    ];

    public $depends = [];
}
