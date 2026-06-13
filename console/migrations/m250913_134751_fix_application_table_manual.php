<?php

use yii\db\Migration;

class m250913_134751_fix_application_table_manual extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m250913_134751_fix_application_table_manual cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250913_134751_fix_application_table_manual cannot be reverted.\n";

        return false;
    }
    */
}
