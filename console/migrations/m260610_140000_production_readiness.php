<?php

use yii\db\Migration;

/**
 * Production readiness: status history, FKs, unique indexes, bookmarks.
 */
class m260610_140000_production_readiness extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%application_status_history}}', true) === null) {
            $this->createTable('{{%application_status_history}}', [
                'id' => $this->primaryKey(),
                'application_id' => $this->integer()->notNull(),
                'from_status' => $this->string(30)->notNull(),
                'to_status' => $this->string(30)->notNull(),
                'changed_by_user_id' => $this->integer()->null(),
                'organization_id' => $this->integer()->null(),
                'created_at' => $this->integer()->notNull(),
            ]);
            $this->createIndex('idx-app_status_history-app', '{{%application_status_history}}', 'application_id');
            $this->addForeignKey(
                'fk-app_status_history-application',
                '{{%application_status_history}}',
                'application_id',
                '{{%application}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if ($this->db->getTableSchema('{{%position_bookmark}}', true) === null) {
            $this->createTable('{{%position_bookmark}}', [
                'id' => $this->primaryKey(),
                'user_id' => $this->integer()->notNull(),
                'position_id' => $this->integer()->notNull(),
                'created_at' => $this->integer()->notNull(),
            ]);
            $this->createIndex(
                'uq-position_bookmark-user-position',
                '{{%position_bookmark}}',
                ['user_id', 'position_id'],
                true
            );
            $this->addForeignKey(
                'fk-position_bookmark-user',
                '{{%position_bookmark}}',
                'user_id',
                '{{%user}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                'fk-position_bookmark-position',
                '{{%position_bookmark}}',
                'position_id',
                '{{%position}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        $interviewSchema = $this->db->getTableSchema('{{%org_interview}}', true);
        if ($interviewSchema !== null && isset($interviewSchema->columns['application_id'])) {
            $this->addFkIfMissing(
                'fk-org_interview-application',
                '{{%org_interview}}',
                'application_id',
                '{{%application}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
            $this->addFkIfMissing(
                'fk-org_interview-position',
                '{{%org_interview}}',
                'position_id',
                '{{%position}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        $programSchema = $this->db->getTableSchema('{{%org_program_student}}', true);
        if ($programSchema !== null) {
            $this->createIndexSafe(
                'uq-org_program_student-program-student',
                '{{%org_program_student}}',
                ['program_id', 'student_id'],
                true
            );
        }
    }

    public function safeDown()
    {
        if ($this->db->getTableSchema('{{%position_bookmark}}', true) !== null) {
            $this->dropTable('{{%position_bookmark}}');
        }
        if ($this->db->getTableSchema('{{%application_status_history}}', true) !== null) {
            $this->dropTable('{{%application_status_history}}');
        }
    }

    private function addFkIfMissing(
        string $name,
        string $table,
        string $column,
        string $refTable,
        string $refColumn,
        string $onDelete,
        string $onUpdate
    ): void {
        try {
            $this->addForeignKey($name, $table, $column, $refTable, $refColumn, $onDelete, $onUpdate);
        } catch (\Throwable $e) {
            // FK may already exist or data may block constraint.
        }
    }

    private function createIndexSafe(string $name, string $table, array $columns, bool $unique = false): void
    {
        try {
            $this->createIndex($name, $table, $columns, $unique);
        } catch (\Throwable $e) {
            // Index may already exist.
        }
    }
}
