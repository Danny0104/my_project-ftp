<?php

use yii\db\Migration;

class m260609_120000_add_oauth_profile_completed_to_user_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn(
            '{{%user}}',
            'oauth_profile_completed',
            $this->boolean()->notNull()->defaultValue(1)->after('role')
        );
    }

    public function safeDown()
    {
        $this->dropColumn('{{%user}}', 'oauth_profile_completed');
    }
}
