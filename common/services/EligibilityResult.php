<?php

namespace common\services;

/**
 * Result of an eligibility evaluation.
 */
class EligibilityResult
{
    /** @var bool */
    public $eligible = false;

    /** @var int 0–100 */
    public $matchScore = 0;

    /** @var string eligible|not_eligible|best_fit|partial */
    public $badge = 'not_eligible';

    /** @var array<int, array{code: string, message: string}> */
    public $reasons = [];

    /** @var array<int, array{label: string, met: bool}> */
    public $requirements = [];

    public function addReason(string $code, string $message): void
    {
        $this->reasons[] = ['code' => $code, 'message' => $message];
        $this->eligible = false;
        $this->badge = 'not_eligible';
    }

    public function addRequirement(string $label, bool $met): void
    {
        $this->requirements[] = ['label' => $label, 'met' => $met];
    }

    public function markEligible(int $matchScore): void
    {
        $this->eligible = true;
        $this->applyMatchScore($matchScore);
    }

    /**
     * Sets display match score and badge without changing apply eligibility.
     */
    public function applyMatchScore(int $matchScore): void
    {
        $this->matchScore = max(0, min(100, $matchScore));
        if ($this->matchScore >= 90) {
            $this->badge = 'best_fit';
        } elseif ($this->matchScore >= 70) {
            $this->badge = 'eligible';
        } elseif ($this->matchScore >= 40) {
            $this->badge = 'partial';
        } else {
            $this->badge = 'not_eligible';
        }
    }

    public function getPrimaryMessage(): string
    {
        if ($this->eligible) {
            return 'You meet the eligibility requirements for this opportunity.';
        }
        return $this->reasons[0]['message'] ?? 'You are not eligible to apply for this opportunity.';
    }

    public function toArray(): array
    {
        return [
            'eligible' => $this->eligible,
            'match_score' => $this->matchScore,
            'badge' => $this->badge,
            'reasons' => $this->reasons,
            'requirements' => $this->requirements,
            'message' => $this->getPrimaryMessage(),
        ];
    }
}
