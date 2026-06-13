<?php

namespace common\components;

use Yii;
use yii\base\Component;
use yii\caching\Cache;

/**
 * Cache Helper Component
 * Provides caching utilities and query optimization
 */
class CacheHelper extends Component
{
    /**
     * @var Cache Cache component
     */
    public $cache;

    /**
     * @var int Default cache duration in seconds
     */
    public $defaultDuration = 3600; // 1 hour

    public function init()
    {
        parent::init();
        if ($this->cache === null) {
            $this->cache = Yii::$app->cache;
        }
    }

    /**
     * Get cached data or execute callback and cache result
     * @param string $key Cache key
     * @param callable $callback Callback to execute if cache miss
     * @param int $duration Cache duration in seconds
     * @return mixed
     */
    public function getOrSet($key, $callback, $duration = null)
    {
        if ($duration === null) {
            $duration = $this->defaultDuration;
        }

        $data = $this->cache->get($key);
        if ($data === false) {
            $data = $callback();
            $this->cache->set($key, $data, $duration);
        }

        return $data;
    }

    /**
     * Cache database query result
     * @param string $key Cache key
     * @param callable $queryCallback Query callback
     * @param int $duration Cache duration
     * @return mixed
     */
    public function cacheQuery($key, $queryCallback, $duration = null)
    {
        return $this->getOrSet($key, $queryCallback, $duration);
    }

    /**
     * Invalidate cache by pattern
     * @param string $pattern Cache key pattern
     */
    public function invalidateByPattern($pattern)
    {
        if ($this->cache instanceof \yii\caching\TagDependency) {
            $this->cache->invalidate($pattern);
        }
    }

    /**
     * Get cache key for model
     * @param string $modelClass
     * @param mixed $id
     * @param string $suffix
     * @return string
     */
    public static function getModelKey($modelClass, $id, $suffix = '')
    {
        $key = strtolower(str_replace('\\', '_', $modelClass)) . '_' . $id;
        if ($suffix) {
            $key .= '_' . $suffix;
        }
        return $key;
    }

    /**
     * Get cache key for query
     * @param string $modelClass
     * @param array $conditions
     * @param string $suffix
     * @return string
     */
    public static function getQueryKey($modelClass, $conditions = [], $suffix = '')
    {
        $key = strtolower(str_replace('\\', '_', $modelClass)) . '_query';
        if (!empty($conditions)) {
            $key .= '_' . md5(serialize($conditions));
        }
        if ($suffix) {
            $key .= '_' . $suffix;
        }
        return $key;
    }

    /**
     * Cache model with relationships
     * @param string $modelClass
     * @param mixed $id
     * @param array $withRelations
     * @param int $duration
     * @return mixed
     */
    public function cacheModelWithRelations($modelClass, $id, $withRelations = [], $duration = null)
    {
        $key = self::getModelKey($modelClass, $id, 'with_relations_' . md5(serialize($withRelations)));
        
        return $this->cacheQuery($key, function() use ($modelClass, $id, $withRelations) {
            $query = $modelClass::find()->where(['id' => $id]);
            if (!empty($withRelations)) {
                $query->with($withRelations);
            }
            return $query->one();
        }, $duration);
    }

    /**
     * Cache paginated results
     * @param string $modelClass
     * @param array $conditions
     * @param int $page
     * @param int $pageSize
     * @param int $duration
     * @return mixed
     */
    public function cachePaginatedResults($modelClass, $conditions = [], $page = 1, $pageSize = 20, $duration = null)
    {
        $key = self::getQueryKey($modelClass, $conditions, "page_{$page}_size_{$pageSize}");
        
        return $this->cacheQuery($key, function() use ($modelClass, $conditions, $page, $pageSize) {
            $query = $modelClass::find();
            if (!empty($conditions)) {
                $query->where($conditions);
            }
            return $query->offset(($page - 1) * $pageSize)
                        ->limit($pageSize)
                        ->all();
        }, $duration);
    }

    /**
     * Cache statistics
     * @param string $type
     * @param int $duration
     * @return mixed
     */
    public function cacheStats($type, $duration = null)
    {
        $key = "stats_{$type}";
        
        return $this->cacheQuery($key, function() use ($type) {
            switch ($type) {
                case 'dashboard':
                    return [
                        'total_users' => \common\models\User::find()->count(),
                        'total_students' => \common\models\Student::find()->count(),
                        'total_organizations' => \common\models\Organization::find()->count(),
                        'total_applications' => \common\models\Application::find()->count(),
                        'total_positions' => \common\models\Position::find()->count(),
                        'total_notifications' => \common\models\Notification::find()->count(),
                    ];
                case 'recent_applications':
                    return \common\models\Application::find()
                        ->with(['student.user', 'position.organization'])
                        ->orderBy(['created_at' => SORT_DESC])
                        ->limit(5)
                        ->all();
                case 'recent_users':
                    return \common\models\User::find()
                        ->orderBy(['created_at' => SORT_DESC])
                        ->limit(5)
                        ->all();
                default:
                    return null;
            }
        }, $duration);
    }

    /**
     * Warm up cache
     */
    public function warmUpCache()
    {
        // Cache dashboard stats
        $this->cacheStats('dashboard', 1800); // 30 minutes
        
        // Cache recent data
        $this->cacheStats('recent_applications', 900); // 15 minutes
        $this->cacheStats('recent_users', 900); // 15 minutes
        
        // Cache active positions
        $this->cacheQuery('active_positions', function() {
            return \common\models\Position::find()
                ->where(['status' => 'active'])
                ->orderBy(['created_at' => SORT_DESC])
                ->limit(10)
                ->all();
        }, 1800); // 30 minutes
    }

    /**
     * Clear all cache
     */
    public function clearAll()
    {
        $this->cache->flush();
    }

    /**
     * Clear cache by tag
     * @param string $tag
     */
    public function clearByTag($tag)
    {
        if ($this->cache instanceof \yii\caching\TagDependency) {
            \yii\caching\TagDependency::invalidate($this->cache, $tag);
        }
    }
}
