<?php

namespace console\controllers;

use common\services\EmailQueueService;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Process outbound email queue.
 *
 * Usage: php yii email-queue/process
 * Cron:  */5 * * * * php yii email-queue/process
 */
class EmailQueueController extends Controller
{
    public $limit = 50;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['limit']);
    }

    public function actionProcess()
    {
        $sent = (new EmailQueueService())->processBatch((int) $this->limit);
        $this->stdout("Emails sent: {$sent}\n");
        return ExitCode::OK;
    }
}
