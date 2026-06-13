<?php

namespace backend\controllers;

use common\models\Admin;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

/**
 * Secured backend controller — requires authenticated admin session.
 */
abstract class BaseController extends Controller
{
    /** @var bool Require write role (super_admin or moderator) for mutating actions */
    protected bool $requireWriteRole = false;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $writeActions = ['create', 'update', 'delete', 'approve-organization', 'reject-organization', 'approve-user', 'reject-user', 'approve-application'];
        if (in_array($action->id, $writeActions, true) || $this->requireWriteRole) {
            $admin = Yii::$app->user->identity;
            if ($admin instanceof Admin && !$admin->canWrite()) {
                throw new ForbiddenHttpException('Your admin role is read-only.');
            }
        }

        if (in_array($action->id, ['create', 'update', 'delete'], true)) {
            $admin = Yii::$app->user->identity;
            if ($admin instanceof Admin && $this->id === 'admin' && !$admin->canManageAdmins()) {
                throw new ForbiddenHttpException('Only super admins can manage admin accounts.');
            }
        }

        return true;
    }
}
