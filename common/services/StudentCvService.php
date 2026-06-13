<?php

namespace common\services;

use common\models\Student;
use Yii;

/**
 * Resolves and validates student CV files stored under frontend/web/uploads.
 */
class StudentCvService
{
    public function resolveRelativePath(Student $student): ?string
    {
        $cv = trim((string) $student->cv);
        if ($cv === '') {
            return null;
        }

        $cv = str_replace('\\', '/', $cv);
        $cv = preg_replace('#^@web/#', '', $cv);
        $cv = preg_replace('#^/?frontend/web/#', '', $cv);
        $cv = ltrim($cv, '/');

        if ($cv === '' || str_contains($cv, '..')) {
            return null;
        }

        if (!preg_match('#^uploads/.+\.(pdf|doc|docx)$#i', $cv)) {
            if (preg_match('#^cv_\d+\.(pdf|doc|docx)$#i', $cv)) {
                $cv = 'uploads/' . $cv;
            } else {
                return null;
            }
        }

        return $cv;
    }

    public function hasCvFile(Student $student): bool
    {
        return $this->resolveAbsolutePath($student) !== null;
    }

    /**
     * @return array{available:bool,relative:?string,absolute:?string,filename:?string}
     */
    public function describe(Student $student): array
    {
        $relative = $this->resolveRelativePath($student);
        $absolute = $relative !== null ? $this->resolveAbsolutePath($student) : null;

        return [
            'available' => $absolute !== null,
            'relative' => $relative,
            'absolute' => $absolute,
            'filename' => $relative !== null ? basename($relative) : null,
        ];
    }

    public function resolveAbsolutePath(Student $student): ?string
    {
        $relative = $this->resolveRelativePath($student);
        if ($relative === null) {
            return null;
        }

        $absolute = Yii::getAlias('@frontend/web/' . $relative);

        return is_file($absolute) ? $absolute : null;
    }

    public function downloadFilename(Student $student): string
    {
        $relative = $this->resolveRelativePath($student);

        return $relative !== null ? basename($relative) : 'cv.pdf';
    }

    public function mimeType(string $absolutePath): string
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }
}
