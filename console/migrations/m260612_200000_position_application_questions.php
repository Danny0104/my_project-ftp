<?php

use yii\db\Migration;

/**
 * Organization-defined application questions on positions; student answers on applications.
 */
class m260612_200000_position_application_questions extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%position}}', 'application_questions', $this->text()->null()->after('criteria'));
        $this->addColumn('{{%application}}', 'application_answers', $this->text()->null()->after('cover_letter'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%application}}', 'application_answers');
        $this->dropColumn('{{%position}}', 'application_questions');
    }
}
