<?php
namespace console\controllers;

use yii\console\Controller;
use common\models\Admin;

class ListAdminController extends Controller
{
    /**
     * Lists all admin users and their statuses.
     * Usage: php yii list-admin
     */
    public function actionIndex()
    {
        $admins = Admin::find()->all();
        if (empty($admins)) {
            echo "No admin users found.\n";
            return 0;
        }
        printf("%-5s %-20s %-30s %-10s\n", 'ID', 'Username', 'Email', 'Status');
        echo str_repeat('-', 70) . "\n";
        foreach ($admins as $admin) {
            $status = $admin->status == Admin::STATUS_ACTIVE ? 'ACTIVE' : 'DELETED';
            printf("%-5d %-20s %-30s %-10s\n", $admin->id, $admin->username, $admin->email, $status);
        }
        return 0;
    }
} 