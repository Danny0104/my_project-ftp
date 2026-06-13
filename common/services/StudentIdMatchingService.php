<?php

namespace common\services;

use common\models\Student;
use common\models\User;
use Yii;

/**
 * Normalizes and compares profile data against OCR-extracted ID fields.
 *
 * Point allocation (max 100):
 * - Registration number: 50
 * - Name: 25
 * - University: 10
 * - Program: 10
 * - Field of study: 5
 */
class StudentIdMatchingService
{
    public const POINTS_REGISTRATION = 50;
    public const POINTS_NAME = 25;
    public const POINTS_UNIVERSITY = 10;
    public const POINTS_PROGRAM = 10;
    public const POINTS_FIELD = 5;

    public const THRESHOLD_AUTO_VERIFY = 70;
    public const THRESHOLD_MANUAL_REVIEW = 50;

    /** @var array<string, string> */
    private const FIELD_LABELS = [
        'registration_number' => 'Registration Number',
        'name' => 'Name',
        'university' => 'University',
        'program' => 'Program',
        'field_of_study' => 'Field of Study',
    ];

    /** @var array<string, string> */
    private const OCR_EQUIVALENTS = [
        'O' => '0',
        '0' => 'O',
        'I' => '1',
        '1' => 'I',
        'S' => '5',
        '5' => 'S',
        'B' => '8',
        '8' => 'B',
        'Z' => '2',
        '2' => 'Z',
    ];

    /**
     * @param array<string, mixed> $extracted
     * @return array{
     *   score: int,
     *   checks: array<string, mixed>,
     *   reasons: string[],
     *   feedback: string[],
     *   matched_fields: string[],
     *   failed_fields: string[],
     *   verification_notes: string[],
     *   profile_snapshot: array<string, string|null>,
     *   extracted_snapshot: array<string, string|null>
     * }
     */
    public function evaluate(Student $student, ?User $user, array $extracted, int $ocrConfidence): array
    {
        $user = $user ?? ($student->user instanceof User ? $student->user : User::findOne($student->user_id));
        $parser = new StudentIdTextParser();

        $profileName = $this->resolveProfileName($user);
        $profileUniversity = trim((string) $student->university);
        $profileReg = trim((string) $student->student_id);
        $profileProgram = trim((string) ($student->program ?? ''));
        $profileField = trim((string) ($student->field_of_study ?? ''));

        $extractedName = $extracted['student_name'] ?? null;
        $extractedUniversity = $extracted['university_name'] ?? null;
        $extractedReg = $extracted['registration_number'] ?? $extracted['student_number'] ?? null;
        $extractedProgram = $extracted['program'] ?? $extracted['department'] ?? null;

        $nameResult = $this->scoreName($profileName, $extractedName);
        $universityResult = $this->scoreUniversity($profileUniversity, $extractedUniversity, $parser);
        $registrationResult = $this->scoreRegistration($profileReg, $extracted, $parser);
        $programResult = $this->scoreProgram($profileProgram, $extractedProgram, $profileField, $parser);
        $fieldResult = $this->scoreFieldOfStudy($profileField, $extractedProgram, $extracted, $parser);

        Yii::info([
            'event' => 'id_verify_field_scores',
            'student_id' => $student->id,
            'name' => $this->debugFieldComparison('name', $profileName, $extractedName, $nameResult),
            'registration' => $this->debugFieldComparison('registration', $profileReg, $extractedReg, $registrationResult),
            'university' => $this->debugFieldComparison('university', $profileUniversity, $extractedUniversity, $universityResult),
            'program' => $this->debugFieldComparison('program', $profileProgram ?: $profileField, $extractedProgram, $programResult),
            'field_of_study' => $this->debugFieldComparison('field_of_study', $profileField, $extractedProgram, $fieldResult),
        ], __METHOD__);

        $expired = $this->isExpired($extracted['expiry_date'] ?? null);
        $readable = $ocrConfidence >= 30 && (
            $extractedReg !== null
            || $extractedName !== null
            || $extractedUniversity !== null
        );

        $earnedPoints = $this->earnedPoints($registrationResult['score'], self::POINTS_REGISTRATION)
            + $this->earnedPoints($nameResult['score'], self::POINTS_NAME)
            + $this->earnedPoints($universityResult['score'], self::POINTS_UNIVERSITY)
            + $this->earnedPoints($programResult['score'], self::POINTS_PROGRAM)
            + $this->earnedPoints($fieldResult['score'], self::POINTS_FIELD);

        if ($expired) {
            $earnedPoints = max(0, $earnedPoints - 10);
        }

        if (!$readable) {
            $earnedPoints = max(0, $earnedPoints - 5);
        }

        $totalScore = min(100, max(0, $earnedPoints));

        $fieldResults = [
            'registration_number' => $registrationResult,
            'name' => $nameResult,
            'university' => $universityResult,
            'program' => $programResult,
            'field_of_study' => $fieldResult,
        ];

        [$matchedFields, $failedFields, $verificationNotes] = $this->buildFieldOutcomes($fieldResults);

        $profileSnapshot = [
            'name' => $profileName ?: null,
            'university' => $profileUniversity ?: null,
            'registration_number' => $profileReg ?: null,
            'program' => $profileProgram ?: null,
            'field_of_study' => $profileField ?: null,
        ];

        $extractedSnapshot = [
            'name' => $extractedName,
            'university' => $extractedUniversity,
            'registration_number' => $extractedReg,
            'program' => $extractedProgram,
        ];

        $checks = [
            'name_match' => $nameResult['matched'],
            'name_score' => $nameResult['score'],
            'name_points' => $this->earnedPoints($nameResult['score'], self::POINTS_NAME),
            'name_detail' => $nameResult['detail'],
            'university_match' => $universityResult['matched'],
            'university_score' => $universityResult['score'],
            'university_points' => $this->earnedPoints($universityResult['score'], self::POINTS_UNIVERSITY),
            'university_detail' => $universityResult['detail'],
            'registration_match' => $registrationResult['matched'],
            'registration_score' => $registrationResult['score'],
            'registration_points' => $this->earnedPoints($registrationResult['score'], self::POINTS_REGISTRATION),
            'registration_detail' => $registrationResult['detail'],
            'program_match' => $programResult['matched'],
            'program_score' => $programResult['score'],
            'program_points' => $this->earnedPoints($programResult['score'], self::POINTS_PROGRAM),
            'program_detail' => $programResult['detail'],
            'field_of_study_match' => $fieldResult['matched'],
            'field_of_study_score' => $fieldResult['score'],
            'field_of_study_points' => $this->earnedPoints($fieldResult['score'], self::POINTS_FIELD),
            'field_of_study_detail' => $fieldResult['detail'],
            'matched_fields' => $matchedFields,
            'failed_fields' => $failedFields,
            'verification_notes' => $verificationNotes,
            'expired' => $expired,
            'readable' => $readable,
            'ocr_confidence' => $ocrConfidence,
            'profile_snapshot' => $profileSnapshot,
            'extracted_snapshot' => $extractedSnapshot,
            'points_breakdown' => [
                'registration' => $this->earnedPoints($registrationResult['score'], self::POINTS_REGISTRATION),
                'name' => $this->earnedPoints($nameResult['score'], self::POINTS_NAME),
                'university' => $this->earnedPoints($universityResult['score'], self::POINTS_UNIVERSITY),
                'program' => $this->earnedPoints($programResult['score'], self::POINTS_PROGRAM),
                'field_of_study' => $this->earnedPoints($fieldResult['score'], self::POINTS_FIELD),
            ],
        ];

        $reasons = $this->buildReasons($fieldResults, $expired, $readable);
        $feedback = $verificationNotes;

        $this->logEvaluation($student, $totalScore, $checks, $profileSnapshot, $extractedSnapshot);

        return [
            'score' => $totalScore,
            'checks' => $checks,
            'reasons' => $reasons,
            'feedback' => $feedback,
            'matched_fields' => $matchedFields,
            'failed_fields' => $failedFields,
            'verification_notes' => $verificationNotes,
            'profile_snapshot' => $profileSnapshot,
            'extracted_snapshot' => $extractedSnapshot,
        ];
    }

    /**
     * @return array{matched: bool, score: int, detail: string|null}
     */
    public function scoreName(?string $profileName, ?string $extractedName): array
    {
        if ($profileName === null || trim($profileName) === '') {
            return ['matched' => false, 'score' => 0, 'detail' => 'Profile name is missing.'];
        }

        if ($extractedName === null || trim($extractedName) === '') {
            return ['matched' => false, 'score' => 0, 'detail' => 'Name not found on ID.'];
        }

        $profileTokens = $this->nameTokens($profileName);
        $extractedTokens = $this->nameTokens($extractedName);

        if ($profileTokens === [] || $extractedTokens === []) {
            return ['matched' => false, 'score' => 0, 'detail' => 'Name could not be compared.'];
        }

        $scores = [
            $this->tokenOverlapScore($profileTokens, $extractedTokens),
            $this->tokenOverlapScore(array_reverse($profileTokens), $extractedTokens),
            $this->tokenOverlapScore($profileTokens, array_reverse($extractedTokens)),
            $this->similarityPercent(
                implode(' ', $profileTokens),
                implode(' ', $extractedTokens)
            ),
        ];

        $score = (int) max($scores);
        $matched = $score >= 70;

        return [
            'matched' => $matched,
            'score' => $score,
            'detail' => $this->detailForScore($score, 'Name matched.', 'Name partially matched.', 'Name mismatch between profile and ID.'),
        ];
    }

    /**
     * @return array{matched: bool, score: int, detail: string|null}
     */
    public function scoreUniversity(string $profileUniversity, ?string $extractedUniversity, StudentIdTextParser $parser): array
    {
        if ($profileUniversity === '') {
            return ['matched' => false, 'score' => 0, 'detail' => 'University not set on profile.'];
        }

        if ($extractedUniversity === null || trim($extractedUniversity) === '') {
            return ['matched' => false, 'score' => 0, 'detail' => 'University not found on ID.'];
        }

        $profileCanonical = $parser->resolveUniversityCanonical($profileUniversity);
        $extractedCanonical = $parser->resolveUniversityCanonical($extractedUniversity);

        if ($profileCanonical !== null && $extractedCanonical !== null && $profileCanonical === $extractedCanonical) {
            return ['matched' => true, 'score' => 100, 'detail' => 'University matched.'];
        }

        $profileNorm = mb_strtolower($parser->normalizeUniversity($profileUniversity), 'UTF-8');
        $extractedNorm = mb_strtolower($parser->normalizeUniversity($extractedUniversity), 'UTF-8');

        if ($profileNorm === $extractedNorm) {
            return ['matched' => true, 'score' => 100, 'detail' => 'University matched.'];
        }

        if ($profileCanonical !== null && str_contains(mb_strtolower($extractedUniversity, 'UTF-8'), mb_strtolower($profileCanonical, 'UTF-8'))) {
            return ['matched' => true, 'score' => 98, 'detail' => 'University matched via abbreviation.'];
        }

        if ($extractedCanonical !== null && str_contains($profileNorm, mb_strtolower($extractedCanonical, 'UTF-8'))) {
            return ['matched' => true, 'score' => 98, 'detail' => 'University matched via abbreviation.'];
        }

        $score = max(
            $this->similarityPercent($profileNorm, $extractedNorm),
            str_contains($profileNorm, $extractedNorm) || str_contains($extractedNorm, $profileNorm) ? 88 : 0
        );

        $matched = $score >= 70;
        $usedAbbreviation = $parser->isUniversityAbbreviation($profileUniversity)
            || $parser->isUniversityAbbreviation($extractedUniversity);

        return [
            'matched' => $matched,
            'score' => (int) $score,
            'detail' => $this->detailForScore(
                (int) $score,
                'University matched.',
                $usedAbbreviation ? 'University abbreviation detected.' : 'University partially matched.',
                'University name mismatch.'
            ),
        ];
    }

    /**
     * @param array<string, mixed> $extracted
     * @return array{matched: bool, score: int, detail: string|null}
     */
    public function scoreRegistration(string $profileReg, array $extracted, StudentIdTextParser $parser): array
    {
        if ($profileReg === '') {
            return ['matched' => false, 'score' => 0, 'detail' => 'Registration number not set on profile.'];
        }

        $candidates = array_filter([
            $extracted['registration_number'] ?? null,
            $extracted['student_number'] ?? null,
        ]);

        if ($candidates === []) {
            return ['matched' => false, 'score' => 0, 'detail' => 'Registration number not found on ID.'];
        }

        $profileNorm = $parser->normalizeRegistrationNumber($profileReg);
        $bestScore = 0;

        foreach ($candidates as $candidate) {
            $extractedNorm = $parser->normalizeRegistrationNumber((string) $candidate);
            $bestScore = max($bestScore, $this->registrationSimilarity($profileNorm, $extractedNorm));
        }

        $matched = $bestScore >= 85;

        return [
            'matched' => $matched,
            'score' => (int) $bestScore,
            'detail' => $this->detailForScore(
                (int) $bestScore,
                'Registration number matched.',
                'Registration number partially matched (possible OCR variation).',
                'Registration number mismatch.'
            ),
        ];
    }

    /**
     * @return array{matched: bool, score: int, detail: string|null}
     */
    public function scoreProgram(
        string $profileProgram,
        ?string $extractedProgram,
        string $profileField,
        StudentIdTextParser $parser
    ): array {
        $profileCandidate = $profileProgram !== '' ? $profileProgram : $profileField;
        if ($profileCandidate === '') {
            return ['matched' => false, 'score' => 0, 'detail' => 'Program not set on profile.'];
        }

        if ($extractedProgram === null || trim($extractedProgram) === '') {
            return ['matched' => false, 'score' => 0, 'detail' => 'Program not found on ID.'];
        }

        $profileNorm = $parser->normalizeProgram($profileCandidate);
        $extractedNorm = $parser->normalizeProgram($extractedProgram);

        if ($profileNorm === $extractedNorm) {
            return ['matched' => true, 'score' => 100, 'detail' => 'Program matched.'];
        }

        if ($profileNorm !== '' && str_contains($extractedNorm, $profileNorm)) {
            return ['matched' => true, 'score' => 92, 'detail' => 'Program matched.'];
        }

        if ($extractedNorm !== '' && str_contains($profileNorm, $extractedNorm)) {
            return ['matched' => true, 'score' => 90, 'detail' => 'Program matched.'];
        }

        $fieldNorm = $parser->normalizeFieldOfStudy($profileField);
        if ($fieldNorm !== '' && str_contains($extractedNorm, $fieldNorm)) {
            return ['matched' => true, 'score' => 85, 'detail' => 'Program matched via field of study keywords.'];
        }

        $score = $this->similarityPercent($profileNorm, $extractedNorm);
        $matched = $score >= 70;

        return [
            'matched' => $matched,
            'score' => $score,
            'detail' => $this->detailForScore(
                $score,
                'Program matched.',
                'Program partially matched.',
                'Program mismatch.'
            ),
        ];
    }

    /**
     * @param array<string, mixed> $extracted
     * @return array{matched: bool, score: int, detail: string|null}
     */
    public function scoreFieldOfStudy(
        string $profileField,
        ?string $extractedProgram,
        array $extracted,
        StudentIdTextParser $parser
    ): array {
        if ($profileField === '') {
            return ['matched' => false, 'score' => 0, 'detail' => 'Field of study not set on profile.'];
        }

        $fieldNorm = $parser->normalizeFieldOfStudy($profileField);
        $candidates = array_filter([
            $extractedProgram,
            $extracted['department'] ?? null,
            $extracted['faculty'] ?? null,
        ]);

        if ($candidates === []) {
            return ['matched' => false, 'score' => 0, 'detail' => 'Field of study not found on ID.'];
        }

        $bestScore = 0;
        foreach ($candidates as $candidate) {
            $candidateNorm = $parser->normalizeProgram((string) $candidate);
            if ($fieldNorm !== '' && str_contains($candidateNorm, $fieldNorm)) {
                $bestScore = max($bestScore, 100);
                continue;
            }
            $bestScore = max($bestScore, $this->similarityPercent($fieldNorm, $parser->normalizeFieldOfStudy((string) $candidate)));
        }

        $matched = $bestScore >= 70;

        return [
            'matched' => $matched,
            'score' => (int) $bestScore,
            'detail' => $this->detailForScore(
                (int) $bestScore,
                'Field of study matched.',
                'Field of study partially matched.',
                'Field of study mismatch.'
            ),
        ];
    }

    public function normalizeRegistrationNumber(string $value): string
    {
        return (new StudentIdTextParser())->normalizeRegistrationNumber($value);
    }

    /**
     * @return string[]
     */
    public function nameTokens(string $name): array
    {
        $name = mb_strtolower($name, 'UTF-8');
        $name = preg_replace('/[^a-z\s]/', ' ', $name) ?? $name;
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? $name);

        if ($name === '') {
            return [];
        }

        $parts = explode(' ', $name);
        $tokens = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || strlen($part) === 1) {
                continue;
            }
            $tokens[] = $part;
        }

        if (count($tokens) >= 3) {
            return [$tokens[0], $tokens[count($tokens) - 1]];
        }

        return $tokens;
    }

    /**
     * @param string[] $a
     * @param string[] $b
     */
    public function tokenOverlapScore(array $a, array $b): int
    {
        if ($a === [] || $b === []) {
            return 0;
        }

        $matched = 0;
        foreach ($a as $token) {
            foreach ($b as $other) {
                if ($token === $other || $this->similarityPercent($token, $other) >= 85) {
                    $matched++;
                    break;
                }
            }
        }

        return (int) round(($matched / max(count($a), count($b))) * 100);
    }

    public function registrationSimilarity(string $a, string $b): int
    {
        if ($a === '' || $b === '') {
            return 0;
        }

        if ($a === $b) {
            return 100;
        }

        if ($this->ocrNormalize($a) === $this->ocrNormalize($b)) {
            return 98;
        }

        if (strlen($a) === strlen($b)) {
            $matches = 0;
            $length = strlen($a);
            for ($i = 0; $i < $length; $i++) {
                if ($this->charsEquivalent($a[$i], $b[$i])) {
                    $matches++;
                }
            }
            if ($length > 0) {
                $ratio = ($matches / $length) * 100;
                if ($ratio >= 85) {
                    return (int) round($ratio);
                }
            }
        }

        return max(
            $this->similarityPercent($this->ocrNormalize($a), $this->ocrNormalize($b)),
            $this->similarityPercent($a, $b)
        );
    }

    public function ocrNormalize(string $value): string
    {
        $map = [
            'O' => '0',
            'I' => '1',
            'S' => '5',
            'B' => '8',
            'Z' => '2',
        ];

        $normalized = [];
        foreach (str_split(strtoupper($value)) as $char) {
            $normalized[] = $map[$char] ?? $char;
        }

        return implode('', $normalized);
    }

    public function charsEquivalent(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        $a = strtoupper($a);
        $b = strtoupper($b);

        if ($a === $b) {
            return true;
        }

        return (self::OCR_EQUIVALENTS[$a] ?? null) === $b
            || (self::OCR_EQUIVALENTS[$b] ?? null) === $a;
    }

    public function similarityPercent(string $a, string $b): int
    {
        if ($a === '' || $b === '') {
            return 0;
        }
        if ($a === $b) {
            return 100;
        }

        similar_text($a, $b, $percent);

        return (int) round($percent);
    }

    private function earnedPoints(int $fieldScorePercent, int $maxPoints): int
    {
        return (int) round(($fieldScorePercent / 100) * $maxPoints);
    }

    private function detailForScore(int $score, string $strong, string $partial, string $fail): string
    {
        if ($score >= 90) {
            return $strong;
        }
        if ($score >= 70) {
            return $partial;
        }

        return $fail;
    }

    /**
     * @param array<string, array{matched: bool, score: int, detail: string|null}> $fieldResults
     * @return array{0: string[], 1: string[], 2: string[]}
     */
    private function buildFieldOutcomes(array $fieldResults): array
    {
        $matched = [];
        $failed = [];
        $notes = [];

        foreach ($fieldResults as $key => $result) {
            $label = self::FIELD_LABELS[$key] ?? $key;
            if (!empty($result['matched'])) {
                $matched[] = $label;
                if (!empty($result['detail'])) {
                    $notes[] = $result['detail'];
                }
            } else {
                if (($result['score'] ?? 0) >= 70 && !empty($result['detail'])) {
                    $notes[] = $result['detail'];
                } elseif (($result['score'] ?? 0) > 0 && !empty($result['detail'])) {
                    $notes[] = $result['detail'];
                } elseif (($result['score'] ?? 0) === 0 && !empty($result['detail'])) {
                    $notes[] = $result['detail'];
                }
                if (($result['score'] ?? 0) < 70) {
                    $failed[] = $label;
                }
            }
        }

        return [$matched, $failed, array_values(array_unique($notes))];
    }

    /**
     * @param array<string, array{matched: bool, score: int, detail: string|null}> $fieldResults
     * @return string[]
     */
    private function buildReasons(array $fieldResults, bool $expired, bool $readable): array
    {
        $reasons = [];
        foreach ($fieldResults as $result) {
            if (($result['score'] ?? 0) < self::THRESHOLD_MANUAL_REVIEW && !empty($result['detail'])) {
                $reasons[] = $result['detail'];
            }
        }
        if ($expired) {
            $reasons[] = 'Student ID appears to be expired.';
        }
        if (!$readable) {
            $reasons[] = 'Unable to read all fields from the uploaded ID.';
        }

        return array_values(array_unique($reasons));
    }

    /**
     * @param array<string, string|null> $profileSnapshot
     * @param array<string, string|null> $extractedSnapshot
     * @param array<string, mixed> $checks
     */
    private function logEvaluation(
        Student $student,
        int $score,
        array $checks,
        array $profileSnapshot,
        array $extractedSnapshot
    ): void {
        Yii::info([
            'event' => 'student_id_verification_evaluated',
            'student_id' => $student->id,
            'user_id' => $student->user_id,
            'verification_score' => $score,
            'matched_fields' => $checks['matched_fields'] ?? [],
            'failed_fields' => $checks['failed_fields'] ?? [],
            'verification_notes' => $checks['verification_notes'] ?? [],
            'points_breakdown' => $checks['points_breakdown'] ?? [],
            'profile_snapshot' => $profileSnapshot,
            'extracted_snapshot' => $extractedSnapshot,
            'ocr_confidence' => $checks['ocr_confidence'] ?? null,
        ], __METHOD__);
    }

    private function resolveProfileName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $profileName = trim(implode(' ', array_filter([
            trim((string) $user->first_name),
            trim((string) $user->last_name),
        ])));

        if ($profileName === '' && trim((string) $user->username) !== '') {
            $profileName = str_replace(['_', '.'], ' ', (string) $user->username);
        }

        return $profileName !== '' ? $profileName : null;
    }

    private function isExpired(?string $expiryDate): bool
    {
        if ($expiryDate === null || trim($expiryDate) === '') {
            return false;
        }

        $timestamp = strtotime(str_replace(['.', '/'], '-', $expiryDate));
        if ($timestamp === false) {
            return false;
        }

        return $timestamp < strtotime('today');
    }

    /**
     * @param array{matched: bool, score: int, detail: string|null} $result
     * @return array<string, mixed>
     */
    private function debugFieldComparison(string $field, ?string $profile, ?string $extracted, array $result): array
    {
        return [
            'field' => $field,
            'profile_value' => $profile,
            'extracted_value' => $extracted,
            'matched' => $result['matched'] ?? false,
            'score' => $result['score'] ?? 0,
            'detail' => $result['detail'] ?? null,
        ];
    }
}
