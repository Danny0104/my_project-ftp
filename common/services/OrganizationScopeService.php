<?php

namespace common\services;

use common\models\Application;
use common\models\Organization;
use common\models\Position;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Resolves organization context and scopes queries to the current org.
 */
class OrganizationScopeService
{
    /**
     * Resolve organization for the current user (creates starter profile if missing).
     */
    public function requireOrganization(): Organization
    {
        if (Yii::$app->user->isGuest) {
            throw new ForbiddenHttpException('Login required.');
        }

        $user = Yii::$app->user->identity;
        if (!$user || $user->role !== 'organization') {
            throw new ForbiddenHttpException('Organization access only.');
        }

        $org = Organization::findOrCreateForUserId((int) $user->id);
        if (!$org) {
            Yii::error([
                'message' => 'Organization user without organization row and auto-create failed',
                'user_id' => $user->id,
            ], 'organization.onboarding');

            throw new NotFoundHttpException(
                'Unable to load your organization profile. Please contact support or try again.'
            );
        }

        return $org;
    }

    /**
     * Safe lookup without throwing (for views/helpers).
     */
    public function resolveOrganization(): ?Organization
    {
        if (Yii::$app->user->isGuest) {
            return null;
        }

        $user = Yii::$app->user->identity;
        if (!$user || $user->role !== 'organization') {
            return null;
        }

        return Organization::findOrCreateForUserId((int) $user->id);
    }

    public function applicationQuery(int $organizationId)
    {
        return Application::find()
            ->alias('a')
            ->innerJoin(['p' => Position::tableName()], 'p.id = a.position_id')
            ->where(['p.organization_id' => $organizationId]);
    }

    public function positionIds(int $organizationId): array
    {
        return Position::find()
            ->select('id')
            ->where(['organization_id' => $organizationId])
            ->column();
    }
}
