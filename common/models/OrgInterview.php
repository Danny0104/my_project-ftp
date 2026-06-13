<?php

namespace common\models;

use common\services\OrgInterviewScheduleService;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $application_id
 * @property int $student_id
 * @property int|null $position_id
 * @property string $title
 * @property string $interview_stage
 * @property int $scheduled_at
 * @property int $duration_minutes
 * @property string|null $meeting_link
 * @property string|null $location
 * @property string $status
 * @property int|null $evaluation_score
 * @property string|null $evaluation_notes
 * @property string|null $interviewer_name
 */
class OrgInterview extends ActiveRecord
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    public const SCENARIO_SCHEDULE = 'schedule';

    public static function tableName()
    {
        return '{{%org_interview}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_SCHEDULE] = $scenarios[self::SCENARIO_DEFAULT];

        return $scenarios;
    }

    public function rules()
    {
        return [
            [['organization_id', 'student_id', 'title', 'scheduled_at'], 'required'],
            [['organization_id', 'application_id', 'student_id', 'position_id', 'scheduled_at', 'duration_minutes', 'evaluation_score', 'reminder_sent', 'created_at', 'updated_at'], 'integer'],
            [['evaluation_notes'], 'string'],
            [['title', 'location', 'interviewer_name'], 'string', 'max' => 255],
            [['interview_stage'], 'string', 'max' => 50],
            [['interview_stage'], 'default', 'value' => OrgInterviewScheduleService::STAGE_DEFAULT],
            [['meeting_link'], 'string', 'max' => 500],
            [['status'], 'string', 'max' => 30],
            [['status'], 'in', 'range' => [self::STATUS_SCHEDULED, self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_NO_SHOW]],
            [['evaluation_score'], 'integer', 'min' => 0, 'max' => 100],
            [['duration_minutes'], 'integer', 'min' => 15, 'max' => 240],
            [['application_id'], 'required', 'on' => self::SCENARIO_SCHEDULE],
            [['application_id', 'interview_stage'], 'validateUniqueInterview', 'on' => self::SCENARIO_SCHEDULE],
        ];
    }

    public function validateUniqueInterview(): void
    {
        if (!$this->application_id) {
            return;
        }

        $existing = OrgInterviewScheduleService::findExisting(
            (int) $this->organization_id,
            (int) $this->student_id,
            $this->position_id ? (int) $this->position_id : null,
            (int) $this->application_id,
            (string) $this->interview_stage
        );

        if ($existing && (int) $existing->id !== (int) $this->id) {
            $this->addError('application_id', 'An interview already exists for this application and stage.');
        }
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $this->interview_stage = OrgInterviewScheduleService::normalizeStage($this->interview_stage);

        return true;
    }

    public function getOrganization()
    {
        return $this->hasOne(Organization::class, ['id' => 'organization_id']);
    }

    public function getStudent()
    {
        return $this->hasOne(Student::class, ['id' => 'student_id']);
    }

    public function getPosition()
    {
        return $this->hasOne(Position::class, ['id' => 'position_id']);
    }

    public function getApplication()
    {
        return $this->hasOne(Application::class, ['id' => 'application_id']);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_NO_SHOW => 'No show',
        ];
    }
}
