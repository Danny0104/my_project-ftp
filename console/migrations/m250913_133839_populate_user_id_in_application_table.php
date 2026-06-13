<?php

use yii\db\Migration;

class m250913_133839_populate_user_id_in_application_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Check if user_id column exists, if not add it
        if (!$this->db->getTableSchema('{{%application}}')->getColumn('user_id')) {
            $this->addColumn('{{%application}}', 'user_id', $this->integer()->null()->after('id'));
        }
        
        // Check if updated_at column exists, if not add it
        if (!$this->db->getTableSchema('{{%application}}')->getColumn('updated_at')) {
            $this->addColumn('{{%application}}', 'updated_at', $this->integer()->notNull()->defaultValue(0)->after('created_at'));
        }
        
        // Check if cover_letter column exists, if not add it
        if (!$this->db->getTableSchema('{{%application}}')->getColumn('cover_letter')) {
            $this->addColumn('{{%application}}', 'cover_letter', $this->text()->after('feedback'));
        }
        
        // Check if resume_url column exists, if not add it
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
        
        // Now make user_id NOT NULL if it's not already
        $this->alterColumn('{{%application}}', 'user_id', $this->integer()->notNull());
        
        // Add foreign key constraint if it doesn't exist
        $foreignKeys = $this->db->getSchema()->getTableForeignKeys('{{%application}}');
        $hasForeignKey = false;
        foreach ($foreignKeys as $fk) {
            if ($fk->columns[0] === 'user_id') {
                $hasForeignKey = true;
                break;
            }
        }
        
        if (!$hasForeignKey) {
            $this->addForeignKey(
                'fk-application-user_id',
                '{{%application}}',
                'user_id',
                '{{%user}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
        
        // Add index for better performance if it doesn't exist
        $indexes = $this->db->getSchema()->getTableIndexes('{{%application}}');
        $hasIndex = false;
        foreach ($indexes as $index) {
            if (in_array('user_id', $index->columns)) {
                $hasIndex = true;
                break;
            }
        }
        
        if (!$hasIndex) {
            $this->createIndex('idx-application-user_id', '{{%application}}', 'user_id');
        }
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
        echo "m250913_133839_populate_user_id_in_application_table cannot be reverted.\n";

        return false;
    }
    */
}
