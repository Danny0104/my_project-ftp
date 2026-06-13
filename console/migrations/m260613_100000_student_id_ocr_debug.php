<?php

use yii\db\Migration;

/**
 * OCR debug payload: raw text, confidence, preprocessing, parser diagnostics.
 */
class m260613_100000_student_id_ocr_debug extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%student}}', true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['id_ocr_debug'])) {
            $this->addColumn('{{%student}}', 'id_ocr_debug', $this->text()->null()->after('id_ocr_confidence'));
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%student}}', true);
        if ($schema === null) {
            return;
        }

        if (isset($schema->columns['id_ocr_debug'])) {
            $this->dropColumn('{{%student}}', 'id_ocr_debug');
        }
    }
}
