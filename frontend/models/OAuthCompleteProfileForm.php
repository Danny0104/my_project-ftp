<?php

namespace frontend\models;

use common\components\EmailRoleDetector;
use common\models\Organization;
use common\models\Student;
use common\models\User;
use Yii;
use yii\base\Model;

/**
 * Role confirmation and profile completion after Google OAuth signup.
 */
class OAuthCompleteProfileForm extends Model
{
    public $role;
    public $organization_name;
    public $organization_type;

    public function rules(): array
    {
        return [
            ['role', 'required'],
            ['role', 'in', 'range' => [EmailRoleDetector::ROLE_STUDENT, EmailRoleDetector::ROLE_ORGANIZATION]],

            [['organization_name', 'organization_type'], 'trim'],
            ['organization_name', 'string', 'max' => 255],
            [
                'organization_type',
                'in',
                'range' => ['company', 'nonprofit', 'government', 'educational', 'startup', 'other'],
            ],
            [
                'organization_name',
                'required',
                'when' => static function (self $model) {
                    return $model->role === EmailRoleDetector::ROLE_ORGANIZATION;
                },
                'whenClient' => "function (attribute, value) {
                    return $('#role-select').val() === 'organization';
                }",
            ],
            [
                'organization_type',
                'required',
                'when' => static function (self $model) {
                    return $model->role === EmailRoleDetector::ROLE_ORGANIZATION;
                },
                'whenClient' => "function (attribute, value) {
                    return $('#role-select').val() === 'organization';
                }",
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'role' => 'Account type',
            'organization_name' => 'Organization name',
            'organization_type' => 'Organization type',
        ];
    }

    public function loadFromUser(User $user): void
    {
        $this->role = in_array($user->role, [
            EmailRoleDetector::ROLE_STUDENT,
            EmailRoleDetector::ROLE_ORGANIZATION,
        ], true) ? $user->role : EmailRoleDetector::detectRole($user->email);
    }

    public function complete(User $user): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $user->role = $this->role;
            $user->oauth_profile_completed = 1;

            if (!$user->save()) {
                $this->addErrors($user->getErrors());
                $transaction->rollBack();
                return false;
            }

            if ($this->role === EmailRoleDetector::ROLE_STUDENT) {
                if (Student::findOrCreateForUserId((int) $user->id) === null) {
                    $this->addError('role', 'Unable to create your student profile.');
                    $transaction->rollBack();
                    return false;
                }
            } else {
                $orgName = trim((string) $this->organization_name);
                if ($orgName === '') {
                    $orgName = $user->username;
                }

                $organization = Organization::findOrCreateForUserId((int) $user->id, [
                    'name' => $orgName,
                    'description' => $this->organization_type
                        ? 'Organization type: ' . $this->organization_type
                        : null,
                ], false);

                if ($organization === null) {
                    $this->addError('organization_name', 'Unable to create your organization profile.');
                    $transaction->rollBack();
                    return false;
                }
            }

            $transaction->commit();
            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), 'oauth-complete-profile');
            $this->addError('role', 'Unable to complete your profile. Please try again.');
            return false;
        }
    }
}
