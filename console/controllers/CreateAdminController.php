<?php
namespace console\controllers;

use yii\console\Controller;
use yii\helpers\Console;
use yii\helpers\BaseConsole;
use common\models\User;
use Yii;

class CreateAdminController extends Controller
{
    /**
     * Creates or updates an admin user.
     * Usage: php yii create-admin/create username email password
     */
    public function actionCreate($username, $email, $password)
    {
        $user = User::findOne(['username' => $username]);
        if (!$user) {
            $user = new User();
            $user->username = $username;
            $user->email = $email;
            $user->created_at = time();
        } else {
            $user->email = $email;
        }
        $user->role = 'admin';
        $user->status = User::STATUS_ACTIVE;
        $user->setPassword($password);
        $user->generateAuthKey();
        $user->updated_at = time();
        $user->verification_token = null;
        if ($user->save(false)) {
            $this->stdout("Admin user '{$username}' created/updated successfully.\n", BaseConsole::FG_GREEN);
        } else {
            $this->stderr("Failed to create/update admin user.\n", BaseConsole::FG_RED);
            foreach ($user->getErrors() as $attr => $errors) {
                foreach ($errors as $error) {
                    $this->stderr("$attr: $error\n", BaseConsole::FG_RED);
                }
            }
        }
    }
} 