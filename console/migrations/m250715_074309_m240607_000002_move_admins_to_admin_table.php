<?php

use yii\db\Migration;

class m250715_074309_m240607_000002_move_admins_to_admin_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Insert all admin users from user table to admin table
        $this->execute("INSERT INTO {{%admin}} (username, email, password_hash, auth_key, status, created_at, updated_at)
            SELECT username, email, password_hash, auth_key, status, created_at, updated_at FROM {{%user}} WHERE role = 'admin'");
        // Optionally, delete them from user table
        $this->delete('{{%user}}', ['role' => 'admin']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Move admins back to user table (if needed)
        $this->execute("INSERT INTO {{%user}} (username, email, password_hash, auth_key, status, created_at, updated_at, role)
            SELECT username, email, password_hash, auth_key, status, created_at, updated_at, 'admin' FROM {{%admin}}");
        $this->delete('{{%admin}}');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250715_074309_m240607_000002_move_admins_to_admin_table cannot be reverted.\n";

        return false;
    }
    */
}
