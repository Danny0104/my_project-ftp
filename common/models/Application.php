<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "application".
 *
 * @property int $id
 * @property int $user_id
 * @property int $student_id
 * @property int $position_id
 * @property string $status
 * @property int $created_at
 * @property int $updated_at
 * @property string|null $feedback
 * @property string|null $cover_letter
 * @property string|null $application_answers
 * @property string|null $resume_url
 *
 * @property User $user
 * @property Student $student
 * @property Position $position
 */
class Application extends ActiveRecord
{
    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_ORG_APPROVED = 'org_approved';
    const STATUS_UNIVERSITY_APPROVED = 'university_approved';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_WITHDRAWN = 'withdrawn';
    const STATUS_COMPLETED = 'completed';

    const SCENARIO_APPLY = 'apply';

    public static function tableName()
    {
        return 'application';
    }

    public function behaviors()
    {
        return [
            \yii\behaviors\TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['user_id', 'student_id', 'position_id', 'status'], 'required'],
            [['user_id', 'student_id', 'position_id', 'created_at', 'updated_at'], 'integer'],
            [['feedback', 'cover_letter', 'application_answers'], 'string'],
            [['status'], 'string', 'max' => 30],
            [['resume_url'], 'string', 'max' => 500],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING,
                self::STATUS_UNDER_REVIEW,
                self::STATUS_ORG_APPROVED,
                self::STATUS_UNIVERSITY_APPROVED,
                self::STATUS_APPROVED,
                self::STATUS_REJECTED,
                self::STATUS_WITHDRAWN,
                self::STATUS_COMPLETED,
            ]],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['student_id'], 'exist', 'skipOnError' => true, 'targetClass' => Student::class, 'targetAttribute' => ['student_id' => 'id']],
            [['position_id'], 'exist', 'skipOnError' => true, 'targetClass' => Position::class, 'targetAttribute' => ['position_id' => 'id']],
            [['position_id'], 'validateEligibility', 'on' => self::SCENARIO_APPLY],
            [['user_id', 'position_id'], 'unique', 'targetAttribute' => ['user_id', 'position_id'],
                'filter' => function ($query) {
                    $query->andWhere(['not in', 'status', [self::STATUS_WITHDRAWN, self::STATUS_REJECTED]]);
                },
                'message' => 'You have already applied for this internship.',
                'on' => self::SCENARIO_APPLY,
            ],
            [['student_id', 'position_id'], 'unique', 'targetAttribute' => ['student_id', 'position_id'],
                'filter' => function ($query) {
                    $query->andWhere(['not in', 'status', [self::STATUS_WITHDRAWN, self::STATUS_REJECTED]]);
                },
                'message' => 'You have already applied for this internship.',
                'on' => self::SCENARIO_APPLY,
            ],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_APPLY] = ['user_id', 'student_id', 'position_id', 'status', 'cover_letter', 'application_answers', 'resume_url'];
        return $scenarios;
    }

    /**
     * Backend eligibility validation — cannot be bypassed via direct POST.
     */
    public function validateEligibility($attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }
        $student = $this->student ?? Student::findOne($this->student_id);
        $position = $this->position ?? Position::findOne($this->position_id);
        if (!$student || !$position) {
            return;
        }
        $result = Yii::$app->eligibility->evaluate($student, $position, 'apply');
        if (!$result->eligible) {
            $this->addError($attribute, $result->getPrimaryMessage());
        }
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'student_id' => 'Student ID',
            'position_id' => 'Position ID',
            'status' => 'Status',
            'feedback' => 'Feedback',
            'cover_letter' => 'Cover Letter',
            'application_answers' => 'Application Answers',
            'resume_url' => 'Resume URL',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getStudent()
    {
        return $this->hasOne(Student::class, ['id' => 'student_id']);
    }

    public function getPosition()
    {
        return $this->hasOne(Position::class, ['id' => 'position_id']);
    }

    public function getStatusHistory()
    {
        return $this->hasMany(ApplicationStatusHistory::class, ['application_id' => 'id'])
            ->orderBy(['created_at' => SORT_ASC]);
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return \common\services\ApplicationWorkflowService::canTransition((string) $this->status, $newStatus);
    }

    /**
     * Get status options
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_ORG_APPROVED => 'Approved by Organization',
            self::STATUS_UNIVERSITY_APPROVED => 'Approved by University',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_WITHDRAWN => 'Withdrawn',
            self::STATUS_COMPLETED => 'Completed',
        ];
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass()
    {
        switch ($this->status) {
            case self::STATUS_PENDING:
                return 'warning';
            case self::STATUS_UNDER_REVIEW:
                return 'info';
            case self::STATUS_ORG_APPROVED:
                return 'success';
            case self::STATUS_UNIVERSITY_APPROVED:
                return 'success';
            case self::STATUS_APPROVED:
                return 'success';
            case self::STATUS_COMPLETED:
                return 'primary';
            case self::STATUS_REJECTED:
                return 'danger';
            case self::STATUS_WITHDRAWN:
                return 'secondary';
            default:
                return 'secondary';
        }
    }

    /**
     * Check if application can be withdrawn
     */
    public function canWithdraw()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_UNDER_REVIEW]);
    }

    /**
     * Check if application can be updated by admin
     */
    public function canUpdateByAdmin()
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_UNDER_REVIEW]);
    }

    /**
     * Any application row for this user + position (including withdrawn).
     */
    public static function findForUserPosition(int $userId, int $positionId): ?self
    {
        return static::find()
            ->where(['user_id' => $userId, 'position_id' => $positionId])
            ->one();
    }

    /**
     * Active application (excludes withdrawn only).
     */
    public static function findActiveForUserPosition(int $userId, int $positionId): ?self
    {
        return static::find()
            ->where(['user_id' => $userId, 'position_id' => $positionId])
            ->andWhere(['not in', 'status', [self::STATUS_WITHDRAWN]])
            ->one();
    }

    /**
     * Statuses that allow submitting again on the same user/position row.
     */
    public static function reapplyableStatuses(): array
    {
        return [self::STATUS_WITHDRAWN, self::STATUS_REJECTED];
    }

    public function isReapplyable(): bool
    {
        return in_array($this->status, self::reapplyableStatuses(), true);
    }
} 