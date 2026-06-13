<?php

namespace frontend\modules\support\controllers;

use Yii;
use yii\web\Controller;

/**
 * Redirects legacy Support Hub URLs to Help Center.
 */
class RedirectController extends Controller
{
    public function actionIndex()
    {
        return $this->redirect(['/site/contact']);
    }
}
