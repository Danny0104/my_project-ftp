<?php

use yii\db\Migration;

/**
 * Platform activity logging, academic faculty registry, and application uniqueness.
 */
class m260610_100000_platform_activity_and_academic_faculty extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%platform_activity_log}}', true) === null) {
            $this->createTable('{{%platform_activity_log}}', [
                'id' => $this->primaryKey(),
                'user_id' => $this->integer()->null(),
                'action' => $this->string(120)->notNull(),
                'entity_type' => $this->string(80)->null(),
                'entity_id' => $this->integer()->null(),
                'meta_json' => $this->text()->null(),
                'created_at' => $this->integer()->notNull(),
            ]);
            $this->createIndex('idx-platform_activity_log-action', '{{%platform_activity_log}}', 'action');
            $this->createIndex('idx-platform_activity_log-created', '{{%platform_activity_log}}', 'created_at');
        }

        if ($this->db->getTableSchema('{{%academic_faculty}}', true) === null) {
            $this->createTable('{{%academic_faculty}}', [
                'id' => $this->primaryKey(),
                'name' => $this->string(255)->notNull(),
                'slug' => $this->string(120)->notNull(),
                'description' => $this->string(500)->null(),
                'is_active' => $this->boolean()->notNull()->defaultValue(true),
                'created_at' => $this->integer()->notNull(),
                'updated_at' => $this->integer()->notNull(),
            ]);
            $this->createIndex('idx-academic_faculty-slug', '{{%academic_faculty}}', 'slug', true);
            $this->createIndex('idx-academic_faculty-name', '{{%academic_faculty}}', 'name', true);
        }

        $fieldSchema = $this->db->getTableSchema('{{%field_of_study}}', true);
        if ($fieldSchema && $fieldSchema->getColumn('faculty_id') === null) {
            $this->addColumn('{{%field_of_study}}', 'faculty_id', $this->integer()->null()->after('category'));
            $this->addForeignKey(
                'fk-field_of_study-faculty',
                '{{%field_of_study}}',
                'faculty_id',
                '{{%academic_faculty}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }

        try {
            $this->createIndex(
                'idx-application-student_position-unique',
                '{{%application}}',
                ['student_id', 'position_id'],
                true
            );
        } catch (\Exception $e) {
            // Index may already exist
        }

        $adminSchema = $this->db->getTableSchema('{{%admin}}', true);
        if ($adminSchema && $adminSchema->getColumn('preferences') === null) {
            $this->addColumn('{{%admin}}', 'preferences', $this->text()->null());
        }

        $this->seedFacultiesFromFields();
    }

    public function safeDown()
    {
        try {
            $this->dropIndex('idx-application-student_position-unique', '{{%application}}');
        } catch (\Exception $e) {
            // ignore
        }

        $fieldSchema = $this->db->getTableSchema('{{%field_of_study}}', true);
        if ($fieldSchema && $fieldSchema->getColumn('faculty_id') !== null) {
            $this->dropForeignKey('fk-field_of_study-faculty', '{{%field_of_study}}');
            $this->dropColumn('{{%field_of_study}}', 'faculty_id');
        }

        $adminSchema = $this->db->getTableSchema('{{%admin}}', true);
        if ($adminSchema && $adminSchema->getColumn('preferences') !== null) {
            $this->dropColumn('{{%admin}}', 'preferences');
        }

        $this->dropTable('{{%academic_faculty}}');
        $this->dropTable('{{%platform_activity_log}}');
    }

    private function seedFacultiesFromFields(): void
    {
        if ($this->db->getTableSchema('{{%academic_faculty}}', true) === null) {
            return;
        }

        $names = (new \yii\db\Query())
            ->select(['faculty'])
            ->from('{{%field_of_study}}')
            ->where(['not', ['faculty' => null]])
            ->andWhere(['<>', 'faculty', ''])
            ->distinct()
            ->column();

        $now = time();
        foreach ($names as $name) {
            $slug = \yii\helpers\Inflector::slug($name);
            $exists = (new \yii\db\Query())
                ->from('{{%academic_faculty}}')
                ->where(['slug' => $slug])
                ->exists();
            if ($exists) {
                continue;
            }
            $this->insert('{{%academic_faculty}}', [
                'name' => $name,
                'slug' => $slug,
                'description' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $faculties = (new \yii\db\Query())
            ->select(['id', 'name'])
            ->from('{{%academic_faculty}}')
            ->all();
        foreach ($faculties as $faculty) {
            $this->update(
                '{{%field_of_study}}',
                ['faculty_id' => $faculty['id']],
                ['faculty' => $faculty['name'], 'faculty_id' => null]
            );
        }
    }
}
