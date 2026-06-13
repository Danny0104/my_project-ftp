<?php

namespace frontend\modules\organization;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'frontend\modules\organization\controllers';

    public function init()
    {
        parent::init();
        $this->layout = '@frontend/views/layouts/organization.php';
    }
}
