<?php

namespace common\services;

use common\models\Student;
use Yii;

/**
 * Detects duplicate IDs, registration numbers, and suspicious reuse.
 */
class StudentIdFraudDetectionService
{
    /**
     * @return array{flagged: bool, reason: string|null, signals: string[]}
     */
    public function analyze(Student $student, string $documentHash, ?string $extractedRegistration): array
    {
        $signals = [];

        $duplicateDoc = Student::find()
            ->where(['id_document_hash' => $documentHash])
            ->andWhere(['not', ['id' => $student->id]])
            ->andWhere(['not', ['id_document_path' => null]])
            ->one();

        if ($duplicateDoc instanceof Student) {
            $signals[] = 'Same ID document image used by another account (student #' . $duplicateDoc->id . ').';
        }

        $profileReg = trim((string) $student->student_id);
        if ($profileReg !== '') {
            $duplicateReg = Student::find()
                ->where(['student_id' => $profileReg])
                ->andWhere(['not', ['id' => $student->id]])
                ->andWhere(['not', ['user_id' => $student->user_id]])
                ->one();

            if ($duplicateReg instanceof Student) {
                $signals[] = 'Registration number already registered to another student account.';
            }
        }

        if ($extractedRegistration !== null && $extractedRegistration !== '') {
            $extractedReg = (new StudentIdTextParser())->normalizeRegistrationNumber($extractedRegistration);
            $duplicateExtracted = Student::find()
                ->where(['or',
                    ['student_id' => $extractedReg],
                    ['like', 'id_ocr_data', $extractedReg],
                ])
                ->andWhere(['not', ['id' => $student->id]])
                ->one();

            if ($duplicateExtracted instanceof Student) {
                $signals[] = 'Extracted registration number matches another student profile.';
            }
        }

        if (empty($signals)) {
            return ['flagged' => false, 'reason' => null, 'signals' => []];
        }

        return [
            'flagged' => true,
            'reason' => 'Potential duplicate identity detected: ' . $signals[0],
            'signals' => $signals,
        ];
    }

    public function computeDocumentHash(string $absolutePath): string
    {
        $hash = @hash_file('sha256', $absolutePath);

        return $hash !== false ? $hash : hash('sha256', (string) @file_get_contents($absolutePath));
    }
}
