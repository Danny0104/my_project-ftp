<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "position".
 *
 * @property int $id
 * @property int $organization_id
 * @property string $title
 * @property string|null $description
 * @property string|null $field_of_study
 * @property string|null $category
 * @property string|null $academic_level_required
 * @property float|null $min_gpa
 * @property int|null $application_deadline
 * @property string|null $skills_required
 * @property string|null $duration
 * @property string|null $criteria
 * @property string|null $application_questions
 * @property string|null $location
 * @property string $status
 * @property int $created_at
 *
 * @property Organization $organization
 * @property PositionAllowedField[] $positionAllowedFields
 * @property FieldOfStudy[] $allowedFields
 */
class Position extends \yii\db\ActiveRecord
{
    public const STATUS_DRAFT = 'Draft';
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_PAUSED = 'Paused';
    public const STATUS_CLOSED = 'Closed';

    /** @var int[] Virtual attribute for multi-select allowed fields in forms */
    public $allowedFieldIds = [];

    public static function tableName()
    {
        return 'position';
    }

    public function rules()
    {
        return [
            [['organization_id', 'title', 'status', 'created_at'], 'required'],
            [['organization_id', 'created_at', 'application_deadline'], 'integer'],
            [['description', 'criteria', 'skills_required', 'application_questions'], 'string'],
            [['min_gpa'], 'number', 'min' => 0, 'max' => 4],
            [['title', 'location', 'field_of_study', 'duration', 'category'], 'string', 'max' => 255],
            [['academic_level_required'], 'string', 'max'  => 50],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [
                self::STATUS_DRAFT,
                self::STATUS_ACTIVE,
                self::STATUS_PAUSED,
                self::STATUS_CLOSED,
            ]],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['created_at'], 'default', 'value' => time()],
            [['allowedFieldIds'], 'safe'],
            [['allowedFieldIds'], 'validateAllowedFieldIds'],
            [['organization_id'], 'exist', 'skipOnError' => true, 'targetClass' => Organization::class, 'targetAttribute' => ['organization_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'organization_id' => 'Organization ID',
            'title' => 'Title',
            'description' => 'Description',
            'field_of_study' => 'Field of Study',
            'category' => 'Internship Category',
            'academic_level_required' => 'Required Academic Level',
            'min_gpa' => 'Minimum GPA',
            'application_deadline' => 'Application Deadline',
            'skills_required' => 'Skills Required',
            'duration' => 'Duration',
            'criteria' => 'Criteria',
            'application_questions' => 'Application Questions',
            'location' => 'Location',
            'status' => 'Status',
            'created_at' => 'Created At',
            'allowedFieldIds' => 'Allowed Fields of Study',
        ];
    }

    public function validateAllowedFieldIds(string $attribute): void
    {
        $ids = is_array($this->allowedFieldIds) ? array_filter(array_map('intval', $this->allowedFieldIds)) : [];
        if (empty($ids)) {
            $this->addError($attribute, 'Please select at least one academic field.');
        }
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            if (!empty($this->application_deadline) && !is_numeric($this->application_deadline)) {
                $ts = strtotime((string) $this->application_deadline);
                $this->application_deadline = $ts ?: null;
            }
            if (is_string($this->allowedFieldIds)) {
                $this->allowedFieldIds = array_filter(array_map('intval', explode(',', $this->allowedFieldIds)));
            }
            return true;
        }
        return false;
    }

    public function afterFind()
    {
        parent::afterFind();
        $this->allowedFieldIds = PositionAllowedField::find()
            ->select('field_of_study_id')
            ->where(['position_id' => $this->id])
            ->column();
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if (is_array($this->allowedFieldIds)) {
            PositionAllowedField::sync((int) $this->id, $this->allowedFieldIds);
        } elseif (!$insert && !empty($this->field_of_study)) {
            $this->syncAllowedFieldsFromLegacyText();
        }
    }

    public function syncAllowedFieldsFromLegacyText(): void
    {
        $ids = [];
        foreach (array_map('trim', explode(',', (string) $this->field_of_study)) as $part) {
            if ($part === '') {
                continue;
            }
            $field = FieldOfStudy::resolve($part);
            if ($field) {
                $ids[] = (int) $field->id;
            }
        }
        if (!empty($ids)) {
            PositionAllowedField::sync((int) $this->id, $ids);
        }
    }

    public function getOrganization()
    {
        return $this->hasOne(Organization::class, ['id' => 'organization_id']);
    }

    public function getApplications()
    {
        return $this->hasMany(Application::class, ['position_id' => 'id']);
    }

    public function getPositionAllowedFields()
    {
        return $this->hasMany(PositionAllowedField::class, ['position_id' => 'id']);
    }

    public function getAllowedFields()
    {
        return $this->hasMany(FieldOfStudy::class, ['id' => 'field_of_study_id'])
            ->via('positionAllowedFields');
    }

    public function isAcceptingApplications(): bool
    {
        return (new \common\services\PublicPositionService())->isAcceptingApplications($this);
    }

    /**
     * @return array<int, array{id: string, type: string, label: string, required: bool, placeholder: string, options: string[]}>
     */
    public function getApplicationQuestions(): array
    {
        return (new \common\services\ApplicationQuestionService())->getQuestions($this);
    }

    public function hasApplicationQuestions(): bool
    {
        return $this->getApplicationQuestions() !== [];
    }

    public function getEffectiveDeadlineTimestamp(): int
    {
        return (new \common\services\PublicPositionService())->effectiveDeadlineTimestamp($this);
    }

    /** @return array<string, string> */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    /**
     * Primary status button metadata for organization opportunity cards.
     *
     * @return array{label:string,next:string,icon:string,confirm:?string,primary:bool}
     */
    public static function getStatusToggleMeta(string $status): array
    {
        switch ($status) {
            case self::STATUS_DRAFT:
                return ['label' => 'Go Live', 'next' => self::STATUS_ACTIVE, 'icon' => 'fa-play', 'confirm' => null, 'primary' => true];
            case self::STATUS_ACTIVE:
                return ['label' => 'Running', 'next' => self::STATUS_PAUSED, 'icon' => 'fa-pause', 'confirm' => null, 'primary' => true];
            case self::STATUS_PAUSED:
                return ['label' => 'Resume', 'next' => self::STATUS_ACTIVE, 'icon' => 'fa-play', 'confirm' => null, 'primary' => true];
            case self::STATUS_CLOSED:
                return ['label' => 'Reopen', 'next' => self::STATUS_ACTIVE, 'icon' => 'fa-rotate-right', 'confirm' => 'Reopen this internship for applications?', 'primary' => false];
            default:
                return ['label' => 'Activate', 'next' => self::STATUS_ACTIVE, 'icon' => 'fa-play', 'confirm' => null, 'primary' => true];
        }
    }

    public static function isValidStatus(string $status): bool
    {
        return array_key_exists($status, self::getStatusOptions());
    }

    public static function normalizeStatus(string $status): string
    {
        $map = [
            'draft' => self::STATUS_DRAFT,
            'active' => self::STATUS_ACTIVE,
            'paused' => self::STATUS_PAUSED,
            'closed' => self::STATUS_CLOSED,
            'open' => self::STATUS_ACTIVE,
        ];
        $key = strtolower(trim($status));
        return $map[$key] ?? self::STATUS_DRAFT;
    }
}
