<?php

use yii\db\Migration;

/**
 * Updates notification table with enhanced fields
 */
class m250730_210232_update_notification_table extends Migration
{
    public function safeUp()
    {
        // Add missing columns to notification table
        $this->addColumn('{{%notification}}', 'title', $this->string(255)->notNull()->defaultValue('Notification'));
        $this->addColumn('{{%notification}}', 'sender_type', $this->string(20)->notNull()->defaultValue('system'));
        $this->addColumn('{{%notification}}', 'sender_id', $this->integer()->notNull()->defaultValue(0));
        $this->addColumn('{{%notification}}', 'action_url', $this->string(255));
        $this->addColumn('{{%notification}}', 'action_text', $this->string(255));
        $this->addColumn('{{%notification}}', 'updated_at', $this->integer()->notNull()->defaultValue(0));
        
        // Drop the old 'type' column if it exists
        $this->dropColumn('{{%notification}}', 'type');
        
        // Add indexes for better performance
        $this->createIndex('idx-notification-sender_type', '{{%notification}}', 'sender_type');
        $this->createIndex('idx-notification-is_read', '{{%notification}}', 'is_read');
        $this->createIndex('idx-notification-created_at', '{{%notification}}', 'created_at');
    }
    
    public function safeDown()
    {
        // Remove indexes
        $this->dropIndex('idx-notification-sender_type', '{{%notification}}');
        $this->dropIndex('idx-notification-is_read', '{{%notification}}');
        $this->dropIndex('idx-notification-created_at', '{{%notification}}');
        
        // Remove added columns
        $this->dropColumn('{{%notification}}', 'title');
        $this->dropColumn('{{%notification}}', 'sender_type');
        $this->dropColumn('{{%notification}}', 'sender_id');
        $this->dropColumn('{{%notification}}', 'action_url');
        $this->dropColumn('{{%notification}}', 'action_text');
        $this->dropColumn('{{%notification}}', 'updated_at');
        
        // Add back the old 'type' column
        $this->addColumn('{{%notification}}', 'type', $this->string(50));
    }
}
