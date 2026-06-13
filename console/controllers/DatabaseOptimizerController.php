<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use common\components\DatabaseOptimizer;

/**
 * Database Optimizer Console Controller
 */
class DatabaseOptimizerController extends Controller
{
    /**
     * Create database indexes for better performance
     */
    public function actionCreateIndexes()
    {
        $this->stdout("Creating database indexes...\n");
        
        try {
            DatabaseOptimizer::createIndexes();
            $this->stdout("Database indexes created successfully!\n", \yii\helpers\Console::FG_GREEN);
        } catch (\Exception $e) {
            $this->stderr("Error creating indexes: " . $e->getMessage() . "\n", \yii\helpers\Console::FG_RED);
            return 1;
        }
        
        return 0;
    }

    /**
     * Analyze query performance
     * @param string $sql SQL query to analyze
     */
    public function actionAnalyzeQuery($sql)
    {
        if (empty($sql)) {
            $this->stderr("Please provide a SQL query to analyze.\n", \yii\helpers\Console::FG_RED);
            return 1;
        }
        
        try {
            $result = DatabaseOptimizer::analyzeQuery($sql);
            $this->stdout("Query analysis results:\n", \yii\helpers\Console::FG_GREEN);
            $this->stdout(print_r($result, true) . "\n");
        } catch (\Exception $e) {
            $this->stderr("Error analyzing query: " . $e->getMessage() . "\n", \yii\helpers\Console::FG_RED);
            return 1;
        }
        
        return 0;
    }

    /**
     * Warm up cache
     */
    public function actionWarmUpCache()
    {
        $this->stdout("Warming up cache...\n");
        
        try {
            $cacheHelper = new \common\components\CacheHelper();
            $cacheHelper->warmUpCache();
            $this->stdout("Cache warmed up successfully!\n", \yii\helpers\Console::FG_GREEN);
        } catch (\Exception $e) {
            $this->stderr("Error warming up cache: " . $e->getMessage() . "\n", \yii\helpers\Console::FG_RED);
            return 1;
        }
        
        return 0;
    }
}
