<?php

use yii\db\Migration;

/**
 * Assigns RBAC roles to records in user table using existing user.role values.
 */
class m260602_120120_assign_user_roles_to_rbac extends Migration
{
    public function safeUp()
    {
        /** @var \yii\rbac\DbManager $auth */
        $auth = Yii::$app->authManager;

        $rows = (new \yii\db\Query())
            ->from('{{%user}}')
            ->select(['id', 'role'])
            ->all();

        foreach ($rows as $row) {
            $userId = (string) $row['id'];
            $roleName = (string) $row['role'];
            $role = $auth->getRole($roleName);
            if (!$role) {
                continue;
            }
            if (!$auth->getAssignment($roleName, $userId)) {
                $auth->assign($role, $userId);
            }
        }
    }

    public function safeDown()
    {
        /** @var \yii\rbac\DbManager $auth */
        $auth = Yii::$app->authManager;
        $auth->removeAllAssignments();
    }
}

