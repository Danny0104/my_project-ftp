<?php

use yii\db\Migration;

class m250913_143025_add_missing_position_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add field_of_study column if it doesn't exist
        if (!$this->db->getTableSchema('{{%position}}')->getColumn('field_of_study')) {
            $this->addColumn('{{%position}}', 'field_of_study', $this->string(255)->after('description'));
        }
        
        // Add skills_required column if it doesn't exist
        if (!$this->db->getTableSchema('{{%position}}')->getColumn('skills_required')) {
            $this->addColumn('{{%position}}', 'skills_required', $this->text()->after('field_of_study'));
        }
        
        // Add duration column if it doesn't exist
        if (!$this->db->getTableSchema('{{%position}}')->getColumn('duration')) {
            $this->addColumn('{{%position}}', 'duration', $this->string(255)->after('skills_required'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop columns
        $this->dropColumn('{{%position}}', 'field_of_study');
        $this->dropColumn('{{%position}}', 'skills_required');
        $this->dropColumn('{{%position}}', 'duration');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250913_143025_add_missing_position_fields cannot be reverted.\n";

        return false;
    }
    */
}
