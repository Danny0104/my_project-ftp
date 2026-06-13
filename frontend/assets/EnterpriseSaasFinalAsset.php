<?php

namespace frontend\assets;

use yii\web\AssetBundle;

/**
 * Final cascade layer — ensures enterprise SaaS overrides win over feature CSS.
 */
class EnterpriseSaasFinalAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/enterprise-saas-system.css',
        'css/enterprise-saas-light-only.css',
        'css/button-contrast.css',
    ];
}
