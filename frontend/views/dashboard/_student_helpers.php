<?php
/**
 * Student dashboard helpers
 */

use common\models\Application;
use yii\helpers\Html;

function ftpOrgInitials(?string $name): string
{
    if (!$name) {
        return '?';
    }
    $parts = preg_split('/\s+/', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(mb_substr($part, 0, 1));
    }
    return $initials ?: '?';
}

function ftpIsRemote(?string $location): bool
{
    if (!$location) {
        return false;
    }
    return (bool) preg_match('/remote|virtual|online|work from home/i', $location);
}

function ftpEstimatedDeadline(int $createdAt): int
{
    return $createdAt + (30 * 24 * 60 * 60);
}

function ftpDaysUntil(int $timestamp): int
{
    if ($timestamp <= time()) {
        return 0;
    }

    $today = new \DateTimeImmutable('today');
    $deadlineDay = (new \DateTimeImmutable())->setTimestamp($timestamp)->setTime(0, 0);

    return (int) $today->diff($deadlineDay)->days;
}

function ftpDeadlineLabel(int $timestamp): string
{
    if ($timestamp <= time()) {
        return 'Closed';
    }

    if (($timestamp - time()) < 86400) {
        return 'Ends today';
    }

    $days = ftpDaysUntil($timestamp);
    if ($days === 1) {
        return '1 day left';
    }

    return $days . ' days left';
}

/** @return array{label: string, days: int|null, is_closed: bool, is_urgent: bool} */
function ftpDeadlineMeta(int $timestamp): array
{
    $isClosed = $timestamp <= time();
    if ($isClosed) {
        return [
            'label' => 'Closed',
            'days' => null,
            'is_closed' => true,
            'is_urgent' => false,
        ];
    }

    $days = ftpDaysUntil($timestamp);

    return [
        'label' => ftpDeadlineLabel($timestamp),
        'days' => $days,
        'is_closed' => false,
        'is_urgent' => $days <= 7,
    ];
}

/**
 * Canonical deadline metadata for a position (uses PublicPositionService).
 *
 * @return array{label: string, days: int|null, is_closed: bool, is_urgent: bool, timestamp: int}
 */
function ftpPositionDeadlineMeta(\common\models\Position $position): array
{
    $service = new \common\services\PublicPositionService();
    $meta = $service->deadlineMeta($position);

    return [
        'label' => $meta['label'],
        'days' => $meta['days'],
        'is_closed' => $meta['is_closed'],
        'is_urgent' => $meta['is_urgent'],
        'timestamp' => $meta['timestamp'],
    ];
}

function ftpTimelineState(Application $app): array
{
    $steps = [
        ['key' => 'applied', 'label' => 'Applied'],
        ['key' => 'review', 'label' => 'Review'],
        ['key' => 'interview', 'label' => 'Interview'],
        ['key' => 'accepted', 'label' => 'Accepted'],
    ];

    $activeIndex = 0;
    $isRejected = $app->status === Application::STATUS_REJECTED;
    $isWithdrawn = $app->status === Application::STATUS_WITHDRAWN;

    switch ($app->status) {
        case Application::STATUS_PENDING:
            $activeIndex = 0;
            break;
        case Application::STATUS_UNDER_REVIEW:
            $activeIndex = 2;
            break;
        case Application::STATUS_APPROVED:
            $activeIndex = 3;
            break;
        case Application::STATUS_REJECTED:
            $activeIndex = 2;
            break;
        default:
            $activeIndex = 0;
    }

    return [
        'steps' => $steps,
        'activeIndex' => $activeIndex,
        'isRejected' => $isRejected,
        'isWithdrawn' => $isWithdrawn,
    ];
}

function spAppStatusKey(string $status): string
{
    switch ($status) {
        case Application::STATUS_PENDING:
            return 'pending';
        case Application::STATUS_UNDER_REVIEW:
            return 'review';
        case Application::STATUS_ORG_APPROVED:
        case Application::STATUS_UNIVERSITY_APPROVED:
            return 'interview';
        case Application::STATUS_APPROVED:
        case Application::STATUS_COMPLETED:
            return 'approved';
        case Application::STATUS_REJECTED:
            return 'rejected';
        case Application::STATUS_WITHDRAWN:
            return 'withdrawn';
        default:
            return 'pending';
    }
}

function ftpStatusPillClass(string $status): string
{
    switch ($status) {
        case Application::STATUS_PENDING:
            return 'pending';
        case Application::STATUS_UNDER_REVIEW:
            return 'review';
        case Application::STATUS_APPROVED:
            return 'approved';
        case Application::STATUS_REJECTED:
            return 'rejected';
        default:
            return 'withdrawn';
    }
}

/**
 * Kanban column key for student application tracker UI.
 */
function spAppKanbanColumn(string $status): string
{
    switch ($status) {
        case Application::STATUS_PENDING:
            return 'submitted';
        case Application::STATUS_UNDER_REVIEW:
            return 'review';
        case Application::STATUS_ORG_APPROVED:
        case Application::STATUS_UNIVERSITY_APPROVED:
            return 'interview';
        case Application::STATUS_APPROVED:
        case Application::STATUS_COMPLETED:
            return 'accepted';
        case Application::STATUS_REJECTED:
            return 'rejected';
        case Application::STATUS_WITHDRAWN:
            return 'draft';
        default:
            return 'submitted';
    }
}

/**
 * Premium journey stage for applications page pipeline UI.
 */
function spAppJourneyStage(string $status): string
{
    switch ($status) {
        case Application::STATUS_PENDING:
            return 'applied';
        case Application::STATUS_UNDER_REVIEW:
            return 'review';
        case Application::STATUS_ORG_APPROVED:
            return 'shortlisted';
        case Application::STATUS_UNIVERSITY_APPROVED:
            return 'interview';
        case Application::STATUS_APPROVED:
        case Application::STATUS_COMPLETED:
            return 'accepted';
        case Application::STATUS_REJECTED:
            return 'rejected';
        case Application::STATUS_WITHDRAWN:
            return 'withdrawn';
        default:
            return 'applied';
    }
}

/** @return array{steps: list<array{key: string, label: string}>, index: int} */
function spAppJourneyTimeline(string $status): array
{
    $steps = [
        ['key' => 'applied', 'label' => 'Applied'],
        ['key' => 'review', 'label' => 'Under Review'],
        ['key' => 'shortlisted', 'label' => 'Shortlisted'],
        ['key' => 'interview', 'label' => 'Interview'],
        ['key' => 'accepted', 'label' => 'Accepted'],
    ];
    $stage = spAppJourneyStage($status);
    $index = 0;
    foreach ($steps as $i => $step) {
        if ($step['key'] === $stage) {
            $index = $i;
            break;
        }
        if ($status === Application::STATUS_REJECTED && $step['key'] === 'interview') {
            $index = $i;
        }
    }
    if ($status === Application::STATUS_REJECTED) {
        $index = min($index, 3);
    }
    return ['steps' => $steps, 'index' => $index, 'stage' => $stage];
}

function ftpRelativeTime(int $timestamp): string
{
    $diff = time() - $timestamp;
    if ($diff < 3600) {
        return max(1, (int) floor($diff / 60)) . 'm ago';
    }
    if ($diff < 86400) {
        return (int) floor($diff / 3600) . 'h ago';
    }
    if ($diff < 604800) {
        return (int) floor($diff / 86400) . 'd ago';
    }
    return date('M j', $timestamp);
}
