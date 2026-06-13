<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%organization}}`.
 */
class m240604_000003_create_organization_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%organization}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'location' => $this->string(255),
            'website' => $this->string(255),
        ]);
        $this->addForeignKey('fk-organization-user_id', '{{%organization}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
    }
    public function safeDown()
    {
        $this->dropForeignKey('fk-organization-user_id', '{{%organization}}');
        $this->dropTable('{{%organization}}');
    }
} 