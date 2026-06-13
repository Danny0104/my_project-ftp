<?php

use yii\db\Migration;

/**
 * Separates notification metadata from live chat (type, category, priority, archive, conversation link).
 */
class m260602_100000_separate_notifications_messaging extends Migration
{
    public function safeUp()
    {
        $table = '{{%notification}}';
        $schema = $this->db->getTableSchema($table, true);
        if (!$schema) {
            return;
        }

        if (!isset($schema->columns['notification_type'])) {
            $this->addColumn($table, 'notification_type', $this->string(50)->notNull()->defaultValue('system'));
            $this->createIndex('idx-notification-type', $table, 'notification_type');
        }
        if (!isset($schema->columns['category'])) {
            $this->addColumn($table, 'category', $this->string(30)->notNull()->defaultValue('system'));
            $this->createIndex('idx-notification-category', $table, 'category');
        }
        if (!isset($schema->columns['priority'])) {
            $this->addColumn($table, 'priority', $this->string(10)->notNull()->defaultValue('normal'));
        }
        if (!isset($schema->columns['is_archived'])) {
            $this->addColumn($table, 'is_archived', $this->smallInteger()->notNull()->defaultValue(0));
            $this->createIndex('idx-notification-archived', $table, 'is_archived');
        }
        if (!isset($schema->columns['related_id'])) {
            $this->addColumn($table, 'related_id', $this->integer()->null());
        }
        if (!isset($schema->columns['conversation_id'])) {
            $this->addColumn($table, 'conversation_id', $this->integer()->null());
            $this->createIndex('idx-notification-conversation', $table, 'conversation_id');
        }
    }

    public function safeDown()
    {
        $table = '{{%notification}}';
        $schema = $this->db->getTableSchema($table, true);
        if (!$schema) {
            return;
        }

        foreach (['conversation_id', 'related_id', 'is_archived', 'priority', 'category', 'notification_type'] as $col) {
            if (isset($schema->columns[$col])) {
                $this->dropColumn($table, $col);
            }
        }
    }
}
