<?php

namespace common\services;

use common\models\Application;
use common\models\Notification;
use common\models\OrgInterview;
use common\models\Organization;
use common\models\Student;
use Yii;
use yii\db\IntegrityException;

/**
 * Centralized interview scheduling with duplicate prevention.
 */
class OrgInterviewScheduleService
{
    public const STAGE_DEFAULT = 'interview';

    public static function normalizeStage(?string $stage): string
    {
        $stage = strtolower(trim((string) $stage));
        if ($stage === '') {
            return self::STAGE_DEFAULT;
        }
        $normalized = preg_replace('/[^a-z0-9_\-]/', '', $stage);

        return $normalized !== '' ? $normalized : self::STAGE_DEFAULT;
    }

    /**
     * Find a non-cancelled interview for the same application scope.
     */
    public static function findExisting(
        int $organizationId,
        int $studentId,
        ?int $positionId,
        ?int $applicationId,
        string $stage
    ): ?OrgInterview {
        $stage = self::normalizeStage($stage);

        $query = OrgInterview::find()
            ->where([
                'organization_id' => $organizationId,
                'student_id' => $studentId,
                'interview_stage' => $stage,
            ])
            ->andWhere(['not', ['status' => OrgInterview::STATUS_CANCELLED]]);

        if ($applicationId !== null) {
            $query->andWhere(['application_id' => $applicationId]);
        } else {
            $query->andWhere(['application_id' => null]);
        }

        if ($positionId !== null) {
            $query->andWhere(['position_id' => $positionId]);
        } else {
            $query->andWhere(['position_id' => null]);
        }

        return $query->orderBy(['id' => SORT_ASC])->one();
    }

    /**
     * @return array{success:bool,message:string,interview:?OrgInterview,already_exists:bool}
     */
    public function scheduleForApplication(
        Application $application,
        int $organizationId,
        array $options = []
    ): array {
        $stage = self::normalizeStage($options['interview_stage'] ?? self::STAGE_DEFAULT);
        $studentId = (int) $application->student_id;
        $positionId = (int) $application->position_id;
        $applicationId = (int) $application->id;

        $existing = self::findExisting($organizationId, $studentId, $positionId, $applicationId, $stage);
        if ($existing) {
            return [
                'success' => true,
                'message' => 'Interview already scheduled.',
                'interview' => $existing,
                'already_exists' => true,
            ];
        }

        $interview = new OrgInterview();
        $interview->scenario = OrgInterview::SCENARIO_SCHEDULE;
        $interview->organization_id = $organizationId;
        $interview->application_id = $applicationId;
        $interview->student_id = $studentId;
        $interview->position_id = $positionId;
        $interview->interview_stage = $stage;
        $interview->title = trim((string) ($options['title'] ?? ''));
        if ($interview->title === '') {
            $interview->title = 'Interview — ' . ($application->position->title ?? 'Candidate');
        }
        $interview->scheduled_at = (int) ($options['scheduled_at'] ?? strtotime('+3 days'));
        $interview->meeting_link = trim((string) ($options['meeting_link'] ?? ''));
        $interview->interviewer_name = trim((string) ($options['interviewer_name'] ?? ''));
        $interview->status = OrgInterview::STATUS_SCHEDULED;

        try {
            if (!$interview->save()) {
                $existingAfterSave = self::findExisting($organizationId, $studentId, $positionId, $applicationId, $stage);
                if ($existingAfterSave) {
                    return [
                        'success' => true,
                        'message' => 'Interview already scheduled.',
                        'interview' => $existingAfterSave,
                        'already_exists' => true,
                    ];
                }

                return [
                    'success' => false,
                    'message' => 'Could not schedule interview.',
                    'interview' => null,
                    'already_exists' => false,
                ];
            }
        } catch (IntegrityException $e) {
            $existingAfterSave = self::findExisting($organizationId, $studentId, $positionId, $applicationId, $stage);
            if ($existingAfterSave) {
                return [
                    'success' => true,
                    'message' => 'Interview already scheduled.',
                    'interview' => $existingAfterSave,
                    'already_exists' => true,
                ];
            }

            throw $e;
        }

        self::notifyStudentInterview($interview, $organizationId, false);

        return [
            'success' => true,
            'message' => 'Interview scheduled successfully.',
            'interview' => $interview,
            'already_exists' => false,
        ];
    }

    public static function notifyStudentInterview(
        OrgInterview $interview,
        int $organizationId,
        bool $isCancellation = false,
        bool $isReschedule = false
    ): void {
        $student = $interview->student ?? Student::findOne((int) $interview->student_id);
        if (!$student || !$student->user_id) {
            return;
        }

        $org = Organization::findOne($organizationId);
        $orgName = $org ? $org->name : 'Organization';
        $when = $interview->scheduled_at
            ? Yii::$app->formatter->asDatetime($interview->scheduled_at)
            : 'TBD';

        if ($isCancellation) {
            $title = 'Interview cancelled';
            $message = sprintf(
                '%s cancelled your interview "%s" that was scheduled for %s.',
                $orgName,
                $interview->title,
                $when
            );
        } elseif ($isReschedule) {
            $title = 'Interview rescheduled';
            $message = sprintf(
                '%s rescheduled your interview "%s" to %s.',
                $orgName,
                $interview->title,
                $when
            );
        } else {
            $title = 'Interview scheduled';
            $message = sprintf(
                '%s scheduled an interview "%s" on %s.',
                $orgName,
                $interview->title,
                $when
            );
        }

        Notification::createFromOrganization(
            (int) $student->user_id,
            $title,
            $message,
            $organizationId,
            Yii::$app->urlManager->createUrl(['interview/index']),
            'View interviews',
            [
                'notification_type' => Notification::TYPE_INTERVIEW,
                'category' => Notification::CATEGORY_INTERVIEWS,
                'priority' => Notification::PRIORITY_HIGH,
                'related_id' => (int) $interview->id,
            ]
        );
    }

    /**
     * Schedule from the interviews modal (resolves application when possible).
     *
     * @return array{success:bool,message:string,interview:?OrgInterview,already_exists:bool}
     */
    public function scheduleFromForm(int $organizationId, OrgInterview $model, ?string $rawSchedule = null): array
    {
        if ($rawSchedule !== null && $rawSchedule !== '') {
            $model->scheduled_at = strtotime(str_replace('T', ' ', $rawSchedule)) ?: $model->scheduled_at;
        }

        if (!$model->student_id) {
            return [
                'success' => false,
                'message' => 'Student is required.',
                'interview' => null,
                'already_exists' => false,
            ];
        }

        $model->scenario = OrgInterview::SCENARIO_SCHEDULE;
        $model->organization_id = $organizationId;
        $model->interview_stage = self::normalizeStage($model->interview_stage);
        if (!$model->title) {
            $model->title = 'Interview session';
        }
        $model->status = OrgInterview::STATUS_SCHEDULED;

        if (!$model->application_id) {
            $model->application_id = $this->resolveApplicationId(
                $organizationId,
                (int) $model->student_id,
                $model->position_id ? (int) $model->position_id : null
            );
        }

        if ($model->application_id && !$model->position_id) {
            $application = Application::findOne((int) $model->application_id);
            if ($application) {
                $model->position_id = (int) $application->position_id;
            }
        }

        if (!$model->application_id) {
            return [
                'success' => false,
                'message' => 'No application found for this student and internship. Schedule from the student application timeline instead.',
                'interview' => null,
                'already_exists' => false,
            ];
        }

        $application = Application::findOne((int) $model->application_id);
        if (!$application) {
            return [
                'success' => false,
                'message' => 'Application not found.',
                'interview' => null,
                'already_exists' => false,
            ];
        }

        return $this->scheduleForApplication($application, $organizationId, [
            'interview_stage' => $model->interview_stage,
            'title' => $model->title,
            'scheduled_at' => $model->scheduled_at,
            'meeting_link' => $model->meeting_link,
            'interviewer_name' => $model->interviewer_name,
        ]);
    }

    private function resolveApplicationId(int $organizationId, int $studentId, ?int $positionId): ?int
    {
        $query = (new OrganizationScopeService())
            ->applicationQuery($organizationId)
            ->andWhere(['a.student_id' => $studentId])
            ->orderBy(['a.created_at' => SORT_DESC]);

        if ($positionId) {
            $query->andWhere(['a.position_id' => $positionId]);
        }

        $application = $query->one();

        return $application ? (int) $application->id : null;
    }

    /**
     * @return OrgInterview[]
     */
    public static function listForOrganization(int $organizationId): array
    {
        return OrgInterview::find()
            ->where(['organization_id' => $organizationId])
            ->with(['student.user', 'position', 'application'])
            ->orderBy(['scheduled_at' => SORT_DESC, 'id' => SORT_DESC])
            ->all();
    }
}
