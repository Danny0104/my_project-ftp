<?php

namespace console\controllers;

use common\models\Notification;
use common\models\OrgInterview;
use common\models\Student;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Sends interview reminders for upcoming sessions.
 *
 * Usage: php yii interview-reminder/run
 * Cron:  php yii interview-reminder/run --hours=24
 */
class InterviewReminderController extends Controller
{
    /** @var int Hours before interview to send reminder */
    public $hours = 24;

    public function options($actionID)
    {
        return array_merge(parent::options($actionID), ['hours']);
    }

    public function actionRun()
    {
        $windowEnd = time() + ((int) $this->hours * 3600);

        $interviews = OrgInterview::find()
            ->where([
                'status' => OrgInterview::STATUS_SCHEDULED,
                'reminder_sent' => 0,
            ])
            ->andWhere(['>', 'scheduled_at', time()])
            ->andWhere(['<=', 'scheduled_at', $windowEnd])
            ->all();

        $sent = 0;
        foreach ($interviews as $interview) {
            $student = $interview->student ?? Student::findOne((int) $interview->student_id);
            if (!$student || !$student->user_id) {
                continue;
            }

            $when = Yii::$app->formatter->asDatetime($interview->scheduled_at);
            Notification::createAlert(
                (int) $student->user_id,
                Notification::TYPE_INTERVIEW,
                Notification::CATEGORY_INTERVIEWS,
                'Interview reminder',
                sprintf('Reminder: your interview "%s" is scheduled for %s.', $interview->title, $when),
                [
                    'sender_type' => Notification::SENDER_TYPE_SYSTEM,
                    'priority' => Notification::PRIORITY_HIGH,
                    'related_id' => (int) $interview->id,
                    'action_url' => '/index.php?r=interview/index',
                    'action_text' => 'View interviews',
                ]
            );

            $interview->reminder_sent = 1;
            $interview->save(false, ['reminder_sent', 'updated_at']);
            $sent++;
        }

        $this->stdout("Interview reminders sent: {$sent}\n");

        return ExitCode::OK;
    }
}
