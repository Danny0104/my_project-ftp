<?php

namespace common\services;

use Yii;

/**
 * Prepares student ID images for Tesseract OCR (grayscale, contrast, denoise, sharpen, resize).
 */
class StudentIdImagePreprocessor
{
    /** Target width for ~300 DPI on a typical ID card (~85mm wide). */
    private const TARGET_WIDTH_PX = 1000;

    /**
     * @return array{path: string, steps: string[], width: int|null, height: int|null, file_size: int|null, temp: bool}
     */
    public function preprocess(string $absolutePath): array
    {
        $steps = [];
        $info = @getimagesize($absolutePath);
        if (!is_array($info)) {
            return [
                'path' => $absolutePath,
                'steps' => ['skipped: invalid image'],
                'width' => null,
                'height' => null,
                'file_size' => is_file($absolutePath) ? (int) filesize($absolutePath) : null,
                'temp' => false,
            ];
        }

        if (!extension_loaded('gd')) {
            $steps[] = 'skipped: GD extension unavailable';

            return [
                'path' => $absolutePath,
                'steps' => $steps,
                'width' => (int) ($info[0] ?? 0),
                'height' => (int) ($info[1] ?? 0),
                'file_size' => (int) filesize($absolutePath),
                'temp' => false,
            ];
        }

        $source = $this->loadImage($absolutePath, (int) ($info[2] ?? 0));
        if ($source === null) {
            $steps[] = 'skipped: could not load image';

            return [
                'path' => $absolutePath,
                'steps' => $steps,
                'width' => (int) ($info[0] ?? 0),
                'height' => (int) ($info[1] ?? 0),
                'file_size' => (int) filesize($absolutePath),
                'temp' => false,
            ];
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width <= 0 || $height <= 0) {
            imagedestroy($source);
            $steps[] = 'skipped: zero dimensions';

            return [
                'path' => $absolutePath,
                'steps' => $steps,
                'width' => $width,
                'height' => $height,
                'file_size' => (int) filesize($absolutePath),
                'temp' => false,
            ];
        }

        $working = $source;
        $steps[] = 'loaded source image';

        if ($width < self::TARGET_WIDTH_PX) {
            $scale = self::TARGET_WIDTH_PX / $width;
            $newWidth = self::TARGET_WIDTH_PX;
            $newHeight = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            if ($resized !== false) {
                imagecopyresampled($resized, $working, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                if ($working !== $source) {
                    imagedestroy($working);
                }
                imagedestroy($source);
                $working = $resized;
                $width = $newWidth;
                $height = $newHeight;
                $steps[] = 'resized to ~300 DPI equivalent (' . $newWidth . 'x' . $newHeight . ')';
            }
        } elseif ($width > self::TARGET_WIDTH_PX * 2) {
            $scale = (self::TARGET_WIDTH_PX * 2) / $width;
            $newWidth = (int) round($width * $scale);
            $newHeight = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            if ($resized !== false) {
                imagecopyresampled($resized, $working, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                if ($working !== $source) {
                    imagedestroy($working);
                }
                imagedestroy($source);
                $working = $resized;
                $width = $newWidth;
                $height = $newHeight;
                $steps[] = 'downscaled oversized image (' . $newWidth . 'x' . $newHeight . ')';
            }
        }

        if ($this->shouldCorrectOrientation($width, $height)) {
            $rotated = imagerotate($working, 90, 0);
            if ($rotated !== false) {
                imagedestroy($working);
                $working = $rotated;
                $width = imagesx($working);
                $height = imagesy($working);
                $steps[] = 'orientation corrected (portrait → landscape)';
            }
        }

        $gray = imagecreatetruecolor($width, $height);
        if ($gray !== false) {
            imagecopy($gray, $working, 0, 0, 0, 0, $width, $height);
            imagefilter($gray, IMG_FILTER_GRAYSCALE);
            imagedestroy($working);
            $working = $gray;
            $steps[] = 'grayscale conversion';
        }

        imagefilter($working, IMG_FILTER_CONTRAST, -35);
        $steps[] = 'contrast enhancement';

        imagefilter($working, IMG_FILTER_BRIGHTNESS, 5);
        $steps[] = 'brightness adjustment';

        if (defined('IMG_FILTER_SMOOTH')) {
            imagefilter($working, IMG_FILTER_SMOOTH, -2);
            $steps[] = 'denoising (smooth)';
        }

        if (function_exists('imageconvolution')) {
            $sharpenMatrix = [
                [-1, -1, -1],
                [-1, 16, -1],
                [-1, -1, -1],
            ];
            imageconvolution($working, $sharpenMatrix, 8, 0);
            $steps[] = 'sharpening (convolution kernel)';
        }

        imagefilter($working, IMG_FILTER_CONTRAST, -10);
        $steps[] = 'final contrast pass';

        $outputPath = tempnam(sys_get_temp_dir(), 'idocr_prep_');
        if ($outputPath === false) {
            imagedestroy($working);
            $steps[] = 'failed: temp file creation';

            return [
                'path' => $absolutePath,
                'steps' => $steps,
                'width' => $width,
                'height' => $height,
                'file_size' => (int) filesize($absolutePath),
                'temp' => false,
            ];
        }

        $pngPath = $outputPath . '.png';
        @unlink($outputPath);
        if (!imagepng($working, $pngPath, 1)) {
            imagedestroy($working);
            @unlink($pngPath);
            $steps[] = 'failed: could not write preprocessed PNG';

            return [
                'path' => $absolutePath,
                'steps' => $steps,
                'width' => $width,
                'height' => $height,
                'file_size' => (int) filesize($absolutePath),
                'temp' => false,
            ];
        }

        imagedestroy($working);
        $steps[] = 'saved preprocessed PNG';

        return [
            'path' => $pngPath,
            'steps' => $steps,
            'width' => $width,
            'height' => $height,
            'file_size' => (int) filesize($pngPath),
            'temp' => true,
        ];
    }

    private function loadImage(string $path, int $type): ?\GdImage
    {
        return match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path) ?: null,
            IMAGETYPE_PNG => @imagecreatefrompng($path) ?: null,
            default => null,
        };
    }

    private function shouldCorrectOrientation(int $width, int $height): bool
    {
        return $height > $width * 1.25;
    }
}
