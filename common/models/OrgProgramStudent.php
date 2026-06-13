<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $program_id
 * @property int $student_id
 * @property int|null $application_id
 * @property string $status
 * @property int $progress_percent
 * @property int|null $assigned_at
 */
class OrgProgramStudent extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%org_program_student}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['program_id', 'student_id'], 'required'],
            [['program_id', 'student_id', 'application_id', 'progress_percent', 'assigned_at', 'created_at', 'updated_at'], 'integer'],
            [['status'], 'string', 'max' => 30],
            [['progress_percent'], 'integer', 'min' => 0, 'max' => 100],
        ];
    }

    public function getProgram()
    {
        return $this->hasOne(OrgInternshipProgram::class, ['id' => 'program_id']);
    }

    public function getStudent()
    {
        return $this->hasOne(Student::class, ['id' => 'student_id']);
    }
}
