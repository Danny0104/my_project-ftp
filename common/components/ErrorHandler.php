<?php

namespace common\components;

use Yii;
use yii\base\Component;
use yii\base\ErrorException;
use yii\web\HttpException;
use yii\db\Exception as DbException;
use Exception;

/**
 * Enhanced Error Handler Component
 * Provides comprehensive error handling and logging
 */
class ErrorHandler extends Component
{
    /**
     * @var array Error categories
     */
    public $categories = [
        'application' => 'Application Errors',
        'database' => 'Database Errors',
        'security' => 'Security Events',
        'performance' => 'Performance Issues',
        'user' => 'User Actions',
        'system' => 'System Events',
    ];

    /**
     * @var array Error severity levels
     */
    public $severityLevels = [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];

    /**
     * Handle and log application errors
     * @param Exception $exception
     * @param string $category
     * @param string $severity
     */
    public function handleError($exception, $category = 'application', $severity = 'error')
    {
        $errorData = $this->prepareErrorData($exception);
        $this->logError($errorData, $category, $severity);
        $this->notifyAdmins($errorData, $severity);
    }

    /**
     * Handle database errors
     * @param DbException $exception
     */
    public function handleDatabaseError($exception)
    {
        $errorData = $this->prepareErrorData($exception);
        $errorData['query'] = $exception->getMessage();
        $errorData['sql'] = $exception->getMessage();
        
        $this->logError($errorData, 'database', 'error');
        
        // Don't notify for common database errors
        if (!$this->isCommonDatabaseError($exception)) {
            $this->notifyAdmins($errorData, 'error');
        }
    }

    /**
     * Handle security events
     * @param string $event
     * @param array $data
     * @param string $severity
     */
    public function handleSecurityEvent($event, $data = [], $severity = 'warning')
    {
        $errorData = [
            'event' => $event,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => Yii::$app->request->getUserIP(),
            'user_agent' => Yii::$app->request->getUserAgent(),
            'user_id' => Yii::$app->user->id ?? 'guest',
        ];
        
        $this->logError($errorData, 'security', $severity);
        
        // Notify for critical security events
        if (in_array($severity, ['emergency', 'alert', 'critical'])) {
            $this->notifyAdmins($errorData, $severity);
        }
    }

    /**
     * Handle performance issues
     * @param string $operation
     * @param float $executionTime
     * @param array $context
     */
    public function handlePerformanceIssue($operation, $executionTime, $context = [])
    {
        $threshold = 2.0; // 2 seconds threshold
        
        if ($executionTime > $threshold) {
            $errorData = [
                'operation' => $operation,
                'execution_time' => $executionTime,
                'context' => $context,
                'timestamp' => date('Y-m-d H:i:s'),
            ];
            
            $severity = $executionTime > 5.0 ? 'warning' : 'info';
            $this->logError($errorData, 'performance', $severity);
        }
    }

    /**
     * Prepare error data for logging
     * @param Exception $exception
     * @return array
     */
    protected function prepareErrorData($exception)
    {
        return [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => Yii::$app->request->getUserIP(),
            'user_agent' => Yii::$app->request->getUserAgent(),
            'user_id' => Yii::$app->user->id ?? 'guest',
            'url' => Yii::$app->request->getUrl(),
            'method' => Yii::$app->request->getMethod(),
            'referrer' => Yii::$app->request->getReferrer(),
        ];
    }

    /**
     * Log error to appropriate channels
     * @param array $errorData
     * @param string $category
     * @param string $severity
     */
    protected function logError($errorData, $category, $severity)
    {
        $logMessage = $this->formatLogMessage($errorData, $category, $severity);
        
        // Log to Yii logger
        Yii::$error($logMessage, $category);
        
        // Log to custom log file
        $this->logToFile($logMessage, $category, $severity);
        
        // Log to database if critical
        if (in_array($severity, ['emergency', 'alert', 'critical', 'error'])) {
            $this->logToDatabase($errorData, $category, $severity);
        }
    }

    /**
     * Format log message
     * @param array $errorData
     * @param string $category
     * @param string $severity
     * @return string
     */
    protected function formatLogMessage($errorData, $category, $severity)
    {
        $message = "[{$severity}] [{$category}] ";
        
        if (isset($errorData['event'])) {
            $message .= "Event: {$errorData['event']}";
        } elseif (isset($errorData['message'])) {
            $message .= "Error: {$errorData['message']}";
        } else {
            $message .= "Unknown error";
        }
        
        $message .= " | User: {$errorData['user_id']} | IP: {$errorData['ip']}";
        
        if (isset($errorData['file'])) {
            $message .= " | File: {$errorData['file']}:{$errorData['line']}";
        }
        
        return $message;
    }

    /**
     * Log to file
     * @param string $message
     * @param string $category
     * @param string $severity
     */
    protected function logToFile($message, $category, $severity)
    {
        $logDir = Yii::getAlias('@runtime/logs');
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $filename = $logDir . '/' . $category . '_' . date('Y-m-d') . '.log';
        $logEntry = date('Y-m-d H:i:s') . " [{$severity}] " . $message . PHP_EOL;
        
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log to database
     * @param array $errorData
     * @param string $category
     * @param string $severity
     */
    protected function logToDatabase($errorData, $category, $severity)
    {
        try {
            $db = Yii::$app->db;
            $db->createCommand()->insert('error_log', [
                'category' => $category,
                'severity' => $severity,
                'message' => $errorData['message'] ?? 'Unknown error',
                'data' => json_encode($errorData),
                'user_id' => $errorData['user_id'],
                'ip_address' => $errorData['ip'],
                'user_agent' => $errorData['user_agent'],
                'created_at' => time(),
            ])->execute();
        } catch (Exception $e) {
            // Fallback to file logging if database fails
            Yii::error("Failed to log to database: " . $e->getMessage(), 'error_handler');
        }
    }

    /**
     * Notify administrators of critical errors
     * @param array $errorData
     * @param string $severity
     */
    protected function notifyAdmins($errorData, $severity)
    {
        if (!in_array($severity, ['emergency', 'alert', 'critical'])) {
            return;
        }
        
        try {
            $adminEmails = $this->getAdminEmails();
            $subject = "[{$severity}] System Error - " . ($errorData['message'] ?? 'Unknown error');
            $body = $this->formatEmailBody($errorData, $severity);
            
            foreach ($adminEmails as $email) {
                Yii::$app->mailer->compose()
                    ->setTo($email)
                    ->setSubject($subject)
                    ->setTextBody($body)
                    ->send();
            }
        } catch (Exception $e) {
            Yii::error("Failed to send admin notification: " . $e->getMessage(), 'error_handler');
        }
    }

    /**
     * Get admin email addresses
     * @return array
     */
    protected function getAdminEmails()
    {
        try {
            return \common\models\Admin::find()
                ->select('email')
                ->where(['status' => 1])
                ->column();
        } catch (Exception $e) {
            return [Yii::$app->params['adminEmail'] ?? 'admin@example.com'];
        }
    }

    /**
     * Format email body
     * @param array $errorData
     * @param string $severity
     * @return string
     */
    protected function formatEmailBody($errorData, $severity)
    {
        $body = "A {$severity} level error has occurred in the system.\n\n";
        $body .= "Error Details:\n";
        $body .= "Message: " . ($errorData['message'] ?? 'Unknown error') . "\n";
        $body .= "Time: " . $errorData['timestamp'] . "\n";
        $body .= "User: " . $errorData['user_id'] . "\n";
        $body .= "IP: " . $errorData['ip'] . "\n";
        $body .= "URL: " . ($errorData['url'] ?? 'N/A') . "\n";
        
        if (isset($errorData['file'])) {
            $body .= "File: " . $errorData['file'] . ":" . $errorData['line'] . "\n";
        }
        
        $body .= "\nStack Trace:\n" . ($errorData['trace'] ?? 'N/A');
        
        return $body;
    }

    /**
     * Check if it's a common database error
     * @param DbException $exception
     * @return bool
     */
    protected function isCommonDatabaseError($exception)
    {
        $commonErrors = [
            'Connection refused',
            'Connection timed out',
            'Table doesn\'t exist',
            'Column doesn\'t exist',
        ];
        
        $message = $exception->getMessage();
        foreach ($commonErrors as $error) {
            if (strpos($message, $error) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get error statistics
     * @param string $category
     * @param int $days
     * @return array
     */
    public function getErrorStats($category = null, $days = 7)
    {
        $query = \common\models\ErrorLog::find()
            ->where(['>=', 'created_at', time() - ($days * 86400)]);
        
        if ($category) {
            $query->andWhere(['category' => $category]);
        }
        
        $stats = $query->select([
            'category',
            'severity',
            'COUNT(*) as count'
        ])
        ->groupBy(['category', 'severity'])
        ->asArray()
        ->all();
        
        return $stats;
    }

    /**
     * Clean old error logs
     * @param int $days
     */
    public function cleanOldLogs($days = 30)
    {
        $cutoffTime = time() - ($days * 86400);
        
        try {
            \common\models\ErrorLog::deleteAll(['<', 'created_at', $cutoffTime]);
        } catch (Exception $e) {
            Yii::error("Failed to clean old logs: " . $e->getMessage(), 'error_handler');
        }
    }
}
