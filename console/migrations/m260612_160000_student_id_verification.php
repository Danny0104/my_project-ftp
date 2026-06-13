<?php

use yii\db\Migration;

/**
 * Student university ID verification document and workflow fields.
 */
class m260612_160000_student_id_verification extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%student}}', true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['id_document_path'])) {
            $this->addColumn('{{%student}}', 'id_document_path', $this->string(500)->null()->after('profile_photo'));
        }

        if (!isset($schema->columns['id_verification_status'])) {
            $this->addColumn(
                '{{%student}}',
                'id_verification_status',
                $this->string(20)->notNull()->defaultValue('none')->after('id_document_path')
            );
            $this->createIndex('idx-student-id-verification', '{{%student}}', 'id_verification_status');
        }

        if (!isset($schema->columns['id_verified_at'])) {
            $this->addColumn('{{%student}}', 'id_verified_at', $this->integer()->null()->after('id_verification_status'));
        }

        if (!isset($schema->columns['id_verified_by'])) {
            $this->addColumn('{{%student}}', 'id_verified_by', $this->integer()->null()->after('id_verified_at'));
        }

        if (!isset($schema->columns['id_rejection_reason'])) {
            $this->addColumn('{{%student}}', 'id_rejection_reason', $this->string(500)->null()->after('id_verified_by'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%student}}', true);
        if ($schema === null) {
            return;
        }

        if (isset($schema->columns['id_rejection_reason'])) {
            $this->dropColumn('{{%student}}', 'id_rejection_reason');
        }
        if (isset($schema->columns['id_verified_by'])) {
            $this->dropColumn('{{%student}}', 'id_verified_by');
        }
        if (isset($schema->columns['id_verified_at'])) {
            $this->dropColumn('{{%student}}', 'id_verified_at');
        }
        if (isset($schema->columns['id_verification_status'])) {
            $this->dropIndex('idx-student-id-verification', '{{%student}}');
            $this->dropColumn('{{%student}}', 'id_verification_status');
        }
        if (isset($schema->columns['id_document_path'])) {
            $this->dropColumn('{{%student}}', 'id_document_path');
        }
    }
}
