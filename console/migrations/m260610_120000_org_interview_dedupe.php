<?php

use yii\db\Migration;

/**
 * Deduplicate org interviews and enforce one interview per application stage.
 */
class m260610_120000_org_interview_dedupe extends Migration
{
    public function safeUp()
    {
        $table = '{{%org_interview}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['interview_stage'])) {
            $this->addColumn($table, 'interview_stage', $this->string(50)->notNull()->defaultValue('interview'));
        }

        // Backfill application + position links from matching applications.
        $this->execute("
            UPDATE {$table} oi
            INNER JOIN {{%application}} a ON a.student_id = oi.student_id
            INNER JOIN {{%position}} p ON p.id = a.position_id AND p.organization_id = oi.organization_id
            SET
                oi.application_id = a.id,
                oi.position_id = COALESCE(oi.position_id, a.position_id)
            WHERE oi.application_id IS NULL
              AND (oi.position_id IS NULL OR oi.position_id = a.position_id)
        ");

        // Prefer the oldest record per application + stage.
        $this->execute("
            DELETE oi_dup FROM {$table} oi_dup
            INNER JOIN {$table} oi_keep ON
                oi_keep.organization_id = oi_dup.organization_id
                AND oi_keep.student_id = oi_dup.student_id
                AND COALESCE(oi_keep.application_id, 0) = COALESCE(oi_dup.application_id, 0)
                AND COALESCE(oi_keep.position_id, 0) = COALESCE(oi_dup.position_id, 0)
                AND oi_keep.interview_stage = oi_dup.interview_stage
                AND oi_keep.id < oi_dup.id
        ");

        // Fallback dedupe for legacy rows still missing application_id.
        $this->execute("
            DELETE oi_dup FROM {$table} oi_dup
            INNER JOIN {$table} oi_keep ON
                oi_keep.organization_id = oi_dup.organization_id
                AND oi_keep.student_id = oi_dup.student_id
                AND COALESCE(oi_keep.position_id, 0) = COALESCE(oi_dup.position_id, 0)
                AND oi_keep.interview_stage = oi_dup.interview_stage
                AND oi_keep.application_id IS NULL
                AND oi_dup.application_id IS NULL
                AND oi_keep.id < oi_dup.id
        ");

        $indexes = $this->db->getSchema()->findUniqueIndexes($schema);
        $hasUnique = false;
        foreach ($indexes as $indexCols) {
            if ($indexCols === ['application_id', 'interview_stage']) {
                $hasUnique = true;
                break;
            }
        }

        if (!$hasUnique) {
            $this->createIndex(
                'uq_org_interview_application_stage',
                $table,
                ['application_id', 'interview_stage'],
                true
            );
        }
    }

    public function safeDown()
    {
        $table = '{{%org_interview}}';
        $schema = $this->db->getTableSchema($table, true);
        if ($schema === null) {
            return;
        }

        if ($this->db->getTableSchema($table, true)->getColumn('interview_stage') !== null) {
            try {
                $this->dropIndex('uq_org_interview_application_stage', $table);
            } catch (\Throwable $e) {
                // Index may not exist.
            }
            $this->dropColumn($table, 'interview_stage');
        }
    }
}
