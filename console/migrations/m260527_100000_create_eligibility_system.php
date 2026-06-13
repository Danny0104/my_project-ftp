<?php

use yii\db\Migration;

/**
 * Academic eligibility & university regulation system.
 */
class m260527_100000_create_eligibility_system extends Migration
{
    public function safeUp()
    {
        if ($this->db->getTableSchema('{{%field_of_study}}', true) === null) {
            $this->createTable('{{%field_of_study}}', [
                'id' => $this->primaryKey(),
                'slug' => $this->string(120)->notNull()->unique(),
                'name' => $this->string(255)->notNull(),
                'category' => $this->string(80)->notNull(),
                'faculty' => $this->string(255)->null(),
                'department' => $this->string(255)->null(),
                'aliases' => $this->text()->null(),
                'is_active' => $this->boolean()->notNull()->defaultValue(true),
                'created_at' => $this->integer()->notNull(),
            ]);
            $this->createIndex('idx-field_of_study-category', '{{%field_of_study}}', 'category');
            $this->createIndex('idx-field_of_study-name', '{{%field_of_study}}', 'name');
        }

        if ($this->db->getTableSchema('{{%position_allowed_field}}', true) === null) {
            $this->createTable('{{%position_allowed_field}}', [
                'position_id' => $this->integer()->notNull(),
                'field_of_study_id' => $this->integer()->notNull(),
                'PRIMARY KEY (position_id, field_of_study_id)',
            ]);
            $this->addForeignKey(
                'fk-position_allowed_field-position',
                '{{%position_allowed_field}}',
                'position_id',
                '{{%position}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                'fk-position_allowed_field-field',
                '{{%position_allowed_field}}',
                'field_of_study_id',
                '{{%field_of_study}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }

        if ($this->db->getTableSchema('{{%platform_regulation}}', true) === null) {
            $this->createTable('{{%platform_regulation}}', [
                'id' => $this->primaryKey(),
                'key' => $this->string(100)->notNull()->unique(),
                'value' => $this->text()->notNull(),
                'description' => $this->string(500)->null(),
                'updated_at' => $this->integer()->notNull(),
            ]);
        }

        if ($this->db->getTableSchema('{{%eligibility_audit_log}}', true) === null) {
            $this->createTable('{{%eligibility_audit_log}}', [
                'id' => $this->primaryKey(),
                'user_id' => $this->integer()->notNull(),
                'student_id' => $this->integer()->null(),
                'position_id' => $this->integer()->notNull(),
                'eligible' => $this->boolean()->notNull(),
                'match_score' => $this->smallInteger()->notNull()->defaultValue(0),
                'reasons_json' => $this->text()->null(),
                'action' => $this->string(50)->notNull()->defaultValue('check'),
                'created_at' => $this->integer()->notNull(),
            ]);
            $this->createIndex('idx-eligibility_audit-user', '{{%eligibility_audit_log}}', 'user_id');
            $this->createIndex('idx-eligibility_audit-position', '{{%eligibility_audit_log}}', 'position_id');
        }

        $studentSchema = $this->db->getTableSchema('{{%student}}', true);
        if ($studentSchema && $studentSchema->getColumn('program') === null) {
            $this->addColumn('{{%student}}', 'program', $this->string(255)->null()->after('field_of_study'));
            $this->addColumn('{{%student}}', 'department', $this->string(255)->null()->after('program'));
            $this->addColumn('{{%student}}', 'faculty', $this->string(255)->null()->after('department'));
            $this->addColumn('{{%student}}', 'academic_level', $this->string(50)->null()->after('faculty'));
            $this->addColumn('{{%student}}', 'skills', $this->text()->null()->after('academic_level'));
            $this->addColumn('{{%student}}', 'gpa', $this->decimal(3, 2)->null()->after('skills'));
        }

        $positionSchema = $this->db->getTableSchema('{{%position}}', true);
        if ($positionSchema && $positionSchema->getColumn('category') === null) {
            $this->addColumn('{{%position}}', 'category', $this->string(100)->null()->after('field_of_study'));
            $this->addColumn('{{%position}}', 'academic_level_required', $this->string(50)->null()->after('category'));
            $this->addColumn('{{%position}}', 'min_gpa', $this->decimal(3, 2)->null()->after('academic_level_required'));
            $this->addColumn('{{%position}}', 'application_deadline', $this->integer()->null()->after('min_gpa'));
        }

        try {
            $this->createIndex(
                'idx-application-user_position-unique',
                '{{%application}}',
                ['user_id', 'position_id'],
                true
            );
        } catch (\Exception $e) {
            // Index may already exist from partial migration run
        }

        if ((int) (new \yii\db\Query())->from('{{%field_of_study}}')->count() === 0) {
            $this->seedFieldsOfStudy();
        }
        if ((int) (new \yii\db\Query())->from('{{%platform_regulation}}')->count() === 0) {
            $this->seedRegulations();
        }
        $this->syncExistingPositionFields();
    }

    public function safeDown()
    {
        $this->dropIndex('idx-application-user_position-unique', '{{%application}}');

        $this->dropColumn('{{%position}}', 'application_deadline');
        $this->dropColumn('{{%position}}', 'min_gpa');
        $this->dropColumn('{{%position}}', 'academic_level_required');
        $this->dropColumn('{{%position}}', 'category');

        $this->dropColumn('{{%student}}', 'gpa');
        $this->dropColumn('{{%student}}', 'skills');
        $this->dropColumn('{{%student}}', 'academic_level');
        $this->dropColumn('{{%student}}', 'faculty');
        $this->dropColumn('{{%student}}', 'department');
        $this->dropColumn('{{%student}}', 'program');

        $this->dropTable('{{%eligibility_audit_log}}');
        $this->dropTable('{{%platform_regulation}}');
        $this->dropTable('{{%position_allowed_field}}');
        $this->dropTable('{{%field_of_study}}');
    }

    private function seedFieldsOfStudy(): void
    {
        $now = time();
        $rows = [
            // Technology
            ['computer-science', 'Computer Science', 'technology', 'School of Computing', 'Computer Science', 'CS,Computing,Informatics'],
            ['information-technology', 'Information Technology', 'technology', 'School of Computing', 'Information Technology', 'IT,Info Tech'],
            ['software-engineering', 'Software Engineering', 'technology', 'School of Computing', 'Software Engineering', 'SE,Software Dev'],
            ['data-science', 'Data Science', 'technology', 'School of Computing', 'Data Science', 'Data Analytics,ML,AI'],
            ['cybersecurity', 'Cybersecurity', 'technology', 'School of Computing', 'Cybersecurity', 'InfoSec,Security'],
            // Healthcare
            ['medicine', 'Medicine', 'healthcare', 'School of Medicine', 'Medicine', 'MBBS,Medical,Doctor'],
            ['nursing', 'Nursing', 'healthcare', 'School of Nursing', 'Nursing', 'RN,Nurse'],
            ['pharmacy', 'Pharmacy', 'healthcare', 'School of Pharmacy', 'Pharmacy', 'PharmD'],
            ['public-health', 'Public Health', 'healthcare', 'School of Public Health', 'Public Health', ''],
            // Engineering
            ['civil-engineering', 'Civil Engineering', 'engineering', 'School of Engineering', 'Civil Engineering', 'Civil Eng'],
            ['mechanical-engineering', 'Mechanical Engineering', 'engineering', 'School of Engineering', 'Mechanical Engineering', 'Mech Eng'],
            ['electrical-engineering', 'Electrical Engineering', 'engineering', 'School of Engineering', 'Electrical Engineering', 'EE,Electrical'],
            ['engineering-general', 'Engineering', 'engineering', 'School of Engineering', 'General Engineering', 'General Eng'],
            // Law
            ['law', 'Law', 'law', 'School of Law', 'Law', 'LLB,Legal Studies'],
            // Business
            ['business-administration', 'Business Administration', 'business', 'School of Business', 'Business Administration', 'BBA,Business,Mgmt'],
            ['accounting', 'Accounting', 'business', 'School of Business', 'Accounting', 'Finance Accounting'],
            ['marketing', 'Marketing', 'business', 'School of Business', 'Marketing', 'Digital Marketing'],
            ['finance', 'Finance', 'business', 'School of Business', 'Finance', 'Financial Management'],
            ['statistics', 'Statistics', 'business', 'School of Business', 'Statistics', 'Stat,Data Analysis'],
            // Education & other
            ['education', 'Education', 'education', 'School of Education', 'Education', 'Teaching,Pedagogy'],
            ['agriculture', 'Agriculture', 'agriculture', 'School of Agriculture', 'Agriculture', 'Agri,Food Science'],
            ['healthcare-general', 'Healthcare', 'healthcare', 'School of Health Sciences', 'General Healthcare', 'Health Sciences'],
        ];

        foreach ($rows as [$slug, $name, $category, $faculty, $department, $aliases]) {
            $this->insert('{{%field_of_study}}', [
                'slug' => $slug,
                'name' => $name,
                'category' => $category,
                'faculty' => $faculty,
                'department' => $department,
                'aliases' => $aliases,
                'is_active' => true,
                'created_at' => $now,
            ]);
        }
    }

    private function seedRegulations(): void
    {
        $now = time();
        $year = (int) date('Y');
        $regs = [
            ['require_profile_complete', '1', 'Student must complete required profile fields before applying'],
            ['require_cv', '1', 'CV/resume required before applying'],
            ['require_field_of_study', '1', 'Student must have field of study set'],
            ['strict_field_matching', '1', 'Enforce taxonomy-based field matching (no cross-faculty bypass)'],
            ['max_applications_per_semester', '8', 'Maximum applications a student may submit per semester'],
            ['training_period_start', $year . '-06-01', 'Earliest date students may apply (YYYY-MM-DD)'],
            ['training_period_end', $year . '-12-31', 'Latest date students may apply (YYYY-MM-DD)'],
            ['min_profile_completion_percent', '75', 'Minimum profile completion percentage required'],
            ['min_gpa_default', '2.0', 'Default minimum GPA if position does not specify'],
        ];

        foreach ($regs as [$key, $value, $desc]) {
            $this->insert('{{%platform_regulation}}', [
                'key' => $key,
                'value' => $value,
                'description' => $desc,
                'updated_at' => $now,
            ]);
        }
    }

    private function syncExistingPositionFields(): void
    {
        $positions = (new \yii\db\Query())
            ->from('{{%position}}')
            ->where(['not', ['field_of_study' => null]])
            ->andWhere(['<>', 'field_of_study', ''])
            ->all();

        $fieldMap = (new \yii\db\Query())
            ->from('{{%field_of_study}}')
            ->indexBy('name')
            ->all();

        foreach ($positions as $pos) {
            $seen = [];
            $parts = array_map('trim', explode(',', (string) $pos['field_of_study']));
            foreach ($parts as $part) {
                if ($part === '') {
                    continue;
                }
                $fieldId = $this->resolveFieldId($part, $fieldMap);
                if ($fieldId && !isset($seen[$fieldId])) {
                    $seen[$fieldId] = true;
                    $exists = (new \yii\db\Query())
                        ->from('{{%position_allowed_field}}')
                        ->where(['position_id' => $pos['id'], 'field_of_study_id' => $fieldId])
                        ->exists();
                    if (!$exists) {
                        $this->insert('{{%position_allowed_field}}', [
                            'position_id' => $pos['id'],
                            'field_of_study_id' => $fieldId,
                        ]);
                    }
                }
            }
        }
    }

    private function resolveFieldId(string $text, array $fieldMap): ?int
    {
        foreach ($fieldMap as $name => $row) {
            if (strcasecmp($name, $text) === 0) {
                return (int) $row['id'];
            }
            $aliases = array_map('trim', explode(',', (string) ($row['aliases'] ?? '')));
            foreach ($aliases as $alias) {
                if ($alias !== '' && stripos($text, $alias) !== false) {
                    return (int) $row['id'];
                }
            }
        }
        return null;
    }
}
