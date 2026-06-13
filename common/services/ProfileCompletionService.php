<?php

namespace common\services;

use common\models\Student;

/**
 * Single source of truth for student profile completion shown in the UI shell.
 */
class ProfileCompletionService
{
    /**
     * @return array<string, array{label: string, icon: string, done: bool, url: array}>
     */
    public function dashboardTasks(Student $student): array
    {
        return [
            'university' => [
                'label' => 'Add University',
                'icon' => 'fa-building-columns',
                'done' => trim((string) $student->university) !== '',
                'url' => ['profile/edit-profile', '#' => 'section-academic'],
            ],
            'student_id' => [
                'label' => 'Registration Number',
                'icon' => 'fa-id-card',
                'done' => trim((string) $student->student_id) !== '',
                'url' => ['profile/edit-profile', '#' => 'section-academic'],
            ],
            'id_document' => [
                'label' => 'Student ID Verification',
                'icon' => 'fa-graduation-cap',
                'done' => $student->isIdVerified(),
                'url' => ['profile/verification'],
            ],
            'field_of_study' => [
                'label' => 'Add Field of Study',
                'icon' => 'fa-book-open',
                'done' => trim((string) $student->field_of_study) !== '',
                'url' => ['profile/edit-profile', '#' => 'section-academic'],
            ],
            'cv' => [
                'label' => 'Upload CV',
                'icon' => 'fa-file-lines',
                'done' => trim((string) $student->cv) !== '',
                'url' => ['profile/edit-profile', '#' => 'section-documents'],
            ],
            'profile_photo' => [
                'label' => 'Add Profile Photo',
                'icon' => 'fa-camera',
                'done' => $student->hasProfilePhoto(),
                'url' => ['profile/edit-profile', '#' => 'section-personal'],
            ],
        ];
    }

    /**
     * Weighted completion: base tasks share 85%, verified student ID adds 15%.
     * Without a matched/approved ID, maximum completion is 85%.
     */
    public function dashboardPercent(Student $student): int
    {
        $tasks = $this->dashboardTasks($student);
        $weights = [
            'university' => 17,
            'student_id' => 17,
            'field_of_study' => 17,
            'cv' => 17,
            'profile_photo' => 17,
            'id_document' => 15,
        ];

        $percent = 0;
        foreach ($weights as $key => $weight) {
            if (!empty($tasks[$key]['done'])) {
                $percent += $weight;
            }
        }

        return min(100, $percent);
    }

    public function isProfileComplete(Student $student): bool
    {
        return $this->dashboardPercent($student) >= 100;
    }
}
