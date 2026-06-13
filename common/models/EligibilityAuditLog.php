<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $student_id
 * @property int $position_id
 * @property bool $eligible
 * @property int $match_score
 * @property string|null $reasons_json
 * @property string $action
 * @property int $created_at
 */
class EligibilityAuditLog extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%eligibility_audit_log}}';
    }

    public function rules()
    {
        return [
            [['user_id', 'position_id', 'eligible', 'created_at'], 'required'],
            [['user_id', 'student_id', 'position_id', 'match_score', 'created_at'], 'integer'],
            [['eligible'], 'boolean'],
            [['reasons_json'], 'string'],
            [['action'], 'string', 'max' => 50],
        ];
    }

    public static function record(int $userId, ?int $studentId, int $positionId, bool $eligible, int $score, array $reasons, string $action = 'check'): void
    {
        $log = new static([
            'user_id' => $userId,
            'student_id' => $studentId,
            'position_id' => $positionId,
            'eligible' => $eligible,
            'match_score' => $score,
            'reasons_json' => json_encode($reasons, JSON_UNESCAPED_UNICODE),
            'action' => $action,
            'created_at' => time(),
        ]);
        $log->save(false);
    }
}
