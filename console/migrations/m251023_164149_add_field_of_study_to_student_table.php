<?php

use yii\db\Migration;

/**
 * Handles adding field_of_study column to table `{{%student}}`.
 */
class m251023_164149_add_field_of_study_to_student_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add field_of_study column to student table
        $this->addColumn('{{%student}}', 'field_of_study', $this->string(255)->after('university'));
        
        // Update existing students with sample field of study data
        $this->update('{{%student}}', ['field_of_study' => 'Computer Science'], ['id' => 1]);
        $this->update('{{%student}}', ['field_of_study' => 'Marketing'], ['id' => 2]);
        $this->update('{{%student}}', ['field_of_study' => 'Statistics'], ['id' => 3]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%student}}', 'field_of_study');
    }
}
