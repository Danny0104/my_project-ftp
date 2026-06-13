<?php

use yii\db\Migration;

/**
 * Organization verification workflow for admin approvals.
 */
class m260610_150000_org_verification extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->getTableSchema('{{%organization}}', true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['verification_status'])) {
            $this->addColumn(
                '{{%organization}}',
                'verification_status',
                $this->string(20)->notNull()->defaultValue('pending')
            );
            $this->createIndex('idx-organization-verification', '{{%organization}}', 'verification_status');
            $this->update('{{%organization}}', ['verification_status' => 'approved']);
        }
    }

    public function safeDown()
    {
        $schema = $this->db->getTableSchema('{{%organization}}', true);
        if ($schema !== null && isset($schema->columns['verification_status'])) {
            $this->dropIndex('idx-organization-verification', '{{%organization}}');
            $this->dropColumn('{{%organization}}', 'verification_status');
        }
    }
}
