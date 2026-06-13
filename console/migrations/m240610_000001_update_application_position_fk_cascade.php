<?php

use yii\db\Migration;

/**
 * Updates foreign key for table `application` to ensure ON DELETE CASCADE for position_id.
 */
class m240610_000001_update_application_position_fk_cascade extends Migration
{
    public function safeUp()
    {
        // Drop the existing foreign key if it exists
        $this->dropForeignKey('fk-application-position_id', '{{%application}}');
        // Add the foreign key with ON DELETE CASCADE
        $this->addForeignKey(
            'fk-application-position_id',
            '{{%application}}',
            'position_id',
            '{{%position}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        // Drop the CASCADE foreign key
        $this->dropForeignKey('fk-application-position_id', '{{%application}}');
        // Optionally, you can re-add the original foreign key here if needed (defaulting to RESTRICT)
        $this->addForeignKey(
            'fk-application-position_id',
            '{{%application}}',
            'position_id',
            '{{%position}}',
            'id',
            'RESTRICT',
            'RESTRICT'
        );
    }
}