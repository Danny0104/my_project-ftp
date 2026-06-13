<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%position}}`.
 */
class m240604_000004_create_position_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%position}}', [
            'id' => $this->primaryKey(),
            'organization_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'criteria' => $this->text(),
            'location' => $this->string(255),
            'status' => $this->string(20)->notNull()->defaultValue('open'),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->addForeignKey('fk-position-organization_id', '{{%position}}', 'organization_id', '{{%organization}}', 'id', 'CASCADE', 'CASCADE');
    }
    public function safeDown()
    {
        $this->dropForeignKey('fk-position-organization_id', '{{%position}}');
        $this->dropTable('{{%position}}');
    }
} 