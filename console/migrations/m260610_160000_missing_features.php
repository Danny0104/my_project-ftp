<?php

use yii\db\Migration;

class m260610_160000_missing_features extends Migration
{
    public function safeUp()
    {
        $orgSchema = $this->db->getTableSchema('{{%organization}}', true);
        if ($orgSchema !== null && !isset($orgSchema->columns['logo'])) {
            $this->addColumn('{{%organization}}', 'logo', $this->string(500)->null());
        }

        $adminSchema = $this->db->getTableSchema('{{%admin}}', true);
        if ($adminSchema !== null && !isset($adminSchema->columns['admin_role'])) {
            $this->addColumn('{{%admin}}', 'admin_role', $this->string(30)->notNull()->defaultValue('super_admin'));
            $this->createIndex('idx-admin-admin_role', '{{%admin}}', 'admin_role');
        }

        $cpSchema = $this->db->getTableSchema('{{%chat_participant}}', true);
        if ($cpSchema !== null && !isset($cpSchema->columns['is_archived'])) {
            $this->addColumn('{{%chat_participant}}', 'is_archived', $this->smallInteger()->notNull()->defaultValue(0));
        }

        if ($this->db->getTableSchema('{{%email_queue}}', true) === null) {
            $this->createTable('{{%email_queue}}', [
                'id' => $this->primaryKey(),
                'to_email' => $this->string(255)->notNull(),
                'subject' => $this->string(255)->notNull(),
                'body_html' => $this->text()->notNull(),
                'body_text' => $this->text()->null(),
                'status' => $this->string(20)->notNull()->defaultValue('pending'),
                'attempts' => $this->smallInteger()->notNull()->defaultValue(0),
                'related_type' => $this->string(50)->null(),
                'related_id' => $this->integer()->null(),
                'created_at' => $this->integer()->notNull(),
                'sent_at' => $this->integer()->null(),
            ]);
            $this->createIndex('idx-email_queue-status', '{{%email_queue}}', 'status');
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema('{{%email_queue}}', true) !== null) {
            $this->dropTable('{{%email_queue}}');
        }
        $cpSchema = $this->db->getTableSchema('{{%chat_participant}}', true);
        if ($cpSchema !== null && isset($cpSchema->columns['is_archived'])) {
            $this->dropColumn('{{%chat_participant}}', 'is_archived');
        }
        $adminSchema = $this->db->getTableSchema('{{%admin}}', true);
        if ($adminSchema !== null && isset($adminSchema->columns['admin_role'])) {
            $this->dropColumn('{{%admin}}', 'admin_role');
        }
        $orgSchema = $this->db->getTableSchema('{{%organization}}', true);
        if ($orgSchema !== null && isset($orgSchema->columns['logo'])) {
            $this->dropColumn('{{%organization}}', 'logo');
        }
    }
}
