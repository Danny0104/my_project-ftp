<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use common\models\User;

/**
 * Signup form
 */
class SignupForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $role;
    public $firstname;
    public $lastname;
    public $phone;
    public $organization_name;
    public $organization_type;
    public $confirm_password;
    public $terms;
    public $newsletter;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['username', 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => '\\common\\models\\User', 'message' => 'This username has already been taken.'],
            ['username', 'string', 'min' => 2, 'max' => 255],

            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\\common\\models\\User', 'message' => 'This email address has already been taken.'],

            ['password', 'required'],
            ['password', 'string', 'min' => Yii::$app->params['user.passwordMinLength']],
            ['password', 'match', 'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', 'message' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.'],

            ['confirm_password', 'required'],
            ['confirm_password', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],

            ['role', 'required'],
            ['role', 'in', 'range' => ['student', 'organization']],

            [['firstname', 'lastname'], 'trim'],
            [['firstname', 'lastname'], 'required'],
            [['firstname', 'lastname'], 'string', 'min' => 2, 'max' => 100],

            ['phone', 'trim'],
            ['phone', 'string', 'max' => 20],

            [['organization_name', 'organization_type'], 'trim'],
            ['organization_name', 'string', 'max' => 255],
            ['organization_type', 'in', 'range' => ['company', 'nonprofit', 'government', 'educational', 'startup', 'other']],
            
            // Conditional validation for organization fields
            ['organization_name', 'required', 'when' => function($model) {
                return $model->role === 'organization';
            }, 'whenClient' => "function (attribute, value) {
                return $('#role-select').val() === 'organization';
            }"],
            ['organization_type', 'required', 'when' => function($model) {
                return $model->role === 'organization';
            }, 'whenClient' => "function (attribute, value) {
                return $('#role-select').val() === 'organization';
            }"],

            ['terms', 'required', 'requiredValue' => 1, 'message' => 'You must agree to the terms and conditions.'],
            ['newsletter', 'boolean'],
        ];
    }

    /**
     * Signs user up.
     *
     * @return bool whether the creating new account was successful and email was sent
     */
    public function signup()
    {
        if (!$this->validate()) {
            return false;
        }
        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->setPassword($this->password);
        $user->generateAuthKey();
        $user->generateEmailVerificationToken();
        $user->role = $this->role;
        $user->status = User::STATUS_INACTIVE;
        // created_at and updated_at handled by beforeSave
        if (!$user->save()) {
            Yii::error($user->getErrors(), 'signup');
            $this->addErrors($user->getErrors());
            return false;
        }
        // Create role-specific profile records
        if ($user->role === 'student') {
            $student = new \common\models\Student();
            $student->user_id = $user->id;
            $student->student_id = '';
            $student->university = '';
            $student->save(false);
        } elseif ($user->role === 'organization') {
            $orgName = trim((string) $this->organization_name);
            if ($orgName === '') {
                $orgName = $user->username;
            }
            $description = $this->organization_type
                ? 'Organization type: ' . $this->organization_type
                : null;

            $organization = \common\models\Organization::findOrCreateForUserId((int) $user->id, [
                'name' => $orgName,
                'description' => $description,
            ], false);

            if (!$organization) {
                Yii::error([
                    'message' => 'Organization profile not created during signup',
                    'user_id' => $user->id,
                ], 'organization.onboarding');
                $this->addError('organization_name', 'Could not create organization profile. Please try again.');
                return false;
            }
        }

        $this->assignRbacRole($user);

        return $this->sendEmail($user);
    }

    protected function assignRbacRole(User $user): void
    {
        if (!Yii::$app->has('authManager')) {
            return;
        }

        $auth = Yii::$app->authManager;
        $role = $auth->getRole($user->role);
        if ($role && !$auth->getAssignment($role->name, $user->id)) {
            $auth->assign($role, (int) $user->id);
        }
    }

    /**
     * Sends confirmation email to user
     * @param User $user user model to with email should be send
     * @return bool whether the email was sent
     */
    protected function sendEmail($user)
    {
        return Yii::$app
            ->mailer
            ->compose(
                ['html' => 'emailVerify-html', 'text' => 'emailVerify-text'],
                ['user' => $user]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo($this->email)
            ->setSubject('Account registration at ' . Yii::$app->name)
            ->send();
    }
}
