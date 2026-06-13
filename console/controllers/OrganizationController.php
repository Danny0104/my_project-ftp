<?php

namespace console\controllers;

use common\models\Organization;
use common\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Maintenance commands for organization profiles.
 */
class OrganizationController extends Controller
{
    /**
     * Create missing organization rows for users with role=organization.
     *
     * Usage: php yii organization/ensure-profiles
     */
    public function actionEnsureProfiles(): int
    {
        $users = User::find()->where(['role' => 'organization'])->all();
        $created = 0;
        $failed = 0;

        foreach ($users as $user) {
            $existing = Organization::findOne(['user_id' => $user->id]);
            if ($existing) {
                continue;
            }

            $org = Organization::findOrCreateForUserId((int) $user->id, [], false);
            if ($org) {
                $created++;
                $this->stdout("Created organization #{$org->id} for user #{$user->id} ({$user->username})\n");
            } else {
                $failed++;
                $this->stderr("Failed for user #{$user->id} ({$user->username})\n");
            }
        }

        $this->stdout("Done. Created: {$created}, Failed: {$failed}\n");

        return $failed > 0 ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
}
