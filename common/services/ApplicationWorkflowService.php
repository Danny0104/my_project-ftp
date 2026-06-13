<?php

namespace common\services;

use common\models\Application;
use common\models\ApplicationStatusHistory;
use common\models\Notification;
use common\models\Organization;
use common\models\Position;
use Yii;

/**
 * ATS application status transitions, history, and student notifications.
 */
class ApplicationWorkflowService
{
    /**
     * @return array<string, string[]>
     */
    public static function transitionMap(): array
    {
        return [
            Application::STATUS_PENDING => [
                Application::STATUS_UNDER_REVIEW,
                Application::STATUS_REJECTED,
            ],
            Application::STATUS_UNDER_REVIEW => [
                Application::STATUS_ORG_APPROVED,
                Application::STATUS_REJECTED,
            ],
            Application::STATUS_ORG_APPROVED => [
                Application::STATUS_UNIVERSITY_APPROVED,
                Application::STATUS_REJECTED,
            ],
            Application::STATUS_UNIVERSITY_APPROVED => [
                Application::STATUS_APPROVED,
                Application::STATUS_REJECTED,
            ],
            Application::STATUS_APPROVED => [
                Application::STATUS_COMPLETED,
                Application::STATUS_REJECTED,
            ],
            Application::STATUS_REJECTED => [],
            Application::STATUS_WITHDRAWN => [],
            Application::STATUS_COMPLETED => [],
        ];
    }

    public static function canTransition(string $fromStatus, string $toStatus): bool
    {
        if ($fromStatus === $toStatus) {
            return false;
        }

        if ($toStatus === Application::STATUS_REJECTED) {
            return !in_array($fromStatus, [
                Application::STATUS_REJECTED,
                Application::STATUS_WITHDRAWN,
                Application::STATUS_COMPLETED,
            ], true);
        }

        $allowed = self::transitionMap()[$fromStatus] ?? [];

        return in_array($toStatus, $allowed, true);
    }

    /**
     * @return array{success:bool,message:string,application:?Application}
     */
    public function updateStatus(
        Application $application,
        string $newStatus,
        int $actorUserId,
        ?int $organizationId = null
    ): array {
        $previousStatus = (string) $application->status;

        if (!self::canTransition($previousStatus, $newStatus)) {
            $labels = Application::getStatusOptions();

            return [
                'success' => false,
                'message' => 'Cannot move from '
                    . ($labels[$previousStatus] ?? $previousStatus)
                    . ' to '
                    . ($labels[$newStatus] ?? $newStatus)
                    . '.',
                'application' => null,
            ];
        }

        $application->status = $newStatus;
        if (!$application->save(false, ['status', 'updated_at'])) {
            return [
                'success' => false,
                'message' => 'Failed to update application status.',
                'application' => null,
            ];
        }

        ApplicationStatusHistory::record(
            (int) $application->id,
            $previousStatus,
            $newStatus,
            $actorUserId,
            $organizationId
        );

        $this->notifyStudent($application, $previousStatus, $newStatus, $organizationId);

        return [
            'success' => true,
            'message' => 'Application moved to ' . (Application::getStatusOptions()[$newStatus] ?? $newStatus) . '.',
            'application' => $application,
        ];
    }

    public function notifyStudent(
        Application $application,
        string $fromStatus,
        string $toStatus,
        ?int $organizationId = null
    ): void {
        if ($fromStatus === $toStatus) {
            return;
        }

        $student = $application->student;
        if (!$student || !$student->user_id) {
            return;
        }

        $position = $application->position ?? Position::findOne((int) $application->position_id);
        $positionTitle = $position ? $position->title : 'your internship application';
        $orgName = 'Organization';
        if ($organizationId) {
            $org = Organization::findOne($organizationId);
            if ($org) {
                $orgName = $org->name;
            }
        }

        $labels = Application::getStatusOptions();
        $statusLabel = $labels[$toStatus] ?? $toStatus;

        $title = 'Application update';
        $message = sprintf(
            '%s updated your application for "%s" to: %s.',
            $orgName,
            $positionTitle,
            $statusLabel
        );
        $priority = Notification::PRIORITY_NORMAL;

        if ($toStatus === Application::STATUS_REJECTED) {
            $title = 'Application not selected';
            $message = sprintf(
                'Your application for "%s" at %s was not selected to move forward.',
                $positionTitle,
                $orgName
            );
            $priority = Notification::PRIORITY_HIGH;
        } elseif ($toStatus === Application::STATUS_ORG_APPROVED) {
            $title = 'You have been shortlisted';
            $message = sprintf('Great news! %s shortlisted your application for "%s".', $orgName, $positionTitle);
        } elseif ($toStatus === Application::STATUS_UNIVERSITY_APPROVED) {
            $title = 'Interview stage';
            $message = sprintf('Your application for "%s" is now in the interview stage with %s.', $positionTitle, $orgName);
        } elseif ($toStatus === Application::STATUS_APPROVED) {
            $title = 'Application accepted';
            $message = sprintf('Congratulations! %s accepted your application for "%s".', $orgName, $positionTitle);
            $priority = Notification::PRIORITY_HIGH;
        }

        Notification::createFromOrganization(
            (int) $student->user_id,
            $title,
            $message,
            (int) ($organizationId ?? 0),
            Yii::$app->urlManager->createUrl(['application/view', 'id' => $application->id]),
            'View application',
            [
                'notification_type' => Notification::TYPE_APPLICATION,
                'category' => Notification::CATEGORY_APPLICATIONS,
                'priority' => $priority,
                'related_id' => (int) $application->id,
            ]
        );
    }
}
