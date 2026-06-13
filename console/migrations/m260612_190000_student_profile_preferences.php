<?php

use yii\db\Migration;

/**
 * Internship preferences and social links for student profiles.
 */
class m260612_190000_student_profile_preferences extends Migration
{
    public function safeUp()
    {
        $schema = $this->db->schema->getTableSchema('{{%student}}', true);
        if (!$schema) {
            return;
        }

        $columns = [
            'preferred_industry' => $this->string(120)->null(),
            'preferred_work_mode' => $this->string(50)->null(),
            'preferred_locations' => $this->string(255)->null(),
            'linkedin_url' => $this->string(255)->null(),
            'github_url' => $this->string(255)->null(),
            'portfolio_url' => $this->string(255)->null(),
        ];

        foreach ($columns as $name => $type) {
            if (!isset($schema->columns[$name])) {
                $this->addColumn('{{%student}}', $name, $type);
            }
        }
    }

    public function safeDown()
    {
        $schema = $this->db->schema->getTableSchema('{{%student}}', true);
        if (!$schema) {
            return;
        }

        foreach ([
            'preferred_industry',
            'preferred_work_mode',
            'preferred_locations',
            'linkedin_url',
            'github_url',
            'portfolio_url',
        ] as $name) {
            if (isset($schema->columns[$name])) {
                $this->dropColumn('{{%student}}', $name);
            }
        }
    }
}
