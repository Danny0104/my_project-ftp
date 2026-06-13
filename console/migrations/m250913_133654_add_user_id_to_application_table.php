<?php

use yii\db\Migration;

class m250913_133654_add_user_id_to_application_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add user_id column to application table (nullable first)
        $this->addColumn('{{%application}}', 'user_id', $this->integer()->null()->after('id'));
        
        // Add updated_at column if it doesn't exist
        if (!$this->db->getTableSchema('{{%application}}')->getColumn('updated_at')) {
            $this->addColumn('{{%application}}', 'updated_at', $this->integer()->notNull()->defaultValue(0)->after('created_at'));
        }
        
        // Add cover_letter column if it doesn't exist
        if (!$this->db->getTableSchema('{{%application}}')->getColumn('cover_letter')) {
            $this->addColumn('{{%application}}', 'cover_letter', $this->text()->after('feedback'));
        }
        
        // Add resume_url column if it doesn't exist
        if (!$this->db->getTableSchema('{{%application}}')->getColumn('resume_url')) {
            $this->addColumn('{{%application}}', 'resume_url', $this->string(500)->after('cover_letter'));
        }
        
        // Populate user_id for existing applications based on student_id
        $applications = $this->db->createCommand('SELECT id, student_id FROM {{%application}} WHERE user_id IS NULL')->queryAll();
        foreach ($applications as $app) {
            $student = $this->db->createCommand('SELECT user_id FROM {{%student}} WHERE id = :student_id', [':student_id' => $app['student_id']])->queryOne();
            if ($student) {
                $this->db->createCommand('UPDATE {{%application}} SET user_id = :user_id WHERE id = :id', [
                    ':user_id' => $student['user_id'],
                    ':id' => $app['id']
                ])->execute();
            }
        }
        
        // Now make user_id NOT NULL
        $this->alterColumn('{{%application}}', 'user_id', $this->integer()->notNull());
        
        // Add foreign key constraint
        $this->addForeignKey(
            'fk-application-user_id',
            '{{%application}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        
        // Add index for better performance
        $this->createIndex('idx-application-user_id', '{{%application}}', 'user_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key constraint
        $this->dropForeignKey('fk-application-user_id', '{{%application}}');
        
        // Drop index
        $this->dropIndex('idx-application-user_id', '{{%application}}');
        
        // Drop columns
        $this->dropColumn('{{%application}}', 'user_id');
        $this->dropColumn('{{%application}}', 'updated_at');
        $this->dropColumn('{{%application}}', 'cover_letter');
        $this->dropColumn('{{%application}}', 'resume_url');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m250913_133654_add_user_id_to_application_table cannot be reverted.\n";

        return false;
    }
    */
}
