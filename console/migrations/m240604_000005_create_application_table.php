<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%application}}`.
 */
class m240604_000005_create_application_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%application}}', [
            'id' => $this->primaryKey(),
            'student_id' => $this->integer()->notNull(),
            'position_id' => $this->integer()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'feedback' => $this->text(),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->addForeignKey('fk-application-student_id', '{{%application}}', 'student_id', '{{%student}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-application-position_id', '{{%application}}', 'position_id', '{{%position}}', 'id', 'CASCADE', 'CASCADE');
    }
    public function safeDown()
    {
        $this->dropForeignKey('fk-application-student_id', '{{%application}}');
        $this->dropForeignKey('fk-application-position_id', '{{%application}}');
        $this->dropTable('{{%application}}');
    }
} 