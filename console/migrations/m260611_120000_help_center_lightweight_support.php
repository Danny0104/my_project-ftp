<?php

use yii\db\Migration;

/**
 * Replaces ticket-based Support Hub with lightweight Help Center support tables.
 */
class m260611_120000_help_center_lightweight_support extends Migration
{
    public function safeUp()
    {
        if ($this->db->schema->getTableSchema('{{%support_ticket_read}}', true) !== null) {
            $this->dropTable('{{%support_ticket_read}}');
        }
        if ($this->db->schema->getTableSchema('{{%support_attachment}}', true) !== null) {
            $this->dropTable('{{%support_attachment}}');
        }
        if ($this->db->schema->getTableSchema('{{%support_message}}', true) !== null) {
            $this->dropTable('{{%support_message}}');
        }
        if ($this->db->schema->getTableSchema('{{%support_ticket}}', true) !== null) {
            $this->dropTable('{{%support_ticket}}');
        }

        $this->createTable('{{%support_conversation}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'user_role' => $this->string(20)->notNull(),
            'category' => $this->string(50)->notNull(),
            'subject' => $this->string(255)->notNull(),
            'last_message_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_support_conversation_user', '{{%support_conversation}}', ['user_id', 'last_message_at']);
        $this->addForeignKey(
            'fk_support_conversation_user',
            '{{%support_conversation}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%support_message}}', [
            'id' => $this->primaryKey(),
            'conversation_id' => $this->integer()->notNull(),
            'sender_id' => $this->integer()->notNull(),
            'receiver_id' => $this->integer()->notNull()->defaultValue(0),
            'sender_role' => $this->string(20)->notNull(),
            'body' => $this->text()->notNull(),
            'is_read' => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_support_message_conversation', '{{%support_message}}', ['conversation_id', 'id']);
        $this->createIndex('idx_support_message_receiver_unread', '{{%support_message}}', ['receiver_id', 'is_read']);
        $this->addForeignKey(
            'fk_support_message_conversation',
            '{{%support_message}}',
            'conversation_id',
            '{{%support_conversation}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%support_chat_message}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'sender_id' => $this->integer()->notNull(),
            'receiver_id' => $this->integer()->notNull()->defaultValue(0),
            'sender_role' => $this->string(20)->notNull(),
            'body' => $this->text()->notNull(),
            'is_read' => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_support_chat_user', '{{%support_chat_message}}', ['user_id', 'id']);
        $this->createIndex('idx_support_chat_receiver_unread', '{{%support_chat_message}}', ['receiver_id', 'is_read']);
        $this->addForeignKey(
            'fk_support_chat_user',
            '{{%support_chat_message}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%support_admin_presence}}', [
            'admin_id' => $this->primaryKey(),
            'last_seen_at' => $this->integer()->notNull(),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%support_admin_presence}}');
        $this->dropTable('{{%support_chat_message}}');
        $this->dropTable('{{%support_message}}');
        $this->dropTable('{{%support_conversation}}');
    }
}
