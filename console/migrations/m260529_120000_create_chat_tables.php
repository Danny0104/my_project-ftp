<?php

use yii\db\Migration;

/**
 * Real-time chat: conversations, participants, messages, delivery/read status.
 */
class m260529_120000_create_chat_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%chat_conversation}}', [
            'id' => $this->primaryKey(),
            'application_id' => $this->integer()->null(),
            'organization_id' => $this->integer()->notNull(),
            'student_user_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->null(),
            'last_message_id' => $this->integer()->null(),
            'last_message_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_chat_conv_app', '{{%chat_conversation}}', 'application_id');
        $this->createIndex('idx_chat_conv_org_student', '{{%chat_conversation}}', ['organization_id', 'student_user_id'], true);
        $this->addForeignKey('fk_chat_conv_org', '{{%chat_conversation}}', 'organization_id', '{{%organization}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_chat_conv_student_user', '{{%chat_conversation}}', 'student_user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%chat_participant}}', [
            'id' => $this->primaryKey(),
            'conversation_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'role' => $this->string(20)->notNull()->defaultValue('member'),
            'last_read_message_id' => $this->integer()->null(),
            'last_read_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_chat_part_user_conv', '{{%chat_participant}}', ['conversation_id', 'user_id'], true);
        $this->addForeignKey('fk_chat_part_conv', '{{%chat_participant}}', 'conversation_id', '{{%chat_conversation}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_chat_part_user', '{{%chat_participant}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%chat_message}}', [
            'id' => $this->primaryKey(),
            'conversation_id' => $this->integer()->notNull(),
            'sender_user_id' => $this->integer()->notNull(),
            'body' => $this->text()->notNull(),
            'attachment_path' => $this->string(500)->null(),
            'attachment_name' => $this->string(255)->null(),
            'attachment_mime' => $this->string(128)->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_chat_msg_conv', '{{%chat_message}}', ['conversation_id', 'id']);
        $this->addForeignKey('fk_chat_msg_conv', '{{%chat_message}}', 'conversation_id', '{{%chat_conversation}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_chat_msg_sender', '{{%chat_message}}', 'sender_user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%chat_message_status}}', [
            'id' => $this->primaryKey(),
            'message_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'delivered_at' => $this->integer()->null(),
            'read_at' => $this->integer()->null(),
        ]);
        $this->createIndex('idx_chat_msg_status_unique', '{{%chat_message_status}}', ['message_id', 'user_id'], true);
        $this->addForeignKey('fk_chat_msg_status_msg', '{{%chat_message_status}}', 'message_id', '{{%chat_message}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_chat_msg_status_user', '{{%chat_message_status}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%chat_presence}}', [
            'user_id' => $this->integer()->notNull(),
            'is_online' => $this->smallInteger()->notNull()->defaultValue(0),
            'last_seen_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->addPrimaryKey('pk_chat_presence', '{{%chat_presence}}', 'user_id');
        $this->addForeignKey('fk_chat_presence_user', '{{%chat_presence}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%chat_typing}}', [
            'conversation_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'expires_at' => $this->integer()->notNull(),
        ]);
        $this->addPrimaryKey('pk_chat_typing', '{{%chat_typing}}', ['conversation_id', 'user_id']);
    }

    public function safeDown()
    {
        $this->dropTable('{{%chat_typing}}');
        $this->dropTable('{{%chat_presence}}');
        $this->dropTable('{{%chat_message_status}}');
        $this->dropTable('{{%chat_message}}');
        $this->dropTable('{{%chat_participant}}');
        $this->dropTable('{{%chat_conversation}}');
    }
}
