<?php

namespace frontend\modules\organization\controllers;

use common\models\OrgTeamActivity;
use common\models\OrgTeamMember;
use Yii;
use yii\web\NotFoundHttpException;

class TeamController extends BaseController
{
    protected function navKey(): string
    {
        return 'team';
    }

    public function actionIndex()
    {
        $members = OrgTeamMember::find()
            ->where(['organization_id' => $this->orgId()])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        if (!$members) {
            $this->seedOwnerMember();
            $members = OrgTeamMember::find()
                ->where(['organization_id' => $this->orgId()])
                ->all();
        }

        $activity = OrgTeamActivity::find()
            ->where(['organization_id' => $this->orgId()])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(30)
            ->all();

        $this->view->title = 'Team Management';

        return $this->render('index', [
            'members' => $members,
            'activity' => $activity,
            'roleOptions' => OrgTeamMember::roleOptions(),
        ]);
    }

    public function actionInvite()
    {
        $email = trim((string) Yii::$app->request->post('email', ''));
        $name = trim((string) Yii::$app->request->post('name', ''));
        $role = (string) Yii::$app->request->post('role', OrgTeamMember::ROLE_RECRUITER);

        if ($email === '' || $name === '') {
            return $this->jsonError('Name and email are required.');
        }

        $exists = OrgTeamMember::findOne(['organization_id' => $this->orgId(), 'email' => $email]);
        if ($exists) {
            return $this->jsonError('This email is already on the team.');
        }

        $member = new OrgTeamMember();
        $member->organization_id = $this->orgId();
        $member->email = $email;
        $member->name = $name;
        $member->role = $role;
        $member->status = OrgTeamMember::STATUS_INVITED;
        $member->permissions_json = json_encode(OrgTeamMember::defaultPermissionsForRole($role));

        if (!$member->save()) {
            return $this->jsonError('Invite failed.', ['errors' => $member->errors]);
        }

        $this->audit('team.invited', ['email' => $email, 'role' => $role]);
        return $this->jsonSuccess(['id' => $member->id]);
    }

    public function actionUpdateRole()
    {
        $member = $this->findMember((int) Yii::$app->request->post('id'));
        $member->role = (string) Yii::$app->request->post('role');
        $member->permissions_json = json_encode(OrgTeamMember::defaultPermissionsForRole($member->role));
        $member->save(false);
        $this->audit('team.role_updated', ['id' => $member->id, 'role' => $member->role]);
        return $this->jsonSuccess();
    }

    public function actionUpdateStatus()
    {
        $member = $this->findMember((int) Yii::$app->request->post('id'));
        $status = (string) Yii::$app->request->post('status');
        if (!in_array($status, [OrgTeamMember::STATUS_ACTIVE, OrgTeamMember::STATUS_SUSPENDED, OrgTeamMember::STATUS_INVITED], true)) {
            return $this->jsonError('Invalid status.');
        }
        $member->status = $status;
        $member->save(false);
        $this->audit('team.status_updated', ['id' => $member->id]);
        return $this->jsonSuccess();
    }

    public function actionDelete()
    {
        $member = $this->findMember((int) Yii::$app->request->post('id'));
        if ($member->user_id && (int) $member->user_id === (int) Yii::$app->user->id) {
            return $this->jsonError('You cannot remove the account owner.');
        }
        $member->delete();
        $this->audit('team.removed', ['id' => $member->id]);
        return $this->jsonSuccess();
    }

    private function findMember(int $id): OrgTeamMember
    {
        $m = OrgTeamMember::findOne(['id' => $id, 'organization_id' => $this->orgId()]);
        if (!$m) {
            throw new NotFoundHttpException('Team member not found.');
        }
        return $m;
    }

    private function seedOwnerMember(): void
    {
        $user = Yii::$app->user->identity;
        $member = new OrgTeamMember();
        $member->organization_id = $this->orgId();
        $member->user_id = $user->id;
        $member->email = $user->email ?? ($user->username . '@organization.local');
        $member->name = $user->username ?? 'Owner';
        $member->role = OrgTeamMember::ROLE_HR;
        $member->status = OrgTeamMember::STATUS_ACTIVE;
        $member->last_active_at = time();
        $member->permissions_json = json_encode(OrgTeamMember::defaultPermissionsForRole($member->role));
        $member->save(false);
    }
}
