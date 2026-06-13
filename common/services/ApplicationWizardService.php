<?php

namespace common\services;

use common\models\Student;
use common\models\User;

/**
 * Builds profile readiness data for the apply wizard.
 */
class ApplicationWizardService
{
    /**
     * @return array{
     *   ready: bool,
     *   issues: string[],
     *   fullName: string,
     *   photoUrl: string|null,
     *   university: string,
     *   registrationNumber: string,
     *   fieldOfStudy: string,
     *   skills: string[],
     *   cvUploaded: bool,
     *   cvLabel: string,
     *   idDocumentUploaded: bool,
     *   idVerificationLabel: string,
     *   profileCompletion: int|null
     * }
     */
    public function buildProfileReadiness(
        Student $student,
        ?User $user,
        EligibilityResult $eligibility,
        ?int $profileCompletion
    ): array {
        $issues = [];

        if (!$eligibility->eligible) {
            foreach ($eligibility->reasons as $reason) {
                $message = trim((string) ($reason['message'] ?? ''));
                if ($message !== '') {
                    $issues[] = $message;
                }
            }
        }

        if (empty($student->cv)) {
            $issues[] = 'Upload your CV in Edit Profile.';
        }

        if (!$student->hasIdDocument()) {
            $issues[] = 'Upload your student ID document in Edit Profile.';
        }

        if (empty($student->student_id)) {
            $issues[] = 'Add your registration number in Edit Profile.';
        }

        if ($profileCompletion !== null) {
            $minPct = \common\models\PlatformRegulation::getInt('min_profile_completion_percent', 75);
            if ($profileCompletion < $minPct) {
                $issues[] = "Your profile is {$profileCompletion}% complete. A minimum of {$minPct}% is required.";
            }
        }

        $issues = array_values(array_unique($issues));

        $fullName = trim(((string) ($user->first_name ?? '')) . ' ' . ((string) ($user->last_name ?? '')));
        if ($fullName === '') {
            $fullName = (string) ($user->username ?? 'Student');
        }

        $skills = array_values(array_filter(array_map('trim', explode(',', (string) $student->skills))));

        return [
            'ready' => $issues === [],
            'issues' => $issues,
            'fullName' => $fullName,
            'photoUrl' => $student->getPhotoUrl('md'),
            'university' => (string) ($student->university ?: '—'),
            'registrationNumber' => (string) ($student->student_id ?: '—'),
            'fieldOfStudy' => (string) ($student->field_of_study ?: '—'),
            'skills' => $skills,
            'cvUploaded' => !empty($student->cv),
            'cvLabel' => !empty($student->cv) ? 'Attached' : 'Missing',
            'idDocumentUploaded' => $student->hasIdDocument(),
            'idVerificationLabel' => $student->getIdVerificationLabel(),
            'profileCompletion' => $profileCompletion,
        ];
    }
}
