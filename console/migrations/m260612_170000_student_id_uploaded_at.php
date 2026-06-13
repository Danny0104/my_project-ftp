<?php

use yii\db\Migration;

/**
 * Timestamp for when a student ID document was last uploaded.
 */
class m260612_170000_student_id_uploaded_at extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%student}}', true);
        if ($schema === null || isset($schema->columns['id_uploaded_at'])) {
            return;
        }

        $this->addColumn('{{%student}}', 'id_uploaded_at', $this->integer()->null()->after('id_rejection_reason'));
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%student}}', true);
        if ($schema === null || !isset($schema->columns['id_uploaded_at'])) {
            return;
        }

        $this->dropColumn('{{%student}}', 'id_uploaded_at');
    }
}
