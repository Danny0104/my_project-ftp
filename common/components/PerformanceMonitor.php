<?php

namespace common\components;

use Yii;
use yii\base\Component;
use yii\base\ActionEvent;
use yii\web\Controller;

/**
 * Performance Monitor Component
 * Monitors and logs performance metrics
 */
class PerformanceMonitor extends Component
{
    /**
     * @var array Performance thresholds
     */
    public $thresholds = [
        'database_query' => 1.0, // 1 second
        'page_load' => 1.0, // 1 seconds
        'api_request' => 1.5, // 1.5 seconds
        'file_upload' => 5.0, // 5 seconds
    ];

    /**
     * @var array Performance metrics
     */
    private $metrics = [];

    /**
     * @var float Start time
     */
    private $startTime;

    public function init()
    {
        parent::init();
        $this->startTime = microtime(true);
    }

    /**
     * Start monitoring an operation
     * @param string $operation
     * @return string Operation ID
     */
    public function startOperation($operation)
    {
        $operationId = uniqid($operation . '_', true);
        $this->metrics[$operationId] = [
            'operation' => $operation,
            'start_time' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
        
        return $operationId;
    }

    /**
     * End monitoring an operation
     * @param string $operationId
     * @param array $context
     */
    public function endOperation($operationId, $context = [])
    {
        if (!isset($this->metrics[$operationId])) {
            return;
        }

        $endTime = microtime(true);
        $metric = $this->metrics[$operationId];
        
        $executionTime = $endTime - $metric['start_time'];
        $memoryUsed = memory_get_usage(true) - $metric['memory_start'];
        
        $operation = $metric['operation'];
        $threshold = $this->thresholds[$operation] ?? 1.0;
        
        // Log performance issue if threshold exceeded
        if ($executionTime > $threshold) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handlePerformanceIssue($operation, $executionTime, array_merge($context, [
                'memory_used' => $memoryUsed,
                'operation_id' => $operationId,
            ]));
        }
        
        // Log performance metric
        $this->logPerformanceMetric($operation, $executionTime, $memoryUsed, $context);
        
        unset($this->metrics[$operationId]);
    }

    /**
     * Monitor database query performance
     * @param callable $queryCallback
     * @param string $queryName
     * @return mixed
     */
    public function monitorDatabaseQuery($queryCallback, $queryName = 'database_query')
    {
        $operationId = $this->startOperation($queryName);
        
        try {
            $result = $queryCallback();
            $this->endOperation($operationId, ['query_name' => $queryName]);
            return $result;
        } catch (\Exception $e) {
            $this->endOperation($operationId, [
                'query_name' => $queryName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Monitor page load performance
     * @param callable $pageCallback
     * @param string $pageName
     * @return mixed
     */
    public function monitorPageLoad($pageCallback, $pageName = 'page_load')
    {
        $operationId = $this->startOperation($pageName);
        
        try {
            $result = $pageCallback();
            $this->endOperation($operationId, [
                'page_name' => $pageName,
                'memory_peak' => memory_get_peak_usage(true)
            ]);
            return $result;
        } catch (\Exception $e) {
            $this->endOperation($operationId, [
                'page_name' => $pageName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Monitor API request performance
     * @param callable $apiCallback
     * @param string $endpoint
     * @return mixed
     */
    public function monitorApiRequest($apiCallback, $endpoint = 'api_request')
    {
        $operationId = $this->startOperation('api_request');
        
        try {
            $result = $apiCallback();
            $this->endOperation($operationId, [
                'endpoint' => $endpoint,
                'memory_peak' => memory_get_peak_usage(true)
            ]);
            return $result;
        } catch (\Exception $e) {
            $this->endOperation($operationId, [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Log performance metric
     * @param string $operation
     * @param float $executionTime
     * @param int $memoryUsed
     * @param array $context
     */
    protected function logPerformanceMetric($operation, $executionTime, $memoryUsed, $context = [])
    {
        $logData = [
            'operation' => $operation,
            'execution_time' => $executionTime,
            'memory_used' => $memoryUsed,
            'memory_peak' => memory_get_peak_usage(true),
            'context' => $context,
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => Yii::$app->request->getUrl(),
            'user_id' => Yii::$app->user->id ?? 'guest',
        ];
        
        Yii::info(json_encode($logData), 'performance');
    }

    /**
     * Get current memory usage
     * @return array
     */
    public function getMemoryUsage()
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Get performance statistics
     * @param string $operation
     * @param int $hours
     * @return array
     */
    public function getPerformanceStats($operation = null, $hours = 24)
    {
        $cache = Yii::$app->cache;
        $cacheKey = "performance_stats_{$operation}_{$hours}";
        
        return $cache->getOrSet($cacheKey, function() use ($operation, $hours) {
            // This would typically query a performance log table
            // For now, return basic stats
            return [
                'total_operations' => 0,
                'average_execution_time' => 0,
                'max_execution_time' => 0,
                'min_execution_time' => 0,
                'operations_over_threshold' => 0,
            ];
        }, 300); // Cache for 5 minutes
    }

    /**
     * Clean old performance logs
     * @param int $days
     */
    public function cleanOldLogs($days = 7)
    {
        // Implementation would clean old performance logs
        // This is a placeholder
    }
}
