<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%student}}`.
 */
class m240604_000002_create_student_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%student}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'student_id' => $this->string(50)->notNull(),
            'university' => $this->string(255)->notNull(),
            'cv' => $this->string(255),
            'personal_statement' => $this->text(),
        ]);
        $this->addForeignKey('fk-student-user_id', '{{%student}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
    }
    public function safeDown()
    {
        $this->dropForeignKey('fk-student-user_id', '{{%student}}');
        $this->dropTable('{{%student}}');
    }
} 