<?php

namespace common\services;

use common\models\Student;
use common\models\User;
use Yii;

/**
 * Auto-verification: OCR extraction, profile matching, scoring, status assignment.
 */
class StudentIdVerificationService
{
    public const METHOD_NONE = 'none';
    public const METHOD_AUTO = 'auto';
    public const METHOD_MANUAL = 'manual';

    /**
     * Reload student and user from the database before verification matching.
     *
     * @return array{0: Student, 1: User}
     */
    public function reloadFreshContext(Student $student): array
    {
        $freshStudent = Student::findOne($student->id);
        if ($freshStudent === null) {
            throw new \RuntimeException('Student record not found.');
        }

        $freshUser = User::findOne($freshStudent->user_id);
        if ($freshUser === null) {
            throw new \RuntimeException('User record not found.');
        }

        $freshStudent->populateRelation('user', $freshUser);

        return [$freshStudent, $freshUser];
    }

    /**
     * @return array{ready: bool, missing: string[], profileSummary: array<string, string|null>}
     */
    public function validateProfileReadyForVerification(Student $student, ?User $user = null): array
    {
        $user = $user ?? ($student->user instanceof User ? $student->user : User::findOne($student->user_id));

        $firstName = trim((string) ($user->first_name ?? ''));
        $lastName = trim((string) ($user->last_name ?? ''));
        $fullName = trim($firstName . ' ' . $lastName);

        $missing = [];
        if ($fullName === '') {
            $missing[] = 'Full name';
        }
        if (trim((string) $student->student_id) === '') {
            $missing[] = 'Registration number';
        }
        if (trim((string) $student->university) === '') {
            $missing[] = 'University';
        }
        if (trim((string) $student->program) === '') {
            $missing[] = 'Academic program';
        }
        if (trim((string) $student->field_of_study) === '') {
            $missing[] = 'Field of study';
        }

        return [
            'ready' => $missing === [],
            'missing' => $missing,
            'profileSummary' => [
                'name' => $fullName !== '' ? $fullName : null,
                'registrationNumber' => trim((string) $student->student_id) ?: null,
                'university' => trim((string) $student->university) ?: null,
                'program' => trim((string) $student->program) ?: null,
                'fieldOfStudy' => trim((string) $student->field_of_study) ?: null,
            ],
        ];
    }

    public function verifyAfterUpload(Student $student): array
    {
        Yii::info([
            'step' => 'verification_started',
            'student_id' => $student->id,
            'db_path' => $student->id_document_path,
        ], __METHOD__);

        try {
            [$student, ] = $this->reloadFreshContext($student);
        } catch (\RuntimeException $e) {
            Yii::warning([
                'step' => 'verification_exit',
                'reason' => 'reloadFreshContext failed: ' . $e->getMessage(),
                'student_id' => $student->id,
            ], __METHOD__);
            throw $e;
        }

        Yii::info(['step' => 'context_reloaded', 'student_id' => $student->id, 'db_path' => $student->id_document_path], __METHOD__);

        $docService = new StudentIdDocumentService();
        $pathDiagnostics = $docService->getPathDiagnostics($student);
        $absolutePath = $pathDiagnostics['absolute_path'];

        Yii::info([
            'step' => 'document_path_resolution',
            'event' => 'id_verify_path_resolution',
            'student_id' => $student->id,
            'db_path' => $pathDiagnostics['db_path'],
            'absolute_path' => $absolutePath,
            'expected_file' => $pathDiagnostics['expected_file'],
            'storage_dir' => $pathDiagnostics['storage_dir'],
            'file_exists' => $pathDiagnostics['file_exists'],
            'is_readable' => $pathDiagnostics['is_readable'],
            'relative_path_valid' => $pathDiagnostics['relative_path_valid'],
        ], __METHOD__);

        if ($absolutePath === null) {
            Yii::warning([
                'step' => 'verification_exit',
                'reason' => 'Student ID document not found after upload — absolute path is null',
                'student_id' => $student->id,
                'db_path' => $pathDiagnostics['db_path'],
                'absolute_path' => $absolutePath,
                'expected_file' => $pathDiagnostics['expected_file'],
                'file_exists' => $pathDiagnostics['file_exists'],
                'is_readable' => $pathDiagnostics['is_readable'],
                'relative_path_valid' => $pathDiagnostics['relative_path_valid'],
                'hint' => $pathDiagnostics['db_path'] === null
                    ? 'id_document_path is null in database — path was not saved before reload'
                    : ($pathDiagnostics['file_exists'] ? 'File exists but path validation failed' : 'File missing from storage directory'),
            ], __METHOD__);

            throw new \RuntimeException('Student ID document not found after upload.');
        }

        Yii::info(['step' => 'document_located', 'student_id' => $student->id, 'absolute_path' => $absolutePath], __METHOD__);

        $documentHash = (new StudentIdFraudDetectionService())->computeDocumentHash($absolutePath);
        $student->id_document_hash = $documentHash;

        Yii::info(['step' => 'ocr_start', 'student_id' => $student->id, 'absolute_path' => $absolutePath], __METHOD__);

        $ocrService = new StudentIdOcrService();
        $ocrResult = $ocrService->extractText($absolutePath);

        Yii::info([
            'step' => 'ocr_complete',
            'event' => 'id_verify_ocr_complete',
            'student_id' => $student->id,
            'uploaded_file_path' => $absolutePath,
            'file_size' => $ocrResult['debug']['file_size'] ?? null,
            'image_width' => $ocrResult['debug']['image_width'] ?? null,
            'image_height' => $ocrResult['debug']['image_height'] ?? null,
            'ocr_command' => $ocrResult['debug']['ocr_command'] ?? null,
            'ocr_method' => $ocrResult['method'] ?? null,
            'ocr_confidence' => $ocrResult['confidence'] ?? null,
            'ocr_error' => $ocrResult['error'] ?? null,
            'failure_stage' => $ocrResult['debug']['failure_stage'] ?? null,
            'preprocessing_steps' => $ocrResult['debug']['preprocessing_steps'] ?? [],
            'shell_exit_code' => $ocrResult['debug']['shell_exit_code'] ?? null,
            'shell_output' => $ocrResult['debug']['shell_output'] ?? [],
            'tesseract_binary' => $ocrResult['debug']['tesseract_binary'] ?? null,
            'raw_text_length' => strlen($ocrResult['text'] ?? ''),
            'raw_text' => $ocrResult['text'] ?? '',
        ], __METHOD__);

        Yii::info(['step' => 'parser_start', 'student_id' => $student->id], __METHOD__);

        $parser = new StudentIdTextParser();
        $parseResult = $parser->parseWithDiagnostics($ocrResult['text']);
        $extracted = $parseResult['fields'];

        Yii::info([
            'step' => 'parser_complete',
            'event' => 'id_verify_parsed',
            'student_id' => $student->id,
            'extracted' => $extracted,
            'parser_result' => $parseResult['parser_result'],
        ], __METHOD__);

        Yii::info(['step' => 'match_start', 'student_id' => $student->id], __METHOD__);

        $user = $student->user instanceof User ? $student->user : User::findOne($student->user_id);
        $matchingService = new StudentIdMatchingService();
        $match = $matchingService->evaluate($student, $user, $extracted, (int) $ocrResult['confidence']);

        Yii::info([
            'step' => 'match_complete',
            'event' => 'id_verify_match_complete',
            'student_id' => $student->id,
            'verification_score' => $match['score'],
            'matched_fields' => $match['matched_fields'] ?? [],
            'failed_fields' => $match['failed_fields'] ?? [],
            'name_match' => $match['checks']['name_match'] ?? null,
            'registration_match' => $match['checks']['registration_match'] ?? null,
            'university_match' => $match['checks']['university_match'] ?? null,
            'profile_snapshot' => $match['profile_snapshot'] ?? [],
            'extracted_snapshot' => $match['extracted_snapshot'] ?? [],
        ], __METHOD__);

        Yii::info(['step' => 'fields_assign_start', 'student_id' => $student->id], __METHOD__);

        $student->id_ocr_data = json_encode([
            'raw_text' => $ocrResult['text'],
            'extracted' => $extracted,
            'ocr_method' => $ocrResult['method'],
            'ocr_error' => $ocrResult['error'],
            'profile_snapshot' => $match['profile_snapshot'],
            'verified_at' => time(),
        ], JSON_UNESCAPED_UNICODE);
        $student->id_ocr_confidence = (int) $ocrResult['confidence'];

        $lowOcrConfidence = (int) $ocrResult['confidence'] < StudentIdOcrService::LOW_CONFIDENCE_THRESHOLD;
        $student->id_ocr_debug = json_encode([
            'raw_text' => $ocrResult['text'],
            'confidence' => (int) $ocrResult['confidence'],
            'image_size' => [
                'bytes' => $ocrResult['debug']['file_size'] ?? null,
                'width' => $ocrResult['debug']['image_width'] ?? null,
                'height' => $ocrResult['debug']['image_height'] ?? null,
            ],
            'preprocessing_steps' => $ocrResult['debug']['preprocessing_steps'] ?? [],
            'parser_result' => $parseResult['parser_result'],
            'ocr_command' => $ocrResult['debug']['ocr_command'] ?? null,
            'tesseract_binary' => $ocrResult['debug']['tesseract_binary'] ?? null,
            'failure_stage' => $ocrResult['debug']['failure_stage'] ?? null,
            'uploaded_file_path' => $absolutePath,
            'low_ocr_confidence' => $lowOcrConfidence,
        ], JSON_UNESCAPED_UNICODE);

        $registrationForFraud = $extracted['registration_number'] ?? $extracted['student_number'] ?? null;
        $fraud = (new StudentIdFraudDetectionService())->analyze($student, $documentHash, $registrationForFraud);
        $student->id_fraud_flag = $fraud['flagged'];
        $student->id_fraud_reason = $fraud['reason'];

        $student->id_verification_score = $match['score'];
        $student->id_verification_checks = json_encode($match['checks'], JSON_UNESCAPED_UNICODE);
        $student->id_verification_method = self::METHOD_AUTO;

        Yii::info([
            'step' => 'fields_assigned',
            'student_id' => $student->id,
            'id_ocr_data_set' => $student->id_ocr_data !== null,
            'id_verification_score' => $student->id_verification_score,
            'id_verification_checks_set' => $student->id_verification_checks !== null,
        ], __METHOD__);

        $this->applyStatus($student, $match, $fraud, $ocrResult);

        if ($lowOcrConfidence) {
            $student->id_verification_status = Student::ID_VERIFICATION_PENDING;
            $student->id_rejection_reason = 'Low OCR Confidence. Manual review required.'
                . ($ocrResult['error'] ? ' ' . $ocrResult['error'] : '');
        }

        Yii::info([
            'step' => 'verification_completed',
            'event' => 'student_id_verification_completed',
            'student_id' => $student->id,
            'user_id' => $student->user_id,
            'verification_score' => $match['score'],
            'status' => $student->id_verification_status,
            'matched_fields' => $match['matched_fields'] ?? [],
            'failed_fields' => $match['failed_fields'] ?? [],
            'verification_notes' => $match['verification_notes'] ?? [],
            'document_path' => $student->id_document_path,
        ], __METHOD__);

        return [
            'ocr' => $ocrResult,
            'extracted' => $extracted,
            'match' => $match,
            'fraud' => $fraud,
            'status' => $student->id_verification_status,
            'label' => $student->getIdVerificationLabel(),
            'message' => $student->id_rejection_reason ?? $this->successMessage($student),
            'feedback' => $match['feedback'],
            'matched_fields' => $match['matched_fields'] ?? [],
            'failed_fields' => $match['failed_fields'] ?? [],
            'verification_notes' => $match['verification_notes'] ?? [],
        ];
    }

    /**
     * @param array<string, mixed> $extracted
     * @return array{score: int, checks: array<string, mixed>, reasons: string[]}
     */
    public function evaluateMatches(Student $student, array $extracted, int $ocrConfidence): array
    {
        $user = $student->user instanceof User ? $student->user : User::findOne($student->user_id);
        $match = (new StudentIdMatchingService())->evaluate($student, $user, $extracted, $ocrConfidence);

        return [
            'score' => $match['score'],
            'checks' => $match['checks'],
            'reasons' => $match['reasons'],
            'feedback' => $match['feedback'],
        ];
    }

    /**
     * @param array<string, mixed> $match
     * @param array{flagged: bool, reason: string|null} $fraud
     * @param array{text: string, confidence: int, error: string|null} $ocrResult
     */
    private function applyStatus(Student $student, array $match, array $fraud, array $ocrResult): void
    {
        $score = (int) $match['score'];
        $checks = $match['checks'];
        $feedback = $match['feedback'] ?? [];

        $student->id_verified_at = null;
        $student->id_verified_by = null;
        $student->id_rejection_reason = null;

        if ($fraud['flagged']) {
            $student->id_verification_status = Student::ID_VERIFICATION_PENDING;
            $student->id_rejection_reason = $fraud['reason'];
            return;
        }

        if (!$checks['readable']) {
            $student->id_verification_status = Student::ID_VERIFICATION_PENDING;
            $student->id_rejection_reason = $ocrResult['error']
                ?? 'Unable to read all fields from the uploaded ID. Manual review required.';
            return;
        }

        if ($checks['expired'] && $score < StudentIdMatchingService::THRESHOLD_MANUAL_REVIEW) {
            $student->id_verification_status = Student::ID_VERIFICATION_REJECTED;
            $student->id_rejection_reason = $this->formatFailureReason($score, $feedback, 'Student ID appears to be expired.');
            return;
        }

        if ($score >= StudentIdMatchingService::THRESHOLD_AUTO_VERIFY && !$checks['expired']) {
            $student->id_verification_status = Student::ID_VERIFICATION_APPROVED;
            $student->id_verified_at = time();
            $student->id_verification_method = self::METHOD_AUTO;
            $student->id_rejection_reason = $this->formatApprovalNotes($score, $match);
            return;
        }

        if ($score >= StudentIdMatchingService::THRESHOLD_MANUAL_REVIEW) {
            $student->id_verification_status = Student::ID_VERIFICATION_PENDING;
            $student->id_rejection_reason = $this->formatReviewReason($score, $match);
            return;
        }

        $student->id_verification_status = Student::ID_VERIFICATION_REJECTED;
        $student->id_rejection_reason = $this->formatFailureReason(
            $score,
            $feedback,
            $match['reasons'][0] ?? 'Verification failed.'
        );
    }

    /**
     * @param array<string, mixed> $match
     */
    private function formatReviewReason(int $score, array $match): string
    {
        return $this->formatOutcomeNotes($score, $match, 'Needs manual review.');
    }

    /**
     * @param array<string, mixed> $match
     */
    private function formatApprovalNotes(int $score, array $match): string
    {
        return $this->formatOutcomeNotes($score, $match, 'Auto approved.');
    }

    /**
     * @param array<string, mixed> $match
     */
    private function formatOutcomeNotes(int $score, array $match, string $statusLabel): string
    {
        $checks = is_array($match['checks'] ?? null) ? $match['checks'] : [];
        $matched = $checks['matched_fields'] ?? ($match['matched_fields'] ?? []);
        $failed = $checks['failed_fields'] ?? ($match['failed_fields'] ?? []);
        $notes = $checks['verification_notes'] ?? ($match['verification_notes'] ?? []);

        $parts = [];
        if ($matched !== []) {
            $parts[] = 'Matched: ' . implode(', ', $matched) . '.';
        }
        if ($failed !== []) {
            $parts[] = 'Failed: ' . implode(', ', $failed) . '.';
        }
        if ($notes !== []) {
            $parts[] = implode(' ', $notes);
        }

        $summary = $parts !== [] ? implode(' ', $parts) : 'Verification completed.';

        return $summary . ' Verification score: ' . $score . '%. Status: ' . $statusLabel;
    }

    /**
     * @param string[] $feedback
     */
    private function formatFailureReason(int $score, array $feedback, string $fallback): string
    {
        $lines = array_map(static fn(string $line): string => $line, $feedback);
        $summary = $lines !== [] ? implode(' ', $lines) : $fallback;

        return $summary . ' Verification score: ' . $score . '%.';
    }

    private function successMessage(Student $student): string
    {
        if ($student->id_verification_status === Student::ID_VERIFICATION_APPROVED) {
            return 'Student identity matched automatically.';
        }
        if ($student->id_verification_status === Student::ID_VERIFICATION_PENDING) {
            return 'Student ID uploaded. Manual review required.';
        }

        return 'Student ID uploaded.';
    }

    /**
     * @return array<string, mixed>
     */
    public function getOcrDebug(Student $student): array
    {
        if (empty($student->id_ocr_debug)) {
            return [];
        }

        $decoded = json_decode((string) $student->id_ocr_debug, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getRawOcrText(Student $student): string
    {
        $debug = $this->getOcrDebug($student);
        if (!empty($debug['raw_text'])) {
            return (string) $debug['raw_text'];
        }

        if (empty($student->id_ocr_data)) {
            return '';
        }

        $decoded = json_decode((string) $student->id_ocr_data, true);

        return is_array($decoded) ? (string) ($decoded['raw_text'] ?? '') : '';
    }

    public function isLowOcrConfidence(Student $student): bool
    {
        $debug = $this->getOcrDebug($student);
        if (isset($debug['low_ocr_confidence'])) {
            return (bool) $debug['low_ocr_confidence'];
        }

        return $student->id_ocr_confidence !== null
            && (int) $student->id_ocr_confidence < StudentIdOcrService::LOW_CONFIDENCE_THRESHOLD;
    }

    /**
     * @return array<string, mixed>
     */
    public function getChecks(Student $student): array
    {
        if (empty($student->id_verification_checks)) {
            return [];
        }

        $decoded = json_decode((string) $student->id_verification_checks, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtractedData(Student $student): array
    {
        if (empty($student->id_ocr_data)) {
            return [];
        }

        $decoded = json_decode((string) $student->id_ocr_data, true);
        if (!is_array($decoded)) {
            return [];
        }

        $extracted = is_array($decoded['extracted'] ?? null) ? $decoded['extracted'] : [];
        $checks = $this->getChecks($student);
        $extractedSnapshot = is_array($checks['extracted_snapshot'] ?? null)
            ? $checks['extracted_snapshot']
            : [];

        return array_merge($extracted, $extractedSnapshot);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildUiPayload(Student $student): array
    {
        $checks = $this->getChecks($student);
        $extracted = $this->getExtractedData($student);
        $profileSnapshot = is_array($checks['profile_snapshot'] ?? null) ? $checks['profile_snapshot'] : [];

        Yii::info([
            'event' => 'id_verify_ui_payload',
            'student_id' => $student->id,
            'verification_score_db' => $student->id_verification_score,
            'verification_status_db' => $student->id_verification_status,
            'checks_empty' => $checks === [],
            'name_match_db' => $checks['name_match'] ?? null,
            'registration_match_db' => $checks['registration_match'] ?? null,
            'university_match_db' => $checks['university_match'] ?? null,
            'extracted_from_ocr' => [
                'name' => $extracted['student_name'] ?? null,
                'registration' => $extracted['registration_number'] ?? null,
                'university' => $extracted['university_name'] ?? null,
            ],
            'profile_snapshot_from_checks' => $profileSnapshot,
        ], __METHOD__);
        $isVerified = $student->isIdVerified();
        $isPending = $student->id_verification_status === Student::ID_VERIFICATION_PENDING;
        $isRejected = $student->id_verification_status === Student::ID_VERIFICATION_REJECTED;
        $isAuto = $student->id_verification_method === self::METHOD_AUTO;

        $statusKey = 'none';
        if ($student->id_fraud_flag) {
            $statusKey = 'fraud';
        } elseif ($isVerified && $isAuto) {
            $statusKey = 'auto_verified';
        } elseif ($isVerified) {
            $statusKey = 'verified';
        } elseif ($isPending) {
            $statusKey = 'pending_review';
        } elseif ($isRejected) {
            $statusKey = 'rejected';
        }

        $fieldFeedback = array_values(array_filter(
            $checks['verification_notes'] ?? [
                $checks['registration_detail'] ?? null,
                $checks['name_detail'] ?? null,
                $checks['university_detail'] ?? null,
                $checks['program_detail'] ?? null,
                $checks['field_of_study_detail'] ?? null,
            ]
        ));

        return [
            'statusKey' => $statusKey,
            'verificationScore' => $student->id_verification_score,
            'verificationMethod' => $student->id_verification_method,
            'checks' => [
                'name' => (bool) ($checks['name_match'] ?? false),
                'university' => (bool) ($checks['university_match'] ?? false),
                'registration' => (bool) ($checks['registration_match'] ?? false),
                'program' => (bool) ($checks['program_match'] ?? false),
                'field_of_study' => (bool) ($checks['field_of_study_match'] ?? false),
            ],
            'fieldScores' => [
                'name' => (int) ($checks['name_score'] ?? 0),
                'university' => (int) ($checks['university_score'] ?? 0),
                'registration' => (int) ($checks['registration_score'] ?? 0),
                'program' => (int) ($checks['program_score'] ?? 0),
                'field_of_study' => (int) ($checks['field_of_study_score'] ?? 0),
            ],
            'matchedFields' => $checks['matched_fields'] ?? [],
            'failedFields' => $checks['failed_fields'] ?? [],
            'fieldFeedback' => $fieldFeedback,
            'extracted' => [
                'name' => $extracted['student_name'] ?? $extracted['name'] ?? null,
                'registrationNumber' => $extracted['registration_number'] ?? $extracted['student_number'] ?? null,
                'university' => $extracted['university_name'] ?? $extracted['university'] ?? null,
                'program' => $extracted['program'] ?? null,
                'fieldOfStudy' => $extracted['field_of_study'] ?? null,
                'expiryDate' => $extracted['expiry_date'] ?? null,
            ],
            'profileSnapshot' => [
                'name' => $profileSnapshot['name'] ?? null,
                'registrationNumber' => $profileSnapshot['registration_number'] ?? null,
                'university' => $profileSnapshot['university'] ?? null,
                'program' => $profileSnapshot['program'] ?? null,
                'fieldOfStudy' => $profileSnapshot['field_of_study'] ?? null,
            ],
            'comparisonRows' => $this->buildComparisonRows($student, $checks, $extracted, $profileSnapshot),
            'timeline' => $this->buildVerificationTimeline($student),
            'profileReadiness' => $this->validateProfileReadyForVerification($student),
            'fraudFlag' => (bool) $student->id_fraud_flag,
            'fraudReason' => $student->id_fraud_reason,
            'reviewReason' => $student->id_rejection_reason,
            'ocrConfidence' => $student->id_ocr_confidence,
            'ocrDebug' => $this->getOcrDebug($student),
            'rawOcrText' => $this->getRawOcrText($student),
            'lowOcrConfidence' => $this->isLowOcrConfidence($student),
        ];
    }

    /**
     * @param array<string, mixed> $checks
     * @param array<string, mixed> $extracted
     * @param array<string, mixed> $profileSnapshot
     * @return list<array{key: string, label: string, profile: string|null, ocr: string|null, result: string, resultLabel: string, score: int, matched: bool}>
     */
    private function buildComparisonRows(Student $student, array $checks, array $extracted, array $profileSnapshot): array
    {
        $user = $student->user instanceof User ? $student->user : User::findOne($student->user_id);
        $profileName = trim((string) ($profileSnapshot['name'] ?? ''));
        if ($profileName === '' && $user !== null) {
            $profileName = trim(trim((string) $user->first_name) . ' ' . trim((string) $user->last_name));
        }

        $fieldDefs = [
            [
                'key' => 'name',
                'label' => 'Name',
                'profile' => $profileName !== '' ? $profileName : null,
                'ocr' => $extracted['student_name'] ?? $extracted['name'] ?? null,
                'matchKey' => 'name_match',
                'scoreKey' => 'name_score',
            ],
            [
                'key' => 'registration',
                'label' => 'Registration Number',
                'profile' => trim((string) ($profileSnapshot['registration_number'] ?? $student->student_id)) ?: null,
                'ocr' => $extracted['registration_number'] ?? $extracted['student_number'] ?? null,
                'matchKey' => 'registration_match',
                'scoreKey' => 'registration_score',
            ],
            [
                'key' => 'university',
                'label' => 'University',
                'profile' => trim((string) ($profileSnapshot['university'] ?? $student->university)) ?: null,
                'ocr' => $extracted['university_name'] ?? $extracted['university'] ?? null,
                'matchKey' => 'university_match',
                'scoreKey' => 'university_score',
            ],
            [
                'key' => 'program',
                'label' => 'Program',
                'profile' => trim((string) ($profileSnapshot['program'] ?? $student->program)) ?: null,
                'ocr' => $extracted['program'] ?? null,
                'matchKey' => 'program_match',
                'scoreKey' => 'program_score',
            ],
            [
                'key' => 'field_of_study',
                'label' => 'Field of Study',
                'profile' => trim((string) ($profileSnapshot['field_of_study'] ?? $student->field_of_study)) ?: null,
                'ocr' => $extracted['field_of_study'] ?? null,
                'matchKey' => 'field_of_study_match',
                'scoreKey' => 'field_of_study_score',
            ],
        ];

        $rows = [];
        foreach ($fieldDefs as $def) {
            $matched = (bool) ($checks[$def['matchKey']] ?? false);
            $score = (int) ($checks[$def['scoreKey']] ?? 0);
            $result = $matched ? 'match' : ($score >= 70 ? 'partial' : 'mismatch');
            $resultLabel = match ($result) {
                'match' => 'Match',
                'partial' => 'Partial Match',
                default => 'Mismatch',
            };

            $rows[] = [
                'key' => $def['key'],
                'label' => $def['label'],
                'profile' => $def['profile'] !== null && $def['profile'] !== '' ? (string) $def['profile'] : null,
                'ocr' => $def['ocr'] !== null && $def['ocr'] !== '' ? (string) $def['ocr'] : null,
                'result' => $result,
                'resultLabel' => $resultLabel,
                'score' => $score,
                'matched' => $matched,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{type: string, label: string, at: string|null, meta: string|null}>
     */
    private function buildVerificationTimeline(Student $student): array
    {
        $events = [];

        if ($student->id_uploaded_at) {
            $events[] = [
                'type' => 'upload',
                'label' => 'Student ID uploaded',
                'at' => $student->getIdUploadedAtFormatted(),
                'meta' => null,
            ];
        }

        if ($student->id_ocr_confidence !== null && $student->hasIdDocument()) {
            $events[] = [
                'type' => 'ocr',
                'label' => 'OCR extraction completed',
                'at' => $student->getIdUploadedAtFormatted(),
                'meta' => 'Confidence: ' . (int) $student->id_ocr_confidence . '%',
            ];
        }

        if ($student->id_verification_score !== null && $student->hasIdDocument()) {
            $events[] = [
                'type' => 'matching',
                'label' => 'Profile matching completed',
                'at' => $student->getIdUploadedAtFormatted(),
                'meta' => 'Score: ' . (int) $student->id_verification_score . '%',
            ];
        }

        if ($student->isIdVerified()) {
            $events[] = [
                'type' => 'verified',
                'label' => $student->isIdAutoVerified() ? 'Automatically verified' : 'Manually approved',
                'at' => $student->getIdVerifiedAtFormatted(),
                'meta' => $student->getIdVerificationLabel(),
            ];
        } elseif ($student->id_verification_status === Student::ID_VERIFICATION_REJECTED) {
            $events[] = [
                'type' => 'rejected',
                'label' => 'Verification rejected',
                'at' => $student->getIdVerifiedAtFormatted() ?: $student->getIdUploadedAtFormatted(),
                'meta' => $student->id_rejection_reason,
            ];
        } elseif ($student->id_verification_status === Student::ID_VERIFICATION_PENDING && $student->hasIdDocument()) {
            $events[] = [
                'type' => 'review',
                'label' => 'Sent for manual review',
                'at' => $student->getIdUploadedAtFormatted(),
                'meta' => $student->id_rejection_reason,
            ];
        }

        return $events;
    }
}
