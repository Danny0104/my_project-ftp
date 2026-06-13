<?php

use yii\db\Migration;

/**
 * Handles adding field_of_study, skills_required, and duration columns to table `{{%position}}`.
 */
class m250730_000001_add_fields_to_position_table extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%position}}', 'field_of_study', $this->string(255)->after('description'));
        $this->addColumn('{{%position}}', 'skills_required', $this->text()->after('field_of_study'));
        $this->addColumn('{{%position}}', 'duration', $this->string(50)->after('skills_required'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%position}}', 'field_of_study');
        $this->dropColumn('{{%position}}', 'skills_required');
        $this->dropColumn('{{%position}}', 'duration');
    }
} 