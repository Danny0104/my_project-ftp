<?php

namespace console\controllers;

use common\models\Admin;
use common\models\User;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class UserController extends Controller
{
    /**
     * Rehash all user and admin passwords at the configured passwordHashCost.
     * Usage: php yii user/rehash-passwords password123
     */
    public function actionRehashPasswords(string $password = 'password123'): int
    {
        $cost = (int) (Yii::$app->security->passwordHashCost ?? 10);
        $this->stdout("Rehashing passwords at bcrypt cost {$cost}...\n");

        foreach (User::find()->all() as $user) {
            $user->setPassword($password);
            $user->save(false);
            $this->stdout("  user {$user->username} (#{$user->id})\n");
        }

        foreach (Admin::find()->all() as $admin) {
            $admin->setPassword($password);
            $admin->save(false);
            $this->stdout("  admin {$admin->username} (#{$admin->id})\n");
        }

        $this->stdout("Done.\n");

        return ExitCode::OK;
    }

    /**
     * Reset a single user's password.
     * Usage: php yii user/reset-password org1 password123
     */
    public function actionResetPassword(string $username, string $password): int
    {
        $user = User::find()->where(['username' => $username])->one();
        if (!$user) {
            $this->stderr("User not found: {$username}\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $user->setPassword($password);
        $user->save(false);
        $user->unlockAccount();
        $user->resetFailedLoginAttempts();

        $this->stdout("Password reset for {$username} (cost " . (Yii::$app->security->passwordHashCost ?? 10) . ").\n");

        return ExitCode::OK;
    }
}
