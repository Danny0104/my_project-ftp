<?php

use yii\db\Migration;

/**
 * Seeds RBAC permissions for Support hub.
 */
class m260602_120110_support_rbac_seed extends Migration
{
    public function safeUp()
    {
        /** @var \yii\rbac\DbManager $auth */
        $auth = Yii::$app->authManager;

        $pCreate = $this->perm($auth, 'support.ticket.create', 'Create support tickets');
        $pViewOwn = $this->perm($auth, 'support.ticket.viewOwn', 'View own support tickets');
        $pReplyOwn = $this->perm($auth, 'support.ticket.replyOwn', 'Reply on own support tickets');
        $pUploadOwn = $this->perm($auth, 'support.ticket.uploadOwn', 'Upload attachments on own support tickets');

        $pManageAll = $this->perm($auth, 'support.ticket.manageAll', 'Manage all support tickets');
        $pInternalNote = $this->perm($auth, 'support.ticket.note.internal', 'Add internal notes to support tickets');
        $pBroadcast = $this->perm($auth, 'support.announcement.broadcast', 'Broadcast support announcements');

        $rStudent = $this->role($auth, 'student', 'Student');
        $rOrg = $this->role($auth, 'organization', 'Organization');
        $rAdmin = $this->role($auth, 'admin', 'Admin');

        foreach ([$pCreate, $pViewOwn, $pReplyOwn, $pUploadOwn] as $p) {
            $this->addChildSafe($auth, $rStudent, $p);
            $this->addChildSafe($auth, $rOrg, $p);
        }

        foreach ([$pManageAll, $pInternalNote, $pBroadcast] as $p) {
            $this->addChildSafe($auth, $rAdmin, $p);
        }
    }

    public function safeDown()
    {
        /** @var \yii\rbac\DbManager $auth */
        $auth = Yii::$app->authManager;

        foreach ([
            'support.ticket.create',
            'support.ticket.viewOwn',
            'support.ticket.replyOwn',
            'support.ticket.uploadOwn',
            'support.ticket.manageAll',
            'support.ticket.note.internal',
            'support.announcement.broadcast',
        ] as $name) {
            $perm = $auth->getPermission($name);
            if ($perm) {
                $auth->remove($perm);
            }
        }

        foreach (['student', 'organization', 'admin'] as $roleName) {
            $role = $auth->getRole($roleName);
            if ($role) {
                $auth->remove($role);
            }
        }
    }

    private function perm(\yii\rbac\ManagerInterface $auth, string $name, string $desc): \yii\rbac\Permission
    {
        $p = $auth->getPermission($name);
        if ($p) {
            return $p;
        }
        $p = $auth->createPermission($name);
        $p->description = $desc;
        $auth->add($p);
        return $p;
    }

    private function role(\yii\rbac\ManagerInterface $auth, string $name, string $desc): \yii\rbac\Role
    {
        $r = $auth->getRole($name);
        if ($r) {
            return $r;
        }
        $r = $auth->createRole($name);
        $r->description = $desc;
        $auth->add($r);
        return $r;
    }

    private function addChildSafe(\yii\rbac\ManagerInterface $auth, $parent, $child): void
    {
        if (!$auth->hasChild($parent, $child)) {
            $auth->addChild($parent, $child);
        }
    }
}

