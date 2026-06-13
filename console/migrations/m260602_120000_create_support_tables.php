<?php

use yii\db\Migration;

/**
 * Support hub: tickets, messages, attachments, and per-user read cursor (unread counts).
 */
class m260602_120000_create_support_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%support_ticket}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(32)->notNull()->unique(),
            'created_by_user_id' => $this->integer()->notNull(),
            'created_by_role' => $this->string(20)->notNull(),
            'subject' => $this->string(255)->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('open'),
            'priority' => $this->string(10)->notNull()->defaultValue('normal'),
            'assigned_admin_id' => $this->integer()->null(),
            'last_message_id' => $this->integer()->null(),
            'last_message_at' => $this->integer()->null(),
            'closed_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_support_ticket_creator_status', '{{%support_ticket}}', ['created_by_user_id', 'status']);
        $this->createIndex('idx_support_ticket_status_priority', '{{%support_ticket}}', ['status', 'priority']);
        $this->createIndex('idx_support_ticket_last_message_at', '{{%support_ticket}}', 'last_message_at');
        $this->createIndex('idx_support_ticket_assigned_admin', '{{%support_ticket}}', 'assigned_admin_id');
        $this->addForeignKey(
            'fk_support_ticket_creator',
            '{{%support_ticket}}',
            'created_by_user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%support_message}}', [
            'id' => $this->primaryKey(),
            'ticket_id' => $this->integer()->notNull(),
            'sender_user_id' => $this->integer()->null(),
            'sender_role' => $this->string(20)->notNull(),
            'body' => $this->text()->notNull(),
            'is_internal_note' => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_support_message_ticket_id', '{{%support_message}}', ['ticket_id', 'id']);
        $this->addForeignKey(
            'fk_support_message_ticket',
            '{{%support_message}}',
            'ticket_id',
            '{{%support_ticket}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_support_message_sender_user',
            '{{%support_message}}',
            'sender_user_id',
            '{{%user}}',
            'id',
            'SET NULL',
            'CASCADE'
        );

        $this->createTable('{{%support_attachment}}', [
            'id' => $this->primaryKey(),
            'ticket_id' => $this->integer()->notNull(),
            'message_id' => $this->integer()->notNull(),
            'path' => $this->string(500)->notNull(),
            'name' => $this->string(255)->notNull(),
            'mime' => $this->string(128)->notNull(),
            'size' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_support_attachment_ticket', '{{%support_attachment}}', 'ticket_id');
        $this->createIndex('idx_support_attachment_message', '{{%support_attachment}}', 'message_id');
        $this->addForeignKey(
            'fk_support_attachment_ticket',
            '{{%support_attachment}}',
            'ticket_id',
            '{{%support_ticket}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_support_attachment_message',
            '{{%support_attachment}}',
            'message_id',
            '{{%support_message}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%support_ticket_read}}', [
            'ticket_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'last_read_message_id' => $this->integer()->null(),
            'last_read_at' => $this->integer()->null(),
        ]);
        $this->addPrimaryKey('pk_support_ticket_read', '{{%support_ticket_read}}', ['ticket_id', 'user_id']);
        $this->addForeignKey(
            'fk_support_ticket_read_ticket',
            '{{%support_ticket_read}}',
            'ticket_id',
            '{{%support_ticket}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_support_ticket_read_user',
            '{{%support_ticket_read}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%support_ticket_read}}');
        $this->dropTable('{{%support_attachment}}');
        $this->dropTable('{{%support_message}}');
        $this->dropTable('{{%support_ticket}}');
    }
}

