<?php

namespace frontend\modules\organization\controllers;

use common\models\Organization;
use common\models\OrgTeamActivity;
use common\services\OrganizationScopeService;
use frontend\assets\OrganizationModulesAsset;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

abstract class BaseController extends Controller
{
    protected ?Organization $organization = null;

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            $user = Yii::$app->user->identity;
                            return $user && $user->role === 'organization';
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'invite' => ['POST'],
                    'update-status' => ['POST'],
                    'update-stage' => ['POST'],
                    'add-note' => ['POST'],
                    'schedule' => ['POST'],
                    'update' => ['POST'],
                    'schedule-interview' => ['POST'],
                    'schedule-interview' => ['POST'],
                    'evaluate' => ['POST'],
                    'approve' => ['POST'],
                    'enroll' => ['POST'],
                    'moderate' => ['POST'],
                    'save' => ['POST'],
                    'export' => ['GET'],
                    'download-cv' => ['GET'],
                    'data' => ['GET'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        Yii::debug('Org module route debug: ' . Yii::$app->request->url, __METHOD__);
        $this->organization = (new OrganizationScopeService())->requireOrganization();
        $this->view->params['orgNavActive'] = $this->navKey();

        OrganizationModulesAsset::register($this->view);

        return true;
    }

    abstract protected function navKey(): string;

    protected function orgId(): int
    {
        return (int) $this->organization->id;
    }

    protected function jsonSuccess(array $data = [], string $message = 'OK'): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return array_merge(['success' => true, 'message' => $message], $data);
    }

    protected function jsonError(string $message, array $data = []): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return array_merge(['success' => false, 'message' => $message], $data);
    }

    protected function audit(string $action, array $meta = []): void
    {
        OrgTeamActivity::log($this->orgId(), $action, Yii::$app->user->id, $meta);
    }

    protected function ensureApplicationBelongsToOrg(int $applicationId): \common\models\Application
    {
        $app = (new OrganizationScopeService())->applicationQuery($this->orgId())
            ->andWhere(['a.id' => $applicationId])
            ->one();

        if (!$app) {
            throw new ForbiddenHttpException('Application not found for this organization.');
        }

        return $app;
    }
}
