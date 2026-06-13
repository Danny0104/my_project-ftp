<?php

namespace common\services;

use common\models\Organization;
use common\models\Student;
use Yii;
use yii\helpers\Url;
use yii\web\UploadedFile;

/**
 * Organization logos and student profile photos.
 */
class ProfileImageService
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** @var array<string, int> */
    public const SIZES = [
        'xs' => 32,
        'sm' => 48,
        'md' => 64,
        'lg' => 128,
        'xl' => 200,
    ];

    public function uploadOrganizationLogo(Organization $org, UploadedFile $file): string
    {
        $this->validateImageFile($file);
        $ext = strtolower((string) $file->extension);
        $dir = $this->organizationLogoDir();
        $basename = 'org_' . $org->id;
        $relative = 'uploads/organizations/logos/' . $basename . '.' . $ext;
        $absolute = $this->webRoot() . '/' . $relative;

        $this->deleteOrganizationFiles($org);
        if (!$file->saveAs($absolute)) {
            throw new \RuntimeException('Could not save organization logo.');
        }

        $this->generateThumbnails($absolute, $dir, $basename);
        $org->logo = $relative;

        return $relative;
    }

    public function uploadStudentPhoto(Student $student, UploadedFile $file): string
    {
        $this->validateImageFile($file);
        $ext = strtolower((string) $file->extension);
        $dir = $this->studentPhotoDir();
        $basename = 'student_' . $student->id;
        $relative = 'uploads/students/photos/' . $basename . '.' . $ext;
        $absolute = $this->webRoot() . '/' . $relative;

        $this->deleteStudentFiles($student);
        if (!$file->saveAs($absolute)) {
            throw new \RuntimeException('Could not save profile photo.');
        }

        $this->generateThumbnails($absolute, $dir, $basename);
        $student->profile_photo = $relative;

        return $relative;
    }

    public function removeOrganizationLogo(Organization $org): void
    {
        $this->deleteOrganizationFiles($org);
        $org->logo = null;
    }

    public function removeStudentPhoto(Student $student): void
    {
        $this->deleteStudentFiles($student);
        $student->profile_photo = null;
    }

    public function organizationLogoUrl(?Organization $org, string $size = 'md'): ?string
    {
        if ($org === null) {
            return null;
        }

        $relative = $this->normalizeOrganizationLogoPath($org->logo);
        if ($relative === null) {
            return null;
        }

        return $this->resolveDisplayUrl($relative, 'org_' . $org->id, $size);
    }

    public function studentPhotoUrl(?Student $student, string $size = 'md'): ?string
    {
        if ($student === null) {
            return null;
        }

        $relative = $this->normalizeStudentPhotoPath($student->profile_photo);
        if ($relative === null) {
            return null;
        }

        return $this->resolveDisplayUrl($relative, 'student_' . $student->id, $size);
    }

    public function organizationHasLogo(?Organization $org): bool
    {
        return $org !== null && $this->organizationLogoUrl($org) !== null;
    }

    public function studentHasPhoto(?Student $student): bool
    {
        return $student !== null && $this->studentPhotoUrl($student) !== null;
    }

    public function organizationInitials(?Organization $org): string
    {
        $name = trim((string) ($org->name ?? ''));
        if ($name === '') {
            return 'OR';
        }

        return $this->initialsFromName($name, 'OR');
    }

    public function studentInitials(?Student $student): string
    {
        $name = trim((string) ($student->user->username ?? $student->student_id ?? ''));
        if ($name === '') {
            return 'ST';
        }

        return $this->initialsFromName($name, 'ST');
    }

    public function organizationLogoUrlById(?int $organizationId, string $size = 'md'): ?string
    {
        if (!$organizationId) {
            return null;
        }

        return $this->organizationLogoUrl(Organization::findOne($organizationId), $size);
    }

    public function studentPhotoUrlByUserId(?int $userId, string $size = 'md'): ?string
    {
        if (!$userId) {
            return null;
        }

        return $this->studentPhotoUrl(Student::findOne(['user_id' => $userId]), $size);
    }

    private function validateImageFile(UploadedFile $file): void
    {
        $ext = strtolower((string) $file->extension);
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Image must be JPG, JPEG, PNG, or WEBP.');
        }
        if ($file->size > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Image must be 5 MB or smaller.');
        }
    }

    private function normalizeOrganizationLogoPath(?string $path): ?string
    {
        $path = $this->normalizeRelativePath($path);
        if ($path === null) {
            return null;
        }

        if (preg_match('#^uploads/(organizations/logos|logos)/.+\.(jpg|jpeg|png|webp)$#i', $path)) {
            $absolute = $this->webRoot() . '/' . $path;
            return is_file($absolute) ? $path : null;
        }

        return null;
    }

    private function normalizeStudentPhotoPath(?string $path): ?string
    {
        $path = $this->normalizeRelativePath($path);
        if ($path === null) {
            return null;
        }

        if (preg_match('#^uploads/students/photos/.+\.(jpg|jpeg|png|webp)$#i', $path)) {
            $absolute = $this->webRoot() . '/' . $path;
            return is_file($absolute) ? $path : null;
        }

        return null;
    }

    private function normalizeRelativePath(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#^@web/#', '', $path);
        $path = preg_replace('#^/?frontend/web/#', '', $path);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }

    private function resolveDisplayUrl(string $relative, string $basename, string $size): string
    {
        $thumbRelative = $this->thumbRelativePath($relative, $basename, $size);
        $thumbAbsolute = $this->webRoot() . '/' . $thumbRelative;
        if (is_file($thumbAbsolute)) {
            return $this->publicUrl($thumbRelative, filemtime($thumbAbsolute) ?: null);
        }

        $absolute = $this->webRoot() . '/' . $relative;
        return $this->publicUrl($relative, is_file($absolute) ? filemtime($absolute) : null);
    }

    private function thumbRelativePath(string $relative, string $basename, string $size): string
    {
        $dir = dirname($relative);
        $px = self::SIZES[$size] ?? self::SIZES['md'];

        return $dir . '/thumbs/' . $basename . '_' . $px . '.jpg';
    }

    private function publicUrl(string $relative, ?int $mtime = null): string
    {
        $url = Url::to('@web/' . ltrim($relative, '/'), true);
        if ($mtime) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . $mtime;
        }

        return $url;
    }

    private function generateThumbnails(string $source, string $dir, string $basename): void
    {
        if (!function_exists('imagecreatefromstring')) {
            return;
        }

        $thumbDir = $dir . '/thumbs';
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $data = @file_get_contents($source);
        if ($data === false) {
            return;
        }

        $image = @imagecreatefromstring($data);
        if ($image === false) {
            return;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width < 1 || $height < 1) {
            imagedestroy($image);
            return;
        }

        foreach (self::SIZES as $px) {
            $thumb = imagecreatetruecolor($px, $px);
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $side = min($width, $height);
            $srcX = (int) (($width - $side) / 2);
            $srcY = (int) (($height - $side) / 2);
            imagecopyresampled($thumb, $image, 0, 0, $srcX, $srcY, $px, $px, $side, $side);
            imagejpeg($thumb, $thumbDir . '/' . $basename . '_' . $px . '.jpg', 88);
            imagedestroy($thumb);
        }

        imagedestroy($image);
    }

    private function deleteOrganizationFiles(Organization $org): void
    {
        $relative = $this->normalizeOrganizationLogoPath($org->logo);
        if ($relative !== null) {
            $this->unlinkIfExists($this->webRoot() . '/' . $relative);
            $this->deleteThumbs(dirname($relative), 'org_' . $org->id);
        }
        // Legacy path
        foreach (self::ALLOWED_EXTENSIONS as $ext) {
            $legacy = $this->webRoot() . '/uploads/logos/org_' . $org->id . '.' . $ext;
            $this->unlinkIfExists($legacy);
        }
    }

    private function deleteStudentFiles(Student $student): void
    {
        $relative = $this->normalizeStudentPhotoPath($student->profile_photo);
        if ($relative !== null) {
            $this->unlinkIfExists($this->webRoot() . '/' . $relative);
            $this->deleteThumbs(dirname($relative), 'student_' . $student->id);
        }
    }

    private function deleteThumbs(string $relativeDir, string $basename): void
    {
        $thumbDir = $this->webRoot() . '/' . $relativeDir . '/thumbs';
        if (!is_dir($thumbDir)) {
            return;
        }
        foreach (self::SIZES as $px) {
            $this->unlinkIfExists($thumbDir . '/' . $basename . '_' . $px . '.jpg');
        }
    }

    private function unlinkIfExists(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function organizationLogoDir(): string
    {
        $dir = $this->webRoot() . '/uploads/organizations/logos';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function studentPhotoDir(): string
    {
        $dir = $this->webRoot() . '/uploads/students/photos';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    private function webRoot(): string
    {
        return Yii::getAlias('@frontend/web');
    }

    public function initialsFromName(string $name, string $fallback = '?'): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(mb_substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : $fallback;
    }
}
