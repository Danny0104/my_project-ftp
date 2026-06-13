<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "student".
 *
 * @property int $id
 * @property int $user_id
 * @property string $student_id
 * @property string $university
 * @property string|null $field_of_study
 * @property string|null $program
 * @property string|null $department
 * @property string|null $faculty
 * @property string|null $academic_level
 * @property string|null $skills
 * @property float|null $gpa
 * @property string|null $cv
 * @property string|null $profile_photo
 * @property string|null $id_document_path
 * @property string $id_verification_status
 * @property int|null $id_verified_at
 * @property int|null $id_verified_by
 * @property string|null $id_rejection_reason
 * @property int|null $id_uploaded_at
 * @property string|null $id_ocr_data
 * @property int|null $id_ocr_confidence
 * @property string|null $id_ocr_debug
 * @property int|null $id_verification_score
 * @property string $id_verification_method
 * @property string|null $id_verification_checks
 * @property string|null $id_document_hash
 * @property bool $id_fraud_flag
 * @property string|null $id_fraud_reason
 * @property int|null $graduation_year
 * @property string|null $preferred_industry
 * @property string|null $preferred_work_mode
 * @property string|null $preferred_locations
 * @property string|null $linkedin_url
 * @property string|null $github_url
 * @property string|null $portfolio_url
 * @property string|null $personal_statement
 *
 * @property User $user
 */
class Student extends \yii\db\ActiveRecord
{
    public const ID_VERIFICATION_NONE = 'none';
    public const ID_VERIFICATION_PENDING = 'pending';
    public const ID_VERIFICATION_APPROVED = 'approved';
    public const ID_VERIFICATION_REJECTED = 'rejected';

    public const ID_METHOD_NONE = 'none';
    public const ID_METHOD_AUTO = 'auto';
    public const ID_METHOD_MANUAL = 'manual';

    public const SCENARIO_REGISTER = 'register';
    public const SCENARIO_PROFILE = 'profile';

    public static function tableName()
    {
        return 'student';
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_REGISTER] = [
            'user_id', 'student_id', 'university', 'field_of_study', 'academic_level', 'graduation_year', 'cv',
        ];
        $scenarios[self::SCENARIO_PROFILE] = [
            'student_id', 'university', 'field_of_study', 'program', 'department', 'faculty',
            'academic_level', 'graduation_year', 'skills', 'gpa', 'personal_statement', 'cv',
            'preferred_industry', 'preferred_work_mode', 'preferred_locations',
            'linkedin_url', 'github_url', 'portfolio_url',
        ];

        return $scenarios;
    }

    public function rules()
    {
        return [
            [['user_id', 'student_id', 'university', 'field_of_study'], 'required', 'on' => self::SCENARIO_REGISTER],
            [['user_id'], 'integer'],
            [['personal_statement', 'skills'], 'string'],
            [['gpa'], 'number', 'min' => 0, 'max' => 4, 'skipOnEmpty' => true],
            [['student_id'], 'string', 'max' => 50],
            [['university', 'cv', 'field_of_study', 'program', 'department', 'faculty'], 'string', 'max' => 255],
            [['preferred_industry', 'preferred_work_mode'], 'string', 'max' => 120],
            [['preferred_locations', 'linkedin_url', 'github_url', 'portfolio_url'], 'string', 'max' => 255],
            [['graduation_year'], 'integer', 'min' => 2000, 'max' => 2100, 'skipOnEmpty' => true],
            [['preferred_work_mode'], 'in', 'range' => ['remote', 'hybrid', 'onsite', 'flexible', ''], 'skipOnEmpty' => true],
            [['linkedin_url', 'github_url', 'portfolio_url'], 'url', 'defaultScheme' => 'https', 'skipOnEmpty' => true],
            [['profile_photo', 'id_document_path'], 'string', 'max' => 500],
            [['id_rejection_reason', 'id_fraud_reason'], 'string', 'max' => 500],
            [['id_ocr_data', 'id_verification_checks', 'id_ocr_debug'], 'string'],
            [['id_verified_at', 'id_verified_by', 'id_uploaded_at', 'id_ocr_confidence', 'id_verification_score'], 'integer'],
            [['id_document_hash'], 'string', 'max' => 64],
            [['id_fraud_flag'], 'boolean'],
            [['id_verification_method'], 'string', 'max' => 20],
            [['id_verification_method'], 'in', 'range' => [self::ID_METHOD_NONE, self::ID_METHOD_AUTO, self::ID_METHOD_MANUAL]],
            [['id_verification_status'], 'string', 'max' => 20],
            [['id_verification_status'], 'in', 'range' => [
                self::ID_VERIFICATION_NONE,
                self::ID_VERIFICATION_PENDING,
                self::ID_VERIFICATION_APPROVED,
                self::ID_VERIFICATION_REJECTED,
            ]],
            [['academic_level'], 'string', 'max' => 50],
            [['academic_level'], 'in', 'range' => ['undergraduate', 'graduate', 'postgraduate', 'diploma', ''], 'skipOnEmpty' => true],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'student_id' => 'Student ID',
            'university' => 'University',
            'field_of_study' => 'Field of Study',
            'program' => 'Program',
            'department' => 'Department',
            'faculty' => 'Faculty / School',
            'academic_level' => 'Academic Level',
            'skills' => 'Skills',
            'gpa' => 'GPA',
            'cv' => 'CV',
            'profile_photo' => 'Profile Photo',
            'student_id' => 'Registration Number',
            'id_document_path' => 'Student ID Document',
            'id_verification_status' => 'ID Verification Status',
            'id_rejection_reason' => 'Rejection Reason',
            'personal_statement' => 'Personal Statement',
            'graduation_year' => 'Graduation Year',
            'preferred_industry' => 'Preferred Industry',
            'preferred_work_mode' => 'Preferred Work Mode',
            'preferred_locations' => 'Preferred Locations',
            'linkedin_url' => 'LinkedIn',
            'github_url' => 'GitHub',
            'portfolio_url' => 'Portfolio',
        ];
    }

    public static function getWorkModeOptions(): array
    {
        return [
            '' => 'Select work mode…',
            'remote' => 'Remote',
            'hybrid' => 'Hybrid',
            'onsite' => 'On-site',
            'flexible' => 'Flexible',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getPhotoUrl(string $size = 'md'): ?string
    {
        return (new \common\services\ProfileImageService())->studentPhotoUrl($this, $size);
    }

    public function hasProfilePhoto(): bool
    {
        return $this->getPhotoUrl() !== null;
    }

    public function hasIdDocument(): bool
    {
        return (new \common\services\StudentIdDocumentService())->hasDocument($this);
    }

    public function isIdVerified(): bool
    {
        return $this->id_verification_status === self::ID_VERIFICATION_APPROVED;
    }

    public static function getIdVerificationStatusOptions(): array
    {
        return [
            self::ID_VERIFICATION_NONE => 'Not submitted',
            self::ID_VERIFICATION_PENDING => 'Manual review required',
            self::ID_VERIFICATION_APPROVED => 'Verified',
            self::ID_VERIFICATION_REJECTED => 'Verification failed',
        ];
    }

    public function getIdVerificationLabel(): string
    {
        if ($this->id_fraud_flag) {
            return 'Manual review required';
        }

        if ($this->isIdVerified() && $this->id_verification_method === self::ID_METHOD_AUTO) {
            return 'Profile verified';
        }

        if ($this->id_verification_status === self::ID_VERIFICATION_PENDING) {
            return 'Manual review required';
        }

        if ($this->id_verification_status === self::ID_VERIFICATION_REJECTED) {
            return 'Verification failed';
        }

        return self::getIdVerificationStatusOptions()[$this->id_verification_status]
            ?? ucfirst((string) $this->id_verification_status);
    }

    public function isIdAutoVerified(): bool
    {
        return $this->isIdVerified() && $this->id_verification_method === self::ID_METHOD_AUTO;
    }

    public function getIdUploadedAtFormatted(): ?string
    {
        return $this->id_uploaded_at
            ? Yii::$app->formatter->asDatetime((int) $this->id_uploaded_at, 'medium')
            : null;
    }

    public function getIdVerifiedAtFormatted(): ?string
    {
        return $this->id_verified_at
            ? Yii::$app->formatter->asDatetime((int) $this->id_verified_at, 'medium')
            : null;
    }

    /**
     * Returns the student row for a user, creating a placeholder if missing (student role only).
     */
    public static function findOrCreateForUserId(int $userId): ?self
    {
        $student = static::findOne(['user_id' => $userId]);
        if ($student !== null) {
            return $student;
        }

        $user = User::findOne($userId);
        if ($user === null || $user->role !== 'student') {
            return null;
        }

        $student = new static([
            'user_id' => $userId,
            'student_id' => '',
            'university' => '',
            'field_of_study' => '',
            'id_verification_status' => self::ID_VERIFICATION_NONE,
            'id_verification_method' => self::ID_METHOD_NONE,
            'id_fraud_flag' => false,
        ]);
        $student->scenario = self::SCENARIO_PROFILE;
        $student->save(false);

        return $student;
    }

    /**
     * Check eligibility via centralized service (backend-enforced).
     */
    public function canApplyToPosition($position): bool
    {
        return Yii::$app->eligibility->canApply($this, $position);
    }

    /**
     * Full eligibility evaluation with reasons and match score.
     */
    public function getEligibilityForPosition($position): \common\services\EligibilityResult
    {
        return Yii::$app->eligibility->evaluate($this, $position);
    }

    public static function getAcademicLevelOptions(): array
    {
        return [
            '' => 'Select level…',
            'undergraduate' => 'Undergraduate',
            'graduate' => 'Graduate',
            'postgraduate' => 'Postgraduate',
            'diploma' => 'Diploma',
        ];
    }
}
