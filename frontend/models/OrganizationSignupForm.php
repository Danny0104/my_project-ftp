<?php

namespace frontend\models;

use common\models\User;
use common\services\RegistrationService;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class OrganizationSignupForm extends Model
{
    public $contact_person;
    public $email;
    public $phone;
    public $password;
    public $confirm_password;
    public $organization_name;
    public $registration_number;
    public $industry;
    public $organization_type;
    public $country;
    public $region;
    public $city;
    public $address;
    public $website;
    public $terms;

    /** @var UploadedFile|null */
    public $logoFile;

    /** @var UploadedFile|null */
    public $certificateFile;

    public function rules()
    {
        return [
            [[
                'contact_person', 'email', 'phone', 'password', 'confirm_password',
                'organization_name', 'registration_number', 'industry', 'organization_type',
                'country', 'region', 'city', 'address', 'website',
            ], 'trim'],
            [['contact_person', 'email', 'phone', 'password', 'confirm_password', 'organization_name', 'registration_number', 'industry', 'organization_type', 'country', 'region', 'city', 'address'], 'required'],
            ['contact_person', 'string', 'min' => 2, 'max' => 255],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => User::class, 'message' => 'This email address has already been taken.'],
            ['phone', 'string', 'max' => 20],
            ['password', 'string', 'min' => Yii::$app->params['user.passwordMinLength']],
            ['password', 'match', 'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', 'message' => 'Password must contain uppercase, lowercase, and a number.'],
            ['confirm_password', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],
            [['organization_name', 'registration_number', 'industry', 'country', 'region', 'city'], 'string', 'max' => 255],
            ['address', 'string', 'max' => 500],
            ['website', 'url', 'defaultScheme' => 'https', 'skipOnEmpty' => true],
            ['organization_type', 'in', 'range' => ['company', 'nonprofit', 'government', 'educational', 'startup', 'other']],
            ['industry', 'in', 'range' => ['technology', 'finance', 'healthcare', 'education', 'manufacturing', 'retail', 'agriculture', 'energy', 'consulting', 'telecommunications', 'other']],
            ['logoFile', 'file', 'extensions' => ['jpg', 'jpeg', 'png', 'webp'], 'maxSize' => 5 * 1024 * 1024, 'skipOnEmpty' => true],
            ['certificateFile', 'required'],
            ['certificateFile', 'file', 'extensions' => ['pdf', 'jpg', 'jpeg', 'png'], 'maxSize' => 8 * 1024 * 1024],
            ['terms', 'required', 'requiredValue' => 1, 'message' => 'You must agree to the terms and conditions.'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'contact_person' => 'Contact Person Name',
            'organization_name' => 'Organization Name',
            'registration_number' => 'Registration Number',
            'organization_type' => 'Organization Type',
            'logoFile' => 'Organization Logo',
            'certificateFile' => 'Registration Certificate',
        ];
    }

    public static function organizationTypeOptions(): array
    {
        return [
            '' => 'Select type…',
            'company' => 'Company',
            'nonprofit' => 'Non-Profit',
            'government' => 'Government',
            'educational' => 'Educational Institution',
            'startup' => 'Startup',
            'other' => 'Other',
        ];
    }

    public static function industryOptions(): array
    {
        return [
            '' => 'Select industry…',
            'technology' => 'Technology',
            'finance' => 'Finance & Banking',
            'healthcare' => 'Healthcare',
            'education' => 'Education',
            'manufacturing' => 'Manufacturing',
            'retail' => 'Retail & Commerce',
            'agriculture' => 'Agriculture',
            'energy' => 'Energy & Utilities',
            'consulting' => 'Consulting & Professional Services',
            'telecommunications' => 'Telecommunications',
            'other' => 'Other',
        ];
    }

    public static function countryOptions(): array
    {
        return [
            '' => 'Select country…',
            'Tanzania' => 'Tanzania',
            'Kenya' => 'Kenya',
            'Uganda' => 'Uganda',
            'Rwanda' => 'Rwanda',
            'Other' => 'Other',
        ];
    }

    public function signup(): bool
    {
        $this->logoFile = UploadedFile::getInstance($this, 'logoFile');
        $this->certificateFile = UploadedFile::getInstance($this, 'certificateFile');

        if (!$this->validate()) {
            return false;
        }

        $service = new RegistrationService();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $username = $service->generateUsername(
                explode('@', (string) $this->email)[0] ?? $this->organization_name,
                'organization'
            );

            $user = $service->createUser([
                'username' => $username,
                'email' => $this->email,
                'password' => $this->password,
                'first_name' => $this->contact_person,
                'last_name' => '',
                'phone' => $this->phone,
                'role' => 'organization',
            ]);
            if (!$user) {
                $this->addError('email', 'Could not create account.');
                $transaction->rollBack();
                return false;
            }

            $organization = $service->createOrganizationProfile($user, [
                'contact_person' => $this->contact_person,
                'organization_name' => $this->organization_name,
                'registration_number' => $this->registration_number,
                'industry' => $this->industry,
                'organization_type' => $this->organization_type,
                'country' => $this->country,
                'region' => $this->region,
                'city' => $this->city,
                'address' => $this->address,
                'website' => $this->website,
                'phone' => $this->phone,
                'logoFile' => $this->logoFile,
                'certificateFile' => $this->certificateFile,
            ]);
            if (!$organization) {
                $this->addError('organization_name', 'Could not create organization profile.');
                $transaction->rollBack();
                return false;
            }

            if (!$service->sendVerificationEmail($user, $this->email)) {
                Yii::warning('Verification email failed for org user ' . $user->id, 'registration');
            }

            $transaction->commit();

            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $this->addError('email', $e->getMessage());
            return false;
        }
    }
}
