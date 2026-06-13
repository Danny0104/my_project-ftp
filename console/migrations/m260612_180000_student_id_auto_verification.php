<?php

use yii\db\Migration;

/**
 * Auto-verification: OCR data, scoring, fraud flags, document hash.
 */
class m260612_180000_student_id_auto_verification extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%student}}', true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['id_ocr_data'])) {
            $this->addColumn('{{%student}}', 'id_ocr_data', $this->text()->null()->after('id_uploaded_at'));
        }

        if (!isset($schema->columns['id_ocr_confidence'])) {
            $this->addColumn('{{%student}}', 'id_ocr_confidence', $this->smallInteger()->null()->after('id_ocr_data'));
        }

        if (!isset($schema->columns['id_verification_score'])) {
            $this->addColumn('{{%student}}', 'id_verification_score', $this->smallInteger()->null()->after('id_ocr_confidence'));
        }

        if (!isset($schema->columns['id_verification_method'])) {
            $this->addColumn(
                '{{%student}}',
                'id_verification_method',
                $this->string(20)->notNull()->defaultValue('none')->after('id_verification_score')
            );
        }

        if (!isset($schema->columns['id_verification_checks'])) {
            $this->addColumn('{{%student}}', 'id_verification_checks', $this->text()->null()->after('id_verification_method'));
        }

        if (!isset($schema->columns['id_document_hash'])) {
            $this->addColumn('{{%student}}', 'id_document_hash', $this->string(64)->null()->after('id_verification_checks'));
            $this->createIndex('idx-student-id-document-hash', '{{%student}}', 'id_document_hash');
        }

        if (!isset($schema->columns['id_fraud_flag'])) {
            $this->addColumn('{{%student}}', 'id_fraud_flag', $this->boolean()->notNull()->defaultValue(false)->after('id_document_hash'));
        }

        if (!isset($schema->columns['id_fraud_reason'])) {
            $this->addColumn('{{%student}}', 'id_fraud_reason', $this->string(500)->null()->after('id_fraud_flag'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%student}}', true);
        if ($schema === null) {
            return;
        }

        if (isset($schema->columns['id_fraud_reason'])) {
            $this->dropColumn('{{%student}}', 'id_fraud_reason');
        }
        if (isset($schema->columns['id_fraud_flag'])) {
            $this->dropColumn('{{%student}}', 'id_fraud_flag');
        }
        if (isset($schema->columns['id_document_hash'])) {
            $this->dropIndex('idx-student-id-document-hash', '{{%student}}');
            $this->dropColumn('{{%student}}', 'id_document_hash');
        }
        if (isset($schema->columns['id_verification_checks'])) {
            $this->dropColumn('{{%student}}', 'id_verification_checks');
        }
        if (isset($schema->columns['id_verification_method'])) {
            $this->dropColumn('{{%student}}', 'id_verification_method');
        }
        if (isset($schema->columns['id_verification_score'])) {
            $this->dropColumn('{{%student}}', 'id_verification_score');
        }
        if (isset($schema->columns['id_ocr_confidence'])) {
            $this->dropColumn('{{%student}}', 'id_ocr_confidence');
        }
        if (isset($schema->columns['id_ocr_data'])) {
            $this->dropColumn('{{%student}}', 'id_ocr_data');
        }
    }
}
