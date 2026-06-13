<?php

use yii\db\Migration;

/**
 * Handles the creation of table `error_log`.
 */
class m250101_000001_create_error_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('error_log', [
            'id' => $this->primaryKey(),
            'category' => $this->string(50)->notNull()->comment('Error category'),
            'severity' => $this->string(50)->notNull()->comment('Error severity level'),
            'message' => $this->string(500)->notNull()->comment('Error message'),
            'data' => $this->text()->comment('Additional error data (JSON)'),
            'user_id' => $this->integer()->comment('User ID who triggered the error'),
            'ip_address' => $this->string(45)->comment('IP address'),
            'user_agent' => $this->string(500)->comment('User agent string'),
            'created_at' => $this->integer()->notNull()->comment('Created timestamp'),
        ]);

        // Create indexes for better performance
        $this->createIndex('idx_error_log_category', 'error_log', 'category');
        $this->createIndex('idx_error_log_severity', 'error_log', 'severity');
        $this->createIndex('idx_error_log_created_at', 'error_log', 'created_at');
        $this->createIndex('idx_error_log_user_id', 'error_log', 'user_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('error_log');
    }
}
