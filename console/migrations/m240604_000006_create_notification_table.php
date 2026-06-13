<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%notification}}`.
 */
class m240604_000006_create_notification_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%notification}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'message' => $this->text()->notNull(),
            'sender_type' => $this->string(20)->notNull(), // admin, organization, system
            'sender_id' => $this->integer()->notNull()->defaultValue(0),
            'action_url' => $this->string(255),
            'action_text' => $this->string(255),
            'is_read' => $this->boolean()->notNull()->defaultValue(false),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        
        $this->addForeignKey('fk-notification-user_id', '{{%notification}}', 'user_id', '{{%user}}', 'id', 'CASCADE', 'CASCADE');
        
        // Add indexes for better performance
        $this->createIndex('idx-notification-user_id', '{{%notification}}', 'user_id');
        $this->createIndex('idx-notification-sender_type', '{{%notification}}', 'sender_type');
        $this->createIndex('idx-notification-is_read', '{{%notification}}', 'is_read');
        $this->createIndex('idx-notification-created_at', '{{%notification}}', 'created_at');
    }
    
    public function safeDown()
    {
        $this->dropForeignKey('fk-notification-user_id', '{{%notification}}');
        $this->dropIndex('idx-notification-user_id', '{{%notification}}');
        $this->dropIndex('idx-notification-sender_type', '{{%notification}}');
        $this->dropIndex('idx-notification-is_read', '{{%notification}}');
        $this->dropIndex('idx-notification-created_at', '{{%notification}}');
        $this->dropTable('{{%notification}}');
    }
} 