<?php

use yii\db\Migration;

class m260612_120000_student_profile_photo extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%student}}', true);
        if ($schema !== null && !isset($schema->columns['profile_photo'])) {
            $this->addColumn('{{%student}}', 'profile_photo', $this->string(500)->null()->after('cv'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%student}}', true);
        if ($schema !== null && isset($schema->columns['profile_photo'])) {
            $this->dropColumn('{{%student}}', 'profile_photo');
        }
    }
}
