<?php
namespace console\controllers;

use yii\console\Controller;
use yii\helpers\BaseConsole;
use common\models\Admin;
use Yii;

class FixAdminPasswordController extends Controller
{
    /**
     * Sets a valid password hash for the given admin user.
     * Usage: php yii fix-admin-password/set username newpassword
     */
    public function actionSet($username, $password)
    {
        $admin = Admin::findOne(['username' => $username]);
        if (!$admin) {
            $this->stderr("Admin user '{$username}' not found.\n", BaseConsole::FG_RED);
            return 1;
        }
        $admin->setPassword($password);
        $admin->save(false);
        $this->stdout("Password for admin user '{$username}' has been updated.\n", BaseConsole::FG_GREEN);
        return 0;
    }
} 