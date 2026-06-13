<?php

use common\models\Position;
use common\services\PublicPositionService;

/**
 * Helpers for the public internships marketplace.
 */

function pmOrgInitials(?string $name): string
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

function pmPublicService(): PublicPositionService
{
    static $service = null;
    if ($service === null) {
        $service = new PublicPositionService();
    }

    return $service;
}

function pmWorkMode(?string $location): string
{
    return pmPublicService()->workModeKey($location);
}

function pmDeadlineMeta(Position $position): array
{
    return pmPublicService()->deadlineMeta($position);
}

function pmFormatDeadline(Position $position): string
{
    $meta = pmDeadlineMeta($position);

    return Yii::$app->formatter->asDate($meta['timestamp'], 'medium');
}

function pmIsPaid(Position $position): bool
{
    return pmPublicService()->isPaid($position);
}

function pmSkillsList(?string $skills): array
{
    return pmPublicService()->skillsList($skills);
}

function pmPublicBadgeLabel(string $key, ?array $deadlineMeta = null): string
{
    return match ($key) {
        'open' => 'Open',
        'closed' => 'Closed',
        'verified' => 'Verified',
        'remote' => 'Remote',
        'hybrid' => 'Hybrid',
        'on-site' => 'On-site',
        'paid' => 'Paid',
        'new' => 'New',
        'closing_soon' => $deadlineMeta['label'] ?? 'Closing soon',
        default => ucfirst(str_replace('_', ' ', $key)),
    };
}
