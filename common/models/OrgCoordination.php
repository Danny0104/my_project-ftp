<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $student_id
 * @property int|null $application_id
 * @property string|null $university_name
 * @property string|null $supervisor_name
 * @property string|null $supervisor_email
 * @property string $workflow_status
 * @property string $approval_status
 * @property string|null $progress_notes
 * @property string|null $document_path
 * @property int|null $due_at
 */
class OrgCoordination extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%org_coordination}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['organization_id', 'student_id'], 'required'],
            [['organization_id', 'student_id', 'application_id', 'due_at', 'created_at', 'updated_at'], 'integer'],
            [['progress_notes'], 'string'],
            [['university_name', 'supervisor_name', 'supervisor_email'], 'string', 'max' => 255],
            [['document_path'], 'string', 'max' => 500],
            [['workflow_status', 'approval_status'], 'string', 'max' => 50],
            [['supervisor_email'], 'email'],
        ];
    }

    public function getStudent()
    {
        return $this->hasOne(Student::class, ['id' => 'student_id']);
    }

    public function getOrganization()
    {
        return $this->hasOne(Organization::class, ['id' => 'organization_id']);
    }

    public static function workflowOptions(): array
    {
        return [
            'initiated' => 'Initiated',
            'supervisor_assigned' => 'Supervisor assigned',
            'in_progress' => 'In progress',
            'report_submitted' => 'Report submitted',
            'closed' => 'Closed',
        ];
    }

    public static function approvalOptions(): array
    {
        return [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'revision' => 'Needs revision',
        ];
    }
}
