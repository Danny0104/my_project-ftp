<?php

namespace common\services;

use common\models\Student;
use Yii;
use yii\web\UploadedFile;

/**
 * Secure storage for student university ID documents (not web-public).
 */
class StudentIdDocumentService
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    public function upload(Student $student, UploadedFile $file): string
    {
        $this->validateFile($file);
        $ext = strtolower((string) $file->extension);
        $dir = $this->storageDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $this->deleteFiles($student);
        $basename = 'student_' . $student->id . '.' . $ext;
        $absolute = $dir . DIRECTORY_SEPARATOR . $basename;
        if (!$file->saveAs($absolute)) {
            throw new \RuntimeException('Could not save student ID document.');
        }

        @chmod($absolute, 0640);
        $relative = 'student-id/' . $basename;
        $this->validateFileIntegrity($absolute, $ext);

        $student->id_document_path = $relative;
        $student->id_uploaded_at = time();
        $this->resetVerificationFields($student);

        return $relative;
    }

    public function remove(Student $student): void
    {
        $this->deleteFiles($student);
        $student->id_document_path = null;
        $student->id_uploaded_at = null;
        $this->resetVerificationFields($student);
        $student->id_verification_status = Student::ID_VERIFICATION_NONE;
    }

    public function resetVerificationFields(Student $student): void
    {
        $student->id_verification_status = Student::ID_VERIFICATION_PENDING;
        $student->id_verified_at = null;
        $student->id_verified_by = null;
        $student->id_rejection_reason = null;
        $student->id_ocr_data = null;
        $student->id_ocr_confidence = null;
        $student->id_ocr_debug = null;
        $student->id_verification_score = null;
        $student->id_verification_method = Student::ID_METHOD_NONE;
        $student->id_verification_checks = null;
        $student->id_document_hash = null;
        $student->id_fraud_flag = false;
        $student->id_fraud_reason = null;
    }

    private function validateFileIntegrity(string $absolutePath, string $ext): void
    {
        if ($ext === 'pdf') {
            $header = (string) @file_get_contents($absolutePath, false, null, 0, 5);
            if (!str_starts_with($header, '%PDF-')) {
                @unlink($absolutePath);
                throw new \InvalidArgumentException('Corrupted or invalid PDF file.');
            }
            return;
        }

        if (!@getimagesize($absolutePath)) {
            @unlink($absolutePath);
            throw new \InvalidArgumentException('Corrupted or invalid image file.');
        }
    }

    public function hasDocument(Student $student): bool
    {
        return $this->resolveAbsolutePath($student) !== null;
    }

    public function resolveAbsolutePath(Student $student): ?string
    {
        $relative = $this->resolveRelativePath($student);
        if ($relative === null) {
            return null;
        }

        $absolute = $this->storageDir() . DIRECTORY_SEPARATOR . basename($relative);

        return is_file($absolute) ? $absolute : null;
    }

    /**
     * Path resolution diagnostics for upload/OCR debugging.
     *
     * @return array{
     *   db_path: string|null,
     *   relative_path: string|null,
     *   absolute_path: string|null,
     *   storage_dir: string,
     *   expected_file: string|null,
     *   file_exists: bool,
     *   is_readable: bool,
     *   relative_path_valid: bool
     * }
     */
    public function getPathDiagnostics(Student $student): array
    {
        $dbPath = trim((string) $student->id_document_path) ?: null;
        $relative = $this->resolveRelativePath($student);
        $storageDir = $this->storageDir();
        $expectedFile = $relative !== null
            ? $storageDir . DIRECTORY_SEPARATOR . basename($relative)
            : ($dbPath !== null ? $storageDir . DIRECTORY_SEPARATOR . basename($dbPath) : null);
        $absolute = $this->resolveAbsolutePath($student);

        return [
            'db_path' => $dbPath,
            'relative_path' => $relative,
            'absolute_path' => $absolute,
            'storage_dir' => $storageDir,
            'expected_file' => $expectedFile,
            'file_exists' => $expectedFile !== null && file_exists($expectedFile),
            'is_readable' => $expectedFile !== null && is_readable($expectedFile),
            'relative_path_valid' => $relative !== null,
        ];
    }

    public function resolveRelativePath(Student $student): ?string
    {
        $path = trim((string) $student->id_document_path);
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        if (!preg_match('#^student-id/student_\d+\.(jpg|jpeg|png|pdf)$#i', $path)) {
            return null;
        }

        return $path;
    }

    public function mimeType(string $absolutePath): string
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
    }

    public function isImage(string $absolutePath): bool
    {
        return str_starts_with($this->mimeType($absolutePath), 'image/');
    }

    public function downloadFilename(Student $student): string
    {
        $relative = $this->resolveRelativePath($student);

        return $relative !== null ? basename($relative) : 'student-id.pdf';
    }

    private function validateFile(UploadedFile $file): void
    {
        $ext = strtolower((string) $file->extension);
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('ID document must be JPG, JPEG, PNG, or PDF.');
        }
        if ($file->size > self::MAX_BYTES) {
            throw new \InvalidArgumentException('ID document must be 5 MB or smaller.');
        }
    }

    private function storageDir(): string
    {
        return Yii::getAlias('@common/runtime/student-id-documents');
    }

    private function deleteFiles(Student $student): void
    {
        $relative = $this->resolveRelativePath($student);
        if ($relative === null) {
            return;
        }

        $absolute = $this->storageDir() . DIRECTORY_SEPARATOR . basename($relative);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
}
