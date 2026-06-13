<?php

namespace common\services;

use common\models\Application;
use common\models\EligibilityAuditLog;
use common\models\FieldOfStudy;
use common\models\PlatformRegulation;
use common\models\Position;
use common\models\PositionAllowedField;
use common\models\Student;
use Yii;
use yii\db\ActiveQuery;

/**
 * Central academic eligibility & regulation enforcement.
 */
class EligibilityService
{
    public function canApply(Student $student, Position $position): bool
    {
        return $this->evaluate($student, $position)->eligible;
    }

    public function evaluate(Student $student, Position $position, string $auditAction = 'check'): EligibilityResult
    {
        $result = new EligibilityResult();

        $this->checkPositionActive($position, $result);
        if (!empty($result->reasons)) {
            return $this->finalize($student, $position, $result, $auditAction);
        }

        $this->checkRegulations($student, $result);
        $this->checkProfileRequirements($student, $result);
        $this->checkTrainingPeriod($result);
        $this->checkApplicationDeadline($position, $result);
        $this->checkDuplicateApplication($student, $position, $result);
        $this->checkSemesterLimit($student, $result);
        $this->checkGpa($student, $position, $result);
        $this->checkAcademicLevel($student, $position, $result);

        if (!empty($result->reasons)) {
            return $this->finalize($student, $position, $result, $auditAction);
        }

        $fieldScore = $this->checkFieldCompatibility($student, $position, $result);
        if (!empty($result->reasons)) {
            return $this->finalize($student, $position, $result, $auditAction);
        }

        $skillsBonus = $this->skillsMatchBonus($student, $position);
        $finalScore = min(100, $fieldScore + $skillsBonus);
        $result->markEligible($finalScore);

        return $this->finalize($student, $position, $result, $auditAction);
    }

    public function getMatchScore(Student $student, Position $position): int
    {
        return $this->computeFitScore($student, $position);
    }

    /**
     * Browse-time evaluation: fit score for display, apply gates for eligibility flag.
     */
    public function evaluateBrowse(Student $student, Position $position): EligibilityResult
    {
        $result = $this->evaluate($student, $position, 'browse');
        $result->applyMatchScore($this->computeFitScore($student, $position));

        return $result;
    }

    /**
     * Profile/field/skills fit score independent of apply gates (duplicate app, profile %, etc.).
     */
    public function computeFitScore(Student $student, Position $position): int
    {
        if (!self::normalizePositionStatus((string) $position->status)) {
            return 0;
        }

        $fieldScore = $this->computeFieldFitScore($student, $position);
        $skillsBonus = $this->skillsMatchBonus($student, $position);

        return min(100, $fieldScore + $skillsBonus);
    }

    private function computeFieldFitScore(Student $student, Position $position): int
    {
        $allowedFields = $this->getAllowedFieldsForPosition($position);
        $allowedNames = $this->getAllowedFieldNames($position);
        $studentField = FieldOfStudy::resolve($student->field_of_study);

        if (!$studentField) {
            if (!empty($student->field_of_study) && !empty($position->field_of_study)) {
                $studentNorm = strtolower(trim((string) $student->field_of_study));
                foreach (array_map('trim', explode(',', (string) $position->field_of_study)) as $part) {
                    $partNorm = strtolower($part);
                    if ($partNorm === $studentNorm
                        || str_contains($partNorm, $studentNorm)
                        || str_contains($studentNorm, $partNorm)) {
                        return 72;
                    }
                }
            }
            return empty($allowedNames) ? 45 : 35;
        }

        if (empty($allowedFields) && empty($allowedNames)) {
            return 50;
        }

        foreach ($allowedFields as $allowed) {
            if ((int) $allowed->id === (int) $studentField->id) {
                return 95;
            }
        }

        $sameCategory = false;
        foreach ($allowedFields as $allowed) {
            if ($allowed->category === $studentField->category) {
                $sameCategory = true;
                if ($this->fieldsAreRelated($studentField, $allowed)) {
                    return 78;
                }
            }
        }

        if ($sameCategory) {
            return PlatformRegulation::getBool('strict_field_matching', true) ? 68 : 72;
        }

        return 25;
    }

    /**
     * Restrict listing query to positions the student may view as relevant.
     */
    public function applyListingFilter(ActiveQuery $query, Student $student): ActiveQuery
    {
        if (!PlatformRegulation::getBool('strict_field_matching', true)) {
            return $query;
        }

        $studentField = FieldOfStudy::resolve($student->field_of_study);
        if (!$studentField) {
            return $query;
        }

        $allowedPositionIds = (new \yii\db\Query())
            ->select('paf.position_id')
            ->from(['paf' => PositionAllowedField::tableName()])
            ->innerJoin(['fos' => FieldOfStudy::tableName()], 'fos.id = paf.field_of_study_id')
            ->where(['fos.category' => $studentField->category])
            ->column();

        if (empty($allowedPositionIds)) {
            $allowedPositionIds = [-1];
        }

        return $query->andWhere([
            'or',
            ['position.id' => $allowedPositionIds],
            ['like', 'position.field_of_study', $student->field_of_study],
        ]);
    }

    /**
     * @return FieldOfStudy[]
     */
    public function getAllowedFieldsForPosition(Position $position): array
    {
        $ids = PositionAllowedField::find()
            ->select('field_of_study_id')
            ->where(['position_id' => $position->id])
            ->column();

        if (!empty($ids)) {
            return FieldOfStudy::find()->where(['id' => $ids])->all();
        }

        if (empty($position->field_of_study)) {
            return [];
        }

        $fields = [];
        foreach (array_map('trim', explode(',', (string) $position->field_of_study)) as $part) {
            $resolved = FieldOfStudy::resolve($part);
            if ($resolved) {
                $fields[$resolved->id] = $resolved;
            }
        }
        return array_values($fields);
    }

    public function getAllowedFieldNames(Position $position): array
    {
        $allowed = $this->getAllowedFieldsForPosition($position);
        if (!empty($allowed)) {
            return array_map(static fn(FieldOfStudy $f) => $f->name, $allowed);
        }
        if (!empty($position->field_of_study)) {
            return array_map('trim', explode(',', (string) $position->field_of_study));
        }
        return [];
    }

    private function finalize(Student $student, Position $position, EligibilityResult $result, string $action): EligibilityResult
    {
        $skipAudit = in_array($action, ['browse', 'score', 'listing'], true);
        if (!$skipAudit) {
            EligibilityAuditLog::record(
                (int) $student->user_id,
                (int) $student->id,
                (int) $position->id,
                $result->eligible,
                $result->matchScore,
                $result->reasons,
                $action
            );
        }
        return $result;
    }

    private function checkPositionActive(Position $position, EligibilityResult $result): void
    {
        if (!self::normalizePositionStatus((string) $position->status)) {
            $result->addReason('position_inactive', 'This opportunity is not currently accepting applications.');
        }
    }

    private function checkRegulations(Student $student, EligibilityResult $result): void
    {
        if (PlatformRegulation::getBool('require_field_of_study', true) && empty($student->field_of_study)) {
            $result->addReason('missing_field', 'Please set your field of study in your profile before applying.');
        }
    }

    private function checkProfileRequirements(Student $student, EligibilityResult $result): void
    {
        $required = [
            'Student ID' => !empty($student->student_id),
            'University' => !empty($student->university),
            'Field of study' => !empty($student->field_of_study),
        ];

        if (PlatformRegulation::getBool('require_cv', true)) {
            $required['CV / Resume'] = !empty($student->cv);
        }

        foreach ($required as $label => $met) {
            $result->addRequirement($label, $met);
            if (!$met && PlatformRegulation::getBool('require_profile_complete', true)) {
                $result->addReason('profile_incomplete', "Your profile is incomplete. Please add: {$label}.");
                return;
            }
        }

        $completion = $this->profileCompletionPercent($student);
        $minPct = PlatformRegulation::getInt('min_profile_completion_percent', 75);
        $result->addRequirement("Profile completion ({$minPct}%+)", $completion >= $minPct);
        if ($completion < $minPct && PlatformRegulation::getBool('require_profile_complete', true)) {
            $result->addReason(
                'profile_completion',
                "Your profile is {$completion}% complete. A minimum of {$minPct}% is required to apply."
            );
        }
    }

    private function checkTrainingPeriod(EligibilityResult $result): void
    {
        $start = PlatformRegulation::getValue('training_period_start');
        $end = PlatformRegulation::getValue('training_period_end');
        $today = date('Y-m-d');

        if ($start && $today < $start) {
            $result->addReason(
                'training_period',
                'Applications are not open yet. The training application period starts on ' . date('F j, Y', strtotime($start)) . '.'
            );
        }
        if ($end && $today > $end) {
            $result->addReason(
                'training_period',
                'The application period for this semester has ended on ' . date('F j, Y', strtotime($end)) . '.'
            );
        }
    }

    private function checkApplicationDeadline(Position $position, EligibilityResult $result): void
    {
        $publicPositions = new PublicPositionService();
        if ($publicPositions->isDeadlinePassed($position)) {
            $deadline = $publicPositions->effectiveDeadlineTimestamp($position);
            $result->addReason(
                'deadline_passed',
                'The application deadline for this opportunity has passed (' . date('F j, Y', $deadline) . ').'
            );
        }
    }

    private function checkDuplicateApplication(Student $student, Position $position, EligibilityResult $result): void
    {
        $exists = Application::find()
            ->where(['user_id' => $student->user_id, 'position_id' => $position->id])
            ->andWhere(['not in', 'status', [Application::STATUS_WITHDRAWN]])
            ->exists();

        $result->addRequirement('Not already applied', !$exists);
        if ($exists) {
            $result->addReason('duplicate', 'You have already submitted an application for this opportunity.');
        }
    }

    private function checkSemesterLimit(Student $student, EligibilityResult $result): void
    {
        $max = PlatformRegulation::getInt('max_applications_per_semester', 8);
        if ($max <= 0) {
            return;
        }

        $start = PlatformRegulation::getValue('training_period_start');
        $end = PlatformRegulation::getValue('training_period_end');
        $from = $start ? strtotime($start) : strtotime('-6 months');
        $to = $end ? strtotime($end . ' 23:59:59') : time();

        $count = (int) Application::find()
            ->where(['user_id' => $student->user_id])
            ->andWhere(['not in', 'status', [Application::STATUS_WITHDRAWN, Application::STATUS_REJECTED]])
            ->andWhere(['between', 'created_at', $from, $to])
            ->count();

        $result->addRequirement("Semester limit ({$max} applications)", $count < $max);
        if ($count >= $max) {
            $result->addReason(
                'semester_limit',
                "You have reached the maximum of {$max} applications allowed for this training period."
            );
        }
    }

    private function checkGpa(Student $student, Position $position, EligibilityResult $result): void
    {
        $required = $position->min_gpa ?? PlatformRegulation::getValue('min_gpa_default');
        if ($required === null || $required === '') {
            return;
        }
        $required = (float) $required;
        if ($student->gpa === null || $student->gpa === '') {
            $result->addRequirement("Minimum GPA ({$required})", false);
            $result->addReason('gpa_missing', "This opportunity requires a minimum GPA of {$required}. Please update your profile.");
            return;
        }
        $met = (float) $student->gpa >= $required;
        $result->addRequirement("Minimum GPA ({$required})", $met);
        if (!$met) {
            $result->addReason(
                'gpa_low',
                "Your GPA ({$student->gpa}) does not meet the minimum requirement of {$required} for this opportunity."
            );
        }
    }

    private function checkAcademicLevel(Student $student, Position $position, EligibilityResult $result): void
    {
        if (empty($position->academic_level_required) || empty($student->academic_level)) {
            return;
        }
        $met = strtolower($student->academic_level) === strtolower($position->academic_level_required);
        $result->addRequirement('Academic level: ' . $position->academic_level_required, $met);
        if (!$met) {
            $result->addReason(
                'academic_level',
                "This opportunity requires {$position->academic_level_required} level students. Your level: {$student->academic_level}."
            );
        }
    }

    /**
     * Core field-of-study compatibility check. Returns base match score.
     */
    private function checkFieldCompatibility(Student $student, Position $position, EligibilityResult $result): int
    {
        $allowedFields = $this->getAllowedFieldsForPosition($position);
        $allowedNames = $this->getAllowedFieldNames($position);

        if (empty($allowedFields) && empty($allowedNames)) {
            $result->addReason(
                'no_eligibility_defined',
                'This opportunity has no eligible fields of study defined. The organization must specify allowed academic fields before students can apply.'
            );
            return 0;
        }

        $studentField = FieldOfStudy::resolve($student->field_of_study);
        if (!$studentField) {
            $result->addReason(
                'field_unrecognized',
                'Your field of study ("' . $student->field_of_study . '") is not recognized in the academic registry. Please update your profile or contact support.'
            );
            return 0;
        }

        $result->addRequirement('Field of study: ' . $studentField->name, false);

        foreach ($allowedFields as $allowed) {
            if ((int) $allowed->id === (int) $studentField->id) {
                $result->requirements[count($result->requirements) - 1]['met'] = true;
                $result->addRequirement('Matching specialization', true);
                return 95;
            }
        }

        // Same category = partial match (e.g. Data Science → Software Engineering posting)
        $sameCategory = false;
        foreach ($allowedFields as $allowed) {
            if ($allowed->category === $studentField->category) {
                $sameCategory = true;
                break;
            }
        }

        if ($sameCategory && !PlatformRegulation::getBool('strict_field_matching', true)) {
            $result->requirements[count($result->requirements) - 1]['met'] = true;
            $result->addRequirement('Related faculty/category', true);
            return 72;
        }

        if ($sameCategory) {
            // Strict mode: same category but different specialization — check explicit cross-list
            foreach ($allowedFields as $allowed) {
                if ($this->fieldsAreRelated($studentField, $allowed)) {
                    $result->requirements[count($result->requirements) - 1]['met'] = true;
                    return 78;
                }
            }
        }

        $allowedList = implode(', ', $allowedNames);
        $result->addReason(
            'field_mismatch',
            'You cannot apply for this opportunity because your field of study (' . $studentField->name . ') '
            . 'does not match the required field(s): ' . $allowedList . '.'
        );
        return 0;
    }

    private function fieldsAreRelated(FieldOfStudy $student, FieldOfStudy $allowed): bool
    {
        if ($student->category !== $allowed->category) {
            return false;
        }
        // Technology cluster: CS, IT, SE, Data Science, Cybersecurity
        $techCluster = ['computer-science', 'information-technology', 'software-engineering', 'data-science', 'cybersecurity'];
        if (in_array($student->category, ['technology'], true)) {
            return in_array($student->slug, $techCluster, true) && in_array($allowed->slug, $techCluster, true);
        }
        return false;
    }

    private function skillsMatchBonus(Student $student, Position $position): int
    {
        if (empty($student->skills) || empty($position->skills_required)) {
            return 0;
        }
        $studentSkills = array_map('strtolower', array_map('trim', explode(',', $student->skills)));
        $required = array_map('strtolower', array_map('trim', explode(',', $position->skills_required)));
        $matches = count(array_intersect($studentSkills, $required));
        return min(10, $matches * 3);
    }

    public function profileCompletionPercent(Student $student): int
    {
        return (new ProfileCompletionService())->dashboardPercent($student);
    }

    public static function normalizePositionStatus(string $status): bool
    {
        return in_array(strtolower($status), ['active', 'open'], true);
    }
}
