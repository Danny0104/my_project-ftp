<?php

namespace common\services;

use common\models\Application;
use common\models\FieldOfStudy;
use common\models\Position;
use common\models\Student;
use yii\db\Expression;

class OpportunityRecommendationService
{
    private EligibilityService $eligibility;
    private PublicPositionService $publicPositions;

    public function __construct(?EligibilityService $eligibility = null, ?PublicPositionService $publicPositions = null)
    {
        $this->eligibility = $eligibility ?? new EligibilityService();
        $this->publicPositions = $publicPositions ?? new PublicPositionService();
    }

    /**
     * @param int[] $excludePositionIds Already-applied position IDs
     * @return Position[]
     */
    public function forYou(Student $student, array $excludePositionIds = [], int $limit = 4): array
    {
        $query = $this->publicPositions->applyOpenListingFilters(
            Position::find()->alias('position')->with('organization'),
            'position'
        )->orderBy(['position.created_at' => SORT_DESC])->limit(60);

        $query = $this->eligibility->applyListingFilter($query, $student);

        if ($excludePositionIds) {
            $query->andWhere(['not in', 'position.id', $excludePositionIds]);
        }

        $scored = [];
        foreach ($query->all() as $position) {
            $score = $this->eligibility->computeFitScore($student, $position);
            if ($score >= 60) {
                $scored[] = ['position' => $position, 'score' => $score];
            }
        }

        usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_map(
            static fn(array $row): Position => $row['position'],
            array_slice($scored, 0, $limit)
        );
    }

    /**
     * @return Position[]
     */
    public function trending(int $limit = 5): array
    {
        $query = $this->publicPositions->applyOpenListingFilters(
            Position::find()->alias('p')->with('organization'),
            'p'
        )
            ->select(['p.*'])
            ->addSelect(['app_count' => new Expression('COUNT(a.id)')])
            ->leftJoin(
                ['a' => Application::tableName()],
                'a.position_id = p.id AND a.status NOT IN (:withdrawn, :rejected)',
                [':withdrawn' => Application::STATUS_WITHDRAWN, ':rejected' => Application::STATUS_REJECTED]
            )
            ->groupBy('p.id')
            ->orderBy(['app_count' => SORT_DESC, 'p.created_at' => SORT_DESC])
            ->limit($limit);

        return $query->all();
    }

    /**
     * @return array<int, array{position: Position, deadline: int, days: int|null, label: string}>
     */
    public function closingSoon(int $limit = 3): array
    {
        $positions = $this->publicPositions->openListingQuery()->all();

        $items = [];
        foreach ($positions as $position) {
            $deadline = $this->publicPositions->effectiveDeadlineTimestamp($position);
            $days = $this->daysUntilDeadline($deadline);
            if ($days !== null && $days <= 14) {
                $items[] = [
                    'position' => $position,
                    'deadline' => $deadline,
                    'days' => $days,
                    'label' => $this->deadlineLabel($deadline),
                ];
            }
        }

        usort($items, static fn(array $a, array $b): int => $a['deadline'] <=> $b['deadline']);

        return array_slice($items, 0, $limit);
    }

    /**
     * @return array<string, string> key => label
     */
    public function distinctCategories(): array
    {
        $categories = [];
        $rows = $this->publicPositions->openListingQuery()
            ->select(['p.category', 'p.field_of_study'])
            ->asArray()
            ->all();

        foreach ($rows as $row) {
            if (!empty($row['category'])) {
                $key = $this->normalizeCategoryKey((string) $row['category']);
                $categories[$key] = (string) $row['category'];
                continue;
            }

            if (empty($row['field_of_study'])) {
                continue;
            }

            foreach (array_map('trim', explode(',', (string) $row['field_of_study'])) as $part) {
                if ($part === '') {
                    continue;
                }
                $resolved = FieldOfStudy::resolve($part);
                $label = $resolved ? $resolved->name : $part;
                $key = $this->normalizeCategoryKey($resolved ? $resolved->category : $label);
                if ($key !== '') {
                    $categories[$key] = $label;
                }
            }
        }

        ksort($categories);

        return $categories;
    }

    public function daysUntilDeadline(int $timestamp): ?int
    {
        if ($timestamp <= time()) {
            return null;
        }

        $today = new \DateTimeImmutable('today');
        $deadlineDay = (new \DateTimeImmutable())->setTimestamp($timestamp)->setTime(0, 0);

        return (int) $today->diff($deadlineDay)->days;
    }

    public function deadlineLabel(int $timestamp): string
    {
        if ($timestamp <= time()) {
            return 'Closed';
        }

        $secondsLeft = $timestamp - time();
        if ($secondsLeft < 86400) {
            return 'Ends today';
        }

        $days = $this->daysUntilDeadline($timestamp);
        if ($days === 1) {
            return '1 day left';
        }
        if ($days !== null && $days > 0) {
            return $days . ' days left';
        }

        return 'Less than 1 day remaining';
    }

    private function normalizeCategoryKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/\s+/', '-', $value) ?? '';

        return $value;
    }
}
