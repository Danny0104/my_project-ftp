<?php

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $student_id
 * @property int|null $application_id
 * @property int $author_user_id
 * @property string $note
 */
class OrgCandidateNote extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%org_candidate_note}}';
    }

    public function behaviors()
    {
        return [TimestampBehavior::class];
    }

    public function rules()
    {
        return [
            [['organization_id', 'student_id', 'author_user_id', 'note'], 'required'],
            [['organization_id', 'student_id', 'application_id', 'author_user_id', 'created_at', 'updated_at'], 'integer'],
            [['note'], 'string'],
        ];
    }

    public function getAuthor()
    {
        return $this->hasOne(User::class, ['id' => 'author_user_id']);
    }
}
