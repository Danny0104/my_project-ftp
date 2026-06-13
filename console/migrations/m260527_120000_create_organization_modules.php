<?php

use yii\db\Migration;

/**
 * Organization panel modules: interviews, programs, coordination, reviews, team, notes.
 */
class m260527_120000_create_organization_modules extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%org_internship_program}}', [
            'id' => $this->primaryKey(),
            'organization_id' => $this->integer()->notNull(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'category' => $this->string(100),
            'status' => $this->string(30)->notNull()->defaultValue('draft'),
            'start_date' => $this->date(),
            'end_date' => $this->date(),
            'capacity' => $this->integer()->defaultValue(0),
            'completion_percent' => $this->smallInteger()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_org_program_org', '{{%org_internship_program}}', 'organization_id');
        $this->addForeignKey(
            'fk_org_program_org',
            '{{%org_internship_program}}',
            'organization_id',
            '{{%organization}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%org_program_student}}', [
            'id' => $this->primaryKey(),
            'program_id' => $this->integer()->notNull(),
            'student_id' => $this->integer()->notNull(),
            'application_id' => $this->integer(),
            'status' => $this->string(30)->notNull()->defaultValue('active'),
            'progress_percent' => $this->smallInteger()->defaultValue(0),
            'assigned_at' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_org_ps_program', '{{%org_program_student}}', 'program_id');
        $this->addForeignKey('fk_org_ps_program', '{{%org_program_student}}', 'program_id', '{{%org_internship_program}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_org_ps_student', '{{%org_program_student}}', 'student_id', '{{%student}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%org_interview}}', [
            'id' => $this->primaryKey(),
            'organization_id' => $this->integer()->notNull(),
            'application_id' => $this->integer(),
            'student_id' => $this->integer()->notNull(),
            'position_id' => $this->integer(),
            'title' => $this->string(255)->notNull(),
            'scheduled_at' => $this->integer()->notNull(),
            'duration_minutes' => $this->smallInteger()->defaultValue(45),
            'meeting_link' => $this->string(500),
            'location' => $this->string(255),
            'status' => $this->string(30)->notNull()->defaultValue('scheduled'),
            'evaluation_score' => $this->smallInteger(),
            'evaluation_notes' => $this->text(),
            'interviewer_name' => $this->string(255),
            'reminder_sent' => $this->smallInteger()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_org_interview_org', '{{%org_interview}}', 'organization_id');
        $this->createIndex('idx_org_interview_scheduled', '{{%org_interview}}', 'scheduled_at');
        $this->addForeignKey('fk_org_interview_org', '{{%org_interview}}', 'organization_id', '{{%organization}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_org_interview_student', '{{%org_interview}}', 'student_id', '{{%student}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%org_coordination}}', [
            'id' => $this->primaryKey(),
            'organization_id' => $this->integer()->notNull(),
            'student_id' => $this->integer()->notNull(),
            'application_id' => $this->integer(),
            'university_name' => $this->string(255),
            'supervisor_name' => $this->string(255),
            'supervisor_email' => $this->string(255),
            'workflow_status' => $this->string(50)->notNull()->defaultValue('initiated'),
            'approval_status' => $this->string(50)->notNull()->defaultValue('pending'),
            'progress_notes' => $this->text(),
            'document_path' => $this->string(500),
            'due_at' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_org_coord_org', '{{%org_coordination}}', 'organization_id');
        $this->addForeignKey('fk_org_coord_org', '{{%org_coordination}}', 'organization_id', '{{%organization}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%org_review}}', [
            'id' => $this->primaryKey(),
            'organization_id' => $this->integer()->notNull(),
            'student_id' => $this->integer(),
            'application_id' => $this->integer(),
            'program_id' => $this->integer(),
            'rating' => $this->smallInteger()->notNull()->defaultValue(0),
            'category' => $this->string(50)->notNull()->defaultValue('internship'),
            'title' => $this->string(255)->notNull(),
            'feedback' => $this->text(),
            'supervisor_comment' => $this->text(),
            'status' => $this->string(30)->notNull()->defaultValue('published'),
            'reviewer_user_id' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_org_review_org', '{{%org_review}}', 'organization_id');
        $this->addForeignKey('fk_org_review_org', '{{%org_review}}', 'organization_id', '{{%organization}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%org_team_member}}', [
            'id' => $this->primaryKey(),
            'organization_id' => $this->integer()->notNull(),
            'user_id' => $this->integer(),
            'email' => $this->string(255)->notNull(),
            'name' => $this->string(255)->notNull(),
            'role' => $this->string(50)->notNull()->defaultValue('recruiter'),
            'status' => $this->string(30)->notNull()->defaultValue('active'),
            'permissions_json' => $this->text(),
            'last_active_at' => $this->integer(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_org_team_org', '{{%org_team_member}}', 'organization_id');
        $this->createIndex('idx_org_team_email', '{{%org_team_member}}', ['organization_id', 'email'], true);
        $this->addForeignKey('fk_org_team_org', '{{%org_team_member}}', 'organization_id', '{{%organization}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%org_team_activity}}', [
            'id' => $this->primaryKey(),
            'organization_id' => $this->integer()->notNull(),
            'team_member_id' => $this->integer(),
            'user_id' => $this->integer(),
            'action' => $this->string(100)->notNull(),
            'meta_json' => $this->text(),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_org_activity_org', '{{%org_team_activity}}', 'organization_id');
        $this->addForeignKey('fk_org_activity_org', '{{%org_team_activity}}', 'organization_id', '{{%organization}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%org_candidate_note}}', [
            'id' => $this->primaryKey(),
            'organization_id' => $this->integer()->notNull(),
            'student_id' => $this->integer()->notNull(),
            'application_id' => $this->integer(),
            'author_user_id' => $this->integer()->notNull(),
            'note' => $this->text()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_org_note_org_student', '{{%org_candidate_note}}', ['organization_id', 'student_id']);
        $this->addForeignKey('fk_org_note_org', '{{%org_candidate_note}}', 'organization_id', '{{%organization}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%org_candidate_note}}');
        $this->dropTable('{{%org_team_activity}}');
        $this->dropTable('{{%org_team_member}}');
        $this->dropTable('{{%org_review}}');
        $this->dropTable('{{%org_coordination}}');
        $this->dropTable('{{%org_interview}}');
        $this->dropTable('{{%org_program_student}}');
        $this->dropTable('{{%org_internship_program}}');
    }
}
