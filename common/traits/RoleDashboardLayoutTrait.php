<?php

namespace common\traits;

use common\models\Organization;
use Yii;

/**
 * Routes authenticated users to the correct dashboard layout shell.
 * Only replaces the legacy `internal` layout — never overrides `main`, `auth`, or explicit layouts.
 *
 * Important: set `$this->layout` in controllers (e.g. student vs main for position/index).
 * Layout cannot be changed from views (`$this->context->layout` in a view is too late).
 * Panel CSS/JS must be registered in layouts via StudentAsset / OrganizationAsset / AdminAsset.
 */
trait RoleDashboardLayoutTrait
{
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (Yii::$app->user->isGuest) {
            return true;
        }

        $layout = $this->layout;
        if ($layout !== 'internal' && $layout !== null) {
            return true;
        }

        $role = Yii::$app->user->identity->role ?? null;
        if ($role === 'student') {
            $this->layout = 'student';
        } elseif ($role === 'organization') {
            $this->layout = 'organization';
            Organization::findOrCreateForUserId((int) Yii::$app->user->id);
        }

        return true;
    }
}
