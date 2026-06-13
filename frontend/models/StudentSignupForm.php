<?php

namespace frontend\models;

use common\models\FieldOfStudy;
use common\models\Student;
use common\models\User;
use common\services\RegistrationService;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class StudentSignupForm extends Model
{
    public $firstname;
    public $lastname;
    public $username;
    public $email;
    public $phone;
    public $password;
    public $confirm_password;
    public $university;
    public $university_other;
    public $field_of_study;
    public $academic_level;
    public $graduation_year;
    public $terms;
    public $newsletter;

    /** @var UploadedFile|null */
    public $cvFile;

    public function rules()
    {
        return [
            [['firstname', 'lastname', 'username', 'email', 'phone', 'password', 'confirm_password', 'university', 'field_of_study', 'academic_level', 'graduation_year'], 'required'],
            [['firstname', 'lastname', 'username', 'email', 'phone', 'university', 'university_other', 'field_of_study'], 'trim'],
            [['firstname', 'lastname'], 'string', 'min' => 2, 'max' => 100],
            ['username', 'string', 'min' => 3, 'max' => 50],
            ['username', 'match', 'pattern' => '/^[a-zA-Z0-9_]+$/', 'message' => 'Username can only contain letters, numbers, and underscores.'],
            ['username', 'unique', 'targetClass' => User::class, 'message' => 'This username has already been taken.'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => User::class, 'message' => 'This email address has already been taken.'],
            ['phone', 'string', 'max' => 20],
            ['password', 'string', 'min' => Yii::$app->params['user.passwordMinLength']],
            ['password', 'match', 'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', 'message' => 'Password must contain uppercase, lowercase, and a number.'],
            ['confirm_password', 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],
            ['university', 'string', 'max' => 255],
            ['university_other', 'required', 'when' => static fn(self $m): bool => $m->university === 'Other (Please specify)', 'whenClient' => "function () { return $('#student-university').val() === 'Other (Please specify)'; }"],
            ['field_of_study', 'string', 'max' => 255],
            ['field_of_study', 'in', 'range' => array_values(array_filter(array_keys(self::fieldOptions())))],
            ['academic_level', 'in', 'range' => ['undergraduate', 'graduate', 'postgraduate', 'diploma']],
            ['graduation_year', 'integer', 'min' => (int) date('Y'), 'max' => (int) date('Y') + 8],
            ['cvFile', 'file', 'extensions' => ['pdf', 'doc', 'docx'], 'maxSize' => 5 * 1024 * 1024, 'skipOnEmpty' => true],
            ['terms', 'required', 'requiredValue' => 1, 'message' => 'You must agree to the terms and conditions.'],
            ['newsletter', 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'firstname' => 'First Name',
            'lastname' => 'Last Name',
            'academic_level' => 'Education Level',
            'graduation_year' => 'Graduation Year',
            'cvFile' => 'CV Upload',
        ];
    }

    public static function universityOptions(): array
    {
        return [
            '' => 'Select your university…',
            'University of Dar es Salaam (UDSM)' => 'University of Dar es Salaam (UDSM)',
            'Sokoine University of Agriculture (SUA)' => 'Sokoine University of Agriculture (SUA)',
            'Open University of Tanzania (OUT)' => 'Open University of Tanzania (OUT)',
            'State University of Zanzibar (SUZA)' => 'State University of Zanzibar (SUZA)',
            'Mzumbe University (MU)' => 'Mzumbe University (MU)',
            'Other (Please specify)' => 'Other (Please specify)',
        ];
    }

    public static function fieldOptions(): array
    {
        $options = ['' => 'Select field of study…'];
        foreach (FieldOfStudy::find()->where(['is_active' => true])->orderBy(['name' => SORT_ASC])->all() as $field) {
            $options[$field->name] = $field->name;
        }

        return $options;
    }

    public static function graduationYearOptions(): array
    {
        $options = ['' => 'Select year…'];
        $start = (int) date('Y');
        for ($year = $start; $year <= $start + 8; $year++) {
            $options[$year] = (string) $year;
        }

        return $options;
    }

    public function resolvedUniversity(): string
    {
        if ($this->university === 'Other (Please specify)') {
            return trim((string) $this->university_other);
        }

        return trim((string) $this->university);
    }

    public function signup(): bool
    {
        $this->cvFile = UploadedFile::getInstance($this, 'cvFile');

        if (!$this->validate()) {
            return false;
        }

        $service = new RegistrationService();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = $service->createUser([
                'username' => $this->username,
                'email' => $this->email,
                'password' => $this->password,
                'first_name' => $this->firstname,
                'last_name' => $this->lastname,
                'phone' => $this->phone,
                'role' => 'student',
            ]);
            if (!$user) {
                $this->addError('email', 'Could not create account. Please try again.');
                $transaction->rollBack();
                return false;
            }

            $student = $service->createStudentProfile($user, [
                'university' => $this->resolvedUniversity(),
                'field_of_study' => $this->field_of_study,
                'academic_level' => $this->academic_level,
                'graduation_year' => $this->graduation_year,
                'cvFile' => $this->cvFile,
            ]);
            if (!$student) {
                $this->addError('email', 'Could not create student profile.');
                $transaction->rollBack();
                return false;
            }

            if (!$service->sendVerificationEmail($user, $this->email)) {
                Yii::warning('Verification email failed for user ' . $user->id, 'registration');
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
