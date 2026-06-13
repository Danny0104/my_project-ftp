<?php

namespace common\services;

use common\models\Application;
use common\models\Organization;
use common\models\Position;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\db\Query;

/**
 * Public marketplace visibility, deadlines, and applicant metrics.
 */
class PublicPositionService
{
    public const ESTIMATED_DEADLINE_DAYS = 30;
    public const CLOSING_SOON_DAYS = 7;

    public function effectiveDeadlineTimestamp(Position $position): int
    {
        if (!empty($position->application_deadline)) {
            return (int) $position->application_deadline;
        }

        return (int) $position->created_at + (self::ESTIMATED_DEADLINE_DAYS * 86400);
    }

    public function isDeadlinePassed(Position $position): bool
    {
        return time() > $this->effectiveDeadlineTimestamp($position);
    }

    /**
     * @return array{
     *   timestamp: int,
     *   label: string,
     *   days: int|null,
     *   is_closed: bool,
     *   is_urgent: bool,
     *   is_closing_soon: bool,
     *   badge: string|null
     * }
     */
    public function deadlineMeta(Position $position): array
    {
        $timestamp = $this->effectiveDeadlineTimestamp($position);
        $isClosed = $timestamp <= time();

        if ($isClosed) {
            return [
                'timestamp' => $timestamp,
                'label' => 'Closed',
                'days' => null,
                'is_closed' => true,
                'is_urgent' => false,
                'is_closing_soon' => false,
                'badge' => 'Closed',
            ];
        }

        $secondsLeft = $timestamp - time();
        if ($secondsLeft < 86400) {
            return [
                'timestamp' => $timestamp,
                'label' => 'Ends today',
                'days' => 0,
                'is_closed' => false,
                'is_urgent' => true,
                'is_closing_soon' => true,
                'badge' => 'Ends today',
            ];
        }

        $days = $this->calendarDaysUntil($timestamp);
        $label = $days === 1 ? '1 day left' : ($days . ' days left');
        $isClosingSoon = $days <= self::CLOSING_SOON_DAYS;

        return [
            'timestamp' => $timestamp,
            'label' => $label,
            'days' => $days,
            'is_closed' => false,
            'is_urgent' => $isClosingSoon,
            'is_closing_soon' => $isClosingSoon,
            'badge' => $isClosingSoon ? 'Closing soon' : null,
        ];
    }

    public function calendarDaysUntil(int $timestamp): int
    {
        if ($timestamp <= time()) {
            return 0;
        }

        $today = new \DateTimeImmutable('today');
        $deadlineDay = (new \DateTimeImmutable())->setTimestamp($timestamp)->setTime(0, 0);

        return (int) $today->diff($deadlineDay)->days;
    }

    public function isAcceptingApplications(Position $position): bool
    {
        return EligibilityService::normalizePositionStatus((string) $position->status)
            && !$this->isDeadlinePassed($position);
    }

    public function isPubliclyListable(Position $position): bool
    {
        if ($position->status !== Position::STATUS_ACTIVE) {
            return false;
        }

        if ($this->isDeadlinePassed($position)) {
            return false;
        }

        $org = $position->organization;
        if (!$org || !$org->isVerified()) {
            return false;
        }

        return true;
    }

    /**
     * Guests may open active listings; non-active statuses are hidden.
     */
    public function isPubliclyViewable(Position $position, bool $isGuest): bool
    {
        if (!$isGuest) {
            return true;
        }

        return $position->status === Position::STATUS_ACTIVE;
    }

    /**
     * @return string[] User-facing badge keys: open, verified, remote, hybrid, on-site, new, paid, closing_soon, closed
     */
    public function publicBadges(Position $position, ?Organization $org = null): array
    {
        $badges = [];
        $deadline = $this->deadlineMeta($position);
        $org = $org ?? $position->organization;

        if ($deadline['is_closed']) {
            $badges[] = 'closed';
        } else {
            $badges[] = 'open';
            if ($deadline['is_closing_soon']) {
                $badges[] = 'closing_soon';
            }
        }

        if ($org && $org->isVerified()) {
            $badges[] = 'verified';
        }

        $badges[] = $this->workModeKey((string) $position->location);

        if ($this->isPaid($position)) {
            $badges[] = 'paid';
        }

        if ((time() - (int) $position->created_at) < (7 * 86400)) {
            $badges[] = 'new';
        }

        return $badges;
    }

    public function workModeKey(?string $location): string
    {
        if (!$location) {
            return 'on-site';
        }
        if (preg_match('/remote|virtual|online|work from home/i', $location)) {
            return 'remote';
        }
        if (preg_match('/hybrid/i', $location)) {
            return 'hybrid';
        }

        return 'on-site';
    }

    public function workModeLabel(string $key): string
    {
        return match ($key) {
            'remote' => 'Remote',
            'hybrid' => 'Hybrid',
            default => 'On-site',
        };
    }

    public function isPaid(Position $position): bool
    {
        $blob = strtolower(implode(' ', [
            (string) $position->description,
            (string) $position->category,
            (string) $position->criteria,
        ]));

        return (bool) preg_match('/\b(paid|stipend|salary|compensation|remunerat)/i', $blob);
    }

    public function openListingQuery(): ActiveQuery
    {
        return $this->applyOpenListingFilters(
            Position::find()->alias('p')->with(['organization'])
        );
    }

    public function applyOpenListingFilters(ActiveQuery $query, string $alias = 'p'): ActiveQuery
    {
        return $query
            ->innerJoinWith(['organization' => static function (ActiveQuery $orgQuery): void {
                $orgQuery->andWhere(['organization.verification_status' => Organization::VERIFICATION_APPROVED]);
            }])
            ->andWhere(["{$alias}.status" => Position::STATUS_ACTIVE])
            ->andWhere($this->openDeadlineSql($alias));
    }

    public function openDeadlineSql(string $alias = 'p'): Expression
    {
        return new Expression(
            "COALESCE({$alias}.application_deadline, {$alias}.created_at + :deadlineOffset) > :now",
            [
                ':deadlineOffset' => self::ESTIMATED_DEADLINE_DAYS * 86400,
                ':now' => time(),
            ]
        );
    }

    public function countOpenPositions(): int
    {
        return (int) $this->openListingQuery()->count();
    }

    public function countPartnerOrganizations(): int
    {
        return (int) Organization::find()
            ->alias('o')
            ->innerJoin(['p' => Position::tableName()], 'p.organization_id = o.id')
            ->where(['o.verification_status' => Organization::VERIFICATION_APPROVED])
            ->andWhere(['p.status' => Position::STATUS_ACTIVE])
            ->andWhere($this->openDeadlineSql('p'))
            ->select('o.id')
            ->distinct()
            ->count();
    }

    /**
     * @return Organization[]
     */
    public function organizationsWithOpenPositions(): array
    {
        return Organization::find()
            ->alias('o')
            ->innerJoin(['p' => Position::tableName()], 'p.organization_id = o.id')
            ->where(['o.verification_status' => Organization::VERIFICATION_APPROVED])
            ->andWhere(['p.status' => Position::STATUS_ACTIVE])
            ->andWhere($this->openDeadlineSql('p'))
            ->orderBy(['o.name' => SORT_ASC])
            ->groupBy('o.id')
            ->all();
    }

    /**
     * @param int[] $positionIds
     * @return array<int, int>
     */
    public function applicantCountsForPositions(array $positionIds): array
    {
        if ($positionIds === []) {
            return [];
        }

        $rows = (new Query())
            ->select(['position_id', 'cnt' => new Expression('COUNT(*)')])
            ->from(Application::tableName())
            ->where(['position_id' => $positionIds])
            ->andWhere(['not in', 'status', [Application::STATUS_WITHDRAWN]])
            ->groupBy('position_id')
            ->all();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['position_id']] = (int) $row['cnt'];
        }

        return $counts;
    }

    /**
     * @return string[]
     */
    public function skillsList(?string $skills, int $maxVisible = 3): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $skills))));
    }
}
