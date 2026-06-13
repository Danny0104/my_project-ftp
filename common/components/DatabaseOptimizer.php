<?php

namespace common\components;

use Yii;
use yii\base\Component;
use yii\db\Query;
use yii\db\ActiveQuery;

/**
 * Database Optimizer Component
 * Provides database query optimization utilities
 */
class DatabaseOptimizer extends Component
{
    /**
     * Optimize query with proper joins and selects
     * @param ActiveQuery $query
     * @param array $options
     * @return ActiveQuery
     */
    public static function optimizeQuery($query, $options = [])
    {
        $defaultOptions = [
            'select' => true,
            'join' => true,
            'index' => true,
            'limit' => null,
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        // Add proper indexing hints
        if ($options['index']) {
            $query = self::addIndexHints($query);
        }
        
        // Optimize joins
        if ($options['join']) {
            $query = self::optimizeJoins($query);
        }
        
        // Add limit if specified
        if ($options['limit']) {
            $query->limit($options['limit']);
        }
        
        return $query;
    }

    /**
     * Add index hints to query
     * @param ActiveQuery $query
     * @return ActiveQuery
     */
    protected static function addIndexHints($query)
    {
        // Add index hints for common queries
        $tableName = $query->from;
        if (is_array($tableName)) {
            $tableName = array_keys($tableName)[0];
        }
        
        switch ($tableName) {
            case 'user':
                $query->andWhere(['status' => 10]); // Only active users
                break;
            case 'application':
                $query->orderBy(['created_at' => SORT_DESC]);
                break;
            case 'position':
                $query->andWhere(['status' => 'active']);
                break;
        }
        
        return $query;
    }

    /**
     * Optimize joins in query
     * @param ActiveQuery $query
     * @return ActiveQuery
     */
    protected static function optimizeJoins($query)
    {
        // Add eager loading for common relationships
        $withRelations = [];
        
        if (isset($query->from) && is_array($query->from)) {
            $tableName = array_keys($query->from)[0];
            
            switch ($tableName) {
                case 'application':
                    $withRelations = ['student.user', 'position.organization'];
                    break;
                case 'position':
                    $withRelations = ['organization'];
                    break;
                case 'student':
                    $withRelations = ['user'];
                    break;
            }
        }
        
        if (!empty($withRelations)) {
            $query->with($withRelations);
        }
        
        return $query;
    }

    /**
     * Get optimized query for dashboard statistics
     * @return array
     */
    public static function getDashboardStats()
    {
        $cache = Yii::$app->cache;
        $cacheKey = 'dashboard_stats_optimized';
        
        return $cache->getOrSet($cacheKey, function() {
            $stats = [];
            
            // Use single query with UNION for better performance
            $query = new Query();
            $query->select([
                'COUNT(CASE WHEN table_name = "user" THEN 1 END) as total_users',
                'COUNT(CASE WHEN table_name = "student" THEN 1 END) as total_students',
                'COUNT(CASE WHEN table_name = "organization" THEN 1 END) as total_organizations',
                'COUNT(CASE WHEN table_name = "application" THEN 1 END) as total_applications',
                'COUNT(CASE WHEN table_name = "position" AND status = "active" THEN 1 END) as total_positions',
                'COUNT(CASE WHEN table_name = "notification" THEN 1 END) as total_notifications'
            ])
            ->from([
                'user' => (new Query())->select(['table_name' => new \yii\db\Expression('"user"')])->from('user'),
                'student' => (new Query())->select(['table_name' => new \yii\db\Expression('"student"')])->from('student'),
                'organization' => (new Query())->select(['table_name' => new \yii\db\Expression('"organization"')])->from('organization'),
                'application' => (new Query())->select(['table_name' => new \yii\db\Expression('"application"')])->from('application'),
                'position' => (new Query())->select(['table_name' => new \yii\db\Expression('"position"'), 'status'])->from('position'),
                'notification' => (new Query())->select(['table_name' => new \yii\db\Expression('"notification"')])->from('notification')
            ]);
            
            $result = $query->one();
            
            return [
                'total_users' => (int)$result['total_users'],
                'total_students' => (int)$result['total_students'],
                'total_organizations' => (int)$result['total_organizations'],
                'total_applications' => (int)$result['total_applications'],
                'total_positions' => (int)$result['total_positions'],
                'total_notifications' => (int)$result['total_notifications'],
            ];
        }, 1800); // Cache for 30 minutes
    }

    /**
     * Get optimized recent applications query
     * @param int $limit
     * @return array
     */
    public static function getRecentApplications($limit = 5)
    {
        $cache = Yii::$app->cache;
        $cacheKey = "recent_applications_{$limit}";
        
        return $cache->getOrSet($cacheKey, function() use ($limit) {
            return \common\models\Application::find()
                ->select(['id', 'user_id', 'position_id', 'status', 'created_at'])
                ->with([
                    'student' => function($query) {
                        $query->select(['id', 'user_id', 'first_name', 'last_name']);
                    },
                    'student.user' => function($query) {
                        $query->select(['id', 'username']);
                    },
                    'position' => function($query) {
                        $query->select(['id', 'title', 'organization_id']);
                    },
                    'position.organization' => function($query) {
                        $query->select(['id', 'name']);
                    }
                ])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit($limit)
                ->asArray()
                ->all();
        }, 900); // Cache for 15 minutes
    }

    /**
     * Get optimized recent users query
     * @param int $limit
     * @return array
     */
    public static function getRecentUsers($limit = 5)
    {
        $cache = Yii::$app->cache;
        $cacheKey = "recent_users_{$limit}";
        
        return $cache->getOrSet($cacheKey, function() use ($limit) {
            return \common\models\User::find()
                ->select(['id', 'username', 'email', 'role', 'status', 'created_at'])
                ->where(['status' => 10]) // Only active users
                ->orderBy(['created_at' => SORT_DESC])
                ->limit($limit)
                ->asArray()
                ->all();
        }, 900); // Cache for 15 minutes
    }

    /**
     * Get optimized positions for homepage
     * @param int $limit
     * @return array
     */
    public static function getHomepagePositions($limit = 6)
    {
        $cache = Yii::$app->cache;
        $cacheKey = "homepage_positions_{$limit}";
        
        return $cache->getOrSet($cacheKey, function() use ($limit) {
            $service = new \common\services\PublicPositionService();

            return $service->openListingQuery()
                ->select(['p.id', 'p.title', 'p.description', 'p.type', 'p.organization_id', 'p.created_at'])
                ->with([
                    'organization' => function ($query) {
                        $query->select(['id', 'name']);
                    },
                ])
                ->orderBy(['p.created_at' => SORT_DESC])
                ->limit($limit)
                ->asArray()
                ->all();
        }, 1800); // Cache for 30 minutes
    }

    /**
     * Create database indexes for better performance
     */
    public static function createIndexes()
    {
        $db = Yii::$app->db;
        
        $indexes = [
            // User table indexes
            'CREATE INDEX IF NOT EXISTS idx_user_status ON user(status)',
            'CREATE INDEX IF NOT EXISTS idx_user_role ON user(role)',
            'CREATE INDEX IF NOT EXISTS idx_user_created_at ON user(created_at)',
            
            // Application table indexes
            'CREATE INDEX IF NOT EXISTS idx_application_user_id ON application(user_id)',
            'CREATE INDEX IF NOT EXISTS idx_application_position_id ON application(position_id)',
            'CREATE INDEX IF NOT EXISTS idx_application_status ON application(status)',
            'CREATE INDEX IF NOT EXISTS idx_application_created_at ON application(created_at)',
            
            // Position table indexes
            'CREATE INDEX IF NOT EXISTS idx_position_status ON position(status)',
            'CREATE INDEX IF NOT EXISTS idx_position_organization_id ON position(organization_id)',
            'CREATE INDEX IF NOT EXISTS idx_position_created_at ON position(created_at)',
            
            // Student table indexes
            'CREATE INDEX IF NOT EXISTS idx_student_user_id ON student(user_id)',
            
            // Organization table indexes
            'CREATE INDEX IF NOT EXISTS idx_organization_status ON organization(status)',
            
            // Notification table indexes
            'CREATE INDEX IF NOT EXISTS idx_notification_user_id ON notification(user_id)',
            'CREATE INDEX IF NOT EXISTS idx_notification_created_at ON notification(created_at)',
        ];
        
        foreach ($indexes as $index) {
            try {
                $db->createCommand($index)->execute();
            } catch (\Exception $e) {
                Yii::error("Failed to create index: {$index}. Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Analyze query performance
     * @param string $sql
     * @return array
     */
    public static function analyzeQuery($sql)
    {
        $db = Yii::$app->db;
        $result = $db->createCommand("EXPLAIN {$sql}")->queryAll();
        return $result;
    }
}
