<?php

use yii\db\Migration;

/**
 * Adds password_reset_token column required for forgot/reset password flow.
 */
class m260609_210000_add_password_reset_token_to_user_table extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%user}}', true);
        if ($schema && $schema->getColumn('password_reset_token') === null) {
            $this->addColumn(
                '{{%user}}',
                'password_reset_token',
                $this->string(255)->null()->after('auth_key')
            );
            $this->createIndex(
                'idx-user-password_reset_token',
                '{{%user}}',
                'password_reset_token',
                true
            );
        }
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema('{{%user}}', true);
        if ($schema && $schema->getColumn('password_reset_token') !== null) {
            $this->dropIndex('idx-user-password_reset_token', '{{%user}}');
            $this->dropColumn('{{%user}}', 'password_reset_token');
        }
    }
}
