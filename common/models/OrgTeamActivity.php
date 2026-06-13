<?php

namespace common\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $team_member_id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $meta_json
 * @property int $created_at
 */
class OrgTeamActivity extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%org_team_activity}}';
    }

    public function rules()
    {
        return [
            [['organization_id', 'action', 'created_at'], 'required'],
            [['organization_id', 'team_member_id', 'user_id', 'created_at'], 'integer'],
            [['meta_json'], 'string'],
            [['action'], 'string', 'max' => 100],
        ];
    }

    public static function log(int $organizationId, string $action, ?int $userId = null, array $meta = []): void
    {
        $row = new self();
        $row->organization_id = $organizationId;
        $row->user_id = $userId ?? (\Yii::$app->user->id ?? null);
        $row->action = $action;
        $row->meta_json = $meta ? json_encode($meta) : null;
        $row->created_at = time();
        $row->save(false);
    }
}
