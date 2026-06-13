<?php

use yii\db\Migration;

/**
 * Extended profile fields for role-based registration.
 */
class m260612_140000_registration_profile_fields extends Migration
{
    public function safeUp()
    {
        $userSchema = $this->db->schema->getTableSchema('{{%user}}', true);
        if ($userSchema && !isset($userSchema->columns['first_name'])) {
            $this->addColumn('{{%user}}', 'first_name', $this->string(100)->null()->after('username'));
            $this->addColumn('{{%user}}', 'last_name', $this->string(100)->null()->after('first_name'));
            $this->addColumn('{{%user}}', 'phone', $this->string(20)->null()->after('email'));
        }

        $studentSchema = $this->db->schema->getTableSchema('{{%student}}', true);
        if ($studentSchema && !isset($studentSchema->columns['graduation_year'])) {
            $this->addColumn('{{%student}}', 'graduation_year', $this->integer()->null()->after('academic_level'));
        }

        $orgSchema = $this->db->schema->getTableSchema('{{%organization}}', true);
        if ($orgSchema) {
            $columns = [
                'contact_person' => $this->string(255)->null(),
                'registration_number' => $this->string(120)->null(),
                'industry' => $this->string(120)->null(),
                'organization_type' => $this->string(50)->null(),
                'country' => $this->string(100)->null(),
                'region' => $this->string(100)->null(),
                'city' => $this->string(100)->null(),
                'address' => $this->string(500)->null(),
                'registration_certificate' => $this->string(500)->null(),
                'phone' => $this->string(20)->null(),
            ];
            foreach ($columns as $name => $type) {
                if (!isset($orgSchema->columns[$name])) {
                    $this->addColumn('{{%organization}}', $name, $type);
                }
            }
        }
    }

    public function safeDown()
    {
        $userSchema = $this->db->schema->getTableSchema('{{%user}}', true);
        if ($userSchema && isset($userSchema->columns['first_name'])) {
            $this->dropColumn('{{%user}}', 'first_name');
            $this->dropColumn('{{%user}}', 'last_name');
            $this->dropColumn('{{%user}}', 'phone');
        }

        $studentSchema = $this->db->schema->getTableSchema('{{%student}}', true);
        if ($studentSchema && isset($studentSchema->columns['graduation_year'])) {
            $this->dropColumn('{{%student}}', 'graduation_year');
        }

        $orgSchema = $this->db->schema->getTableSchema('{{%organization}}', true);
        if ($orgSchema) {
            foreach ([
                'contact_person', 'registration_number', 'industry', 'organization_type',
                'country', 'region', 'city', 'address', 'registration_certificate', 'phone',
            ] as $name) {
                if (isset($orgSchema->columns[$name])) {
                    $this->dropColumn('{{%organization}}', $name);
                }
            }
        }
    }
}
