<?php

namespace common\services;

use Smalot\PdfParser\Parser as PdfParser;
use Yii;

/**
 * Extracts raw text from student ID documents (PDF text layer or Tesseract OCR).
 */
class StudentIdOcrService
{
    public const LOW_CONFIDENCE_THRESHOLD = 50;

    /**
     * @return array{
     *   text: string,
     *   confidence: int,
     *   method: string,
     *   error: string|null,
     *   debug: array<string, mixed>
     * }
     */
    public function extractText(string $absolutePath): array
    {
        $debug = [
            'uploaded_file_path' => $absolutePath,
            'file_size' => is_file($absolutePath) ? (int) filesize($absolutePath) : null,
            'image_width' => null,
            'image_height' => null,
            'preprocessing_steps' => [],
            'ocr_command' => null,
            'shell_output' => [],
            'shell_exit_code' => null,
            'tesseract_binary' => null,
            'tesseract_version' => null,
            'failure_stage' => null,
            'raw_text' => '',
        ];

        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            $debug['failure_stage'] = 'before_ocr';
            $this->logOcrDebug('ocr_file_unreadable', $debug);

            return $this->buildResult('', 0, 'none', 'File is missing or unreadable.', $debug);
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        $imageInfo = @getimagesize($absolutePath);
        if (is_array($imageInfo)) {
            $debug['image_width'] = (int) ($imageInfo[0] ?? 0);
            $debug['image_height'] = (int) ($imageInfo[1] ?? 0);
        }

        try {
            if ($ext === 'pdf') {
                return $this->extractFromPdf($absolutePath, $debug);
            }

            if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                return $this->extractFromImage($absolutePath, $debug);
            }
        } catch (\Throwable $e) {
            Yii::warning('OCR extraction failed: ' . $e->getMessage(), __METHOD__);
            $debug['failure_stage'] = 'during_ocr';
            $debug['exception'] = $e->getMessage();
            $this->logOcrDebug('ocr_exception', $debug);

            return $this->buildResult('', 0, 'error', 'OCR extraction failed.', $debug);
        }

        $debug['failure_stage'] = 'before_ocr';
        $this->logOcrDebug('ocr_unsupported_type', $debug);

        return $this->buildResult('', 0, 'none', 'Unsupported file type for OCR.', $debug);
    }

    /**
     * @param array<string, mixed> $debug
     * @return array{text: string, confidence: int, method: string, error: string|null, debug: array<string, mixed>}
     */
    private function extractFromPdf(string $absolutePath, array $debug): array
    {
        $debug['ocr_command'] = 'Smalot\\PdfParser\\Parser::parseFile()';

        $parser = new PdfParser();
        $pdf = $parser->parseFile($absolutePath);
        $text = $this->normalizeWhitespace((string) $pdf->getText());
        $debug['raw_text'] = $text;

        if ($text === '') {
            $debug['failure_stage'] = 'during_ocr';
            $this->logOcrDebug('ocr_pdf_empty', $debug);

            return $this->buildResult(
                '',
                15,
                'pdf_empty',
                'PDF has no extractable text. Scanned PDFs require manual review.',
                $debug
            );
        }

        $confidence = min(95, 40 + (int) min(55, strlen($text) / 8));
        $this->logOcrDebug('ocr_pdf_success', $debug, $confidence);

        return $this->buildResult($text, $confidence, 'pdf_text', null, $debug);
    }

    /**
     * @param array<string, mixed> $debug
     * @return array{text: string, confidence: int, method: string, error: string|null, debug: array<string, mixed>}
     */
    private function extractFromImage(string $absolutePath, array $debug): array
    {
        if (!$this->isValidImage($absolutePath)) {
            $debug['failure_stage'] = 'before_ocr';
            $this->logOcrDebug('ocr_invalid_image', $debug);

            return $this->buildResult('', 0, 'invalid_image', 'Corrupted or invalid image file.', $debug);
        }

        $tesseractDiag = $this->diagnoseTesseract();
        $debug['tesseract_binary'] = $tesseractDiag['resolved_path'];
        $debug['tesseract_version'] = $tesseractDiag['version'];
        $debug['tesseract_candidates'] = $tesseractDiag['candidates'];

        $tesseract = $tesseractDiag['resolved_path'];
        if ($tesseract === null || !$tesseractDiag['available']) {
            $debug['failure_stage'] = 'before_ocr';
            $debug['shell_output'] = $tesseractDiag['version_output'];
            $this->logOcrDebug('ocr_tesseract_unavailable', $debug);

            return $this->buildResult(
                '',
                20,
                'tesseract_unavailable',
                'Image OCR unavailable. Tesseract not found or not executable. Document sent for manual review.',
                $debug
            );
        }

        $preprocessor = new StudentIdImagePreprocessor();
        $preprocessed = $preprocessor->preprocess($absolutePath);
        $ocrInputPath = $preprocessed['path'];
        $debug['preprocessing_steps'] = $preprocessed['steps'];
        if ($preprocessed['width'] !== null) {
            $debug['image_width'] = $preprocessed['width'];
        }
        if ($preprocessed['height'] !== null) {
            $debug['image_height'] = $preprocessed['height'];
        }
        if ($preprocessed['file_size'] !== null && $preprocessed['temp']) {
            $debug['preprocessed_file_size'] = $preprocessed['file_size'];
        }

        $outputBase = tempnam(sys_get_temp_dir(), 'idocr_');
        if ($outputBase === false) {
            throw new \RuntimeException('Could not create temporary OCR output file.');
        }

        $cmd = escapeshellarg($tesseract)
            . ' ' . escapeshellarg($ocrInputPath)
            . ' ' . escapeshellarg($outputBase)
            . ' -l eng --psm 6 2>&1';

        $debug['ocr_command'] = $cmd;

        $shellOutput = [];
        $exitCode = 1;
        @exec($cmd, $shellOutput, $exitCode);
        $debug['shell_output'] = $shellOutput;
        $debug['shell_exit_code'] = $exitCode;

        if ($preprocessed['temp'] && is_file($ocrInputPath)) {
            @unlink($ocrInputPath);
        }

        $textFile = $outputBase . '.txt';
        $rawText = is_file($textFile)
            ? (string) file_get_contents($textFile)
            : '';
        $text = $this->normalizeWhitespace($rawText);
        $debug['raw_text'] = $text;

        @unlink($textFile);
        @unlink($outputBase);

        if ($text === '' || $exitCode !== 0) {
            $debug['failure_stage'] = 'during_ocr';
            $this->logOcrDebug('ocr_tesseract_low', $debug);

            return $this->buildResult(
                '',
                25,
                'tesseract_low',
                'Could not read text from image. Manual review required.',
                $debug
            );
        }

        $confidence = min(92, 35 + (int) min(57, strlen($text) / 6));
        $this->logOcrDebug('ocr_tesseract_success', $debug, $confidence);

        return $this->buildResult($text, $confidence, 'tesseract', null, $debug);
    }

    /**
     * @return array{
     *   available: bool,
     *   resolved_path: string|null,
     *   version: string|null,
     *   version_output: string[],
     *   language_packs: string[],
     *   candidates: array<int, array{path: string, exists: bool, executable: bool, version: string|null}>
     * }
     */
    public function diagnoseTesseract(): array
    {
        $configured = Yii::$app->params['studentId.tesseractPath'] ?? null;
        $candidates = [];

        if (is_string($configured) && $configured !== '') {
            $candidates[] = $configured;
        }

        $candidates = array_merge($candidates, [
            'tesseract',
            'C:\\Program Files\\Tesseract-OCR\\tesseract.exe',
            'C:\\Program Files (x86)\\Tesseract-OCR\\tesseract.exe',
        ]);

        $seen = [];
        $candidateReport = [];
        $resolved = null;
        $version = null;
        $versionOutput = [];

        foreach ($candidates as $candidate) {
            if (isset($seen[$candidate])) {
                continue;
            }
            $seen[$candidate] = true;

            $exists = str_contains($candidate, DIRECTORY_SEPARATOR) || str_contains($candidate, '/')
                ? is_file($candidate)
                : $this->isExecutableBinary($candidate);

            $executable = $exists && $this->canRunBinary($candidate);
            $candidateVersion = $executable ? $this->fetchTesseractVersion($candidate) : null;

            $candidateReport[] = [
                'path' => $candidate,
                'exists' => $exists,
                'executable' => $executable,
                'version' => $candidateVersion,
            ];

            if ($resolved === null && $executable) {
                $resolved = $candidate;
                $version = $candidateVersion;
                $versionOutput = $candidateVersion !== null ? explode("\n", $candidateVersion) : [];
            }
        }

        return [
            'available' => $resolved !== null,
            'resolved_path' => $resolved,
            'version' => $version,
            'version_output' => $versionOutput,
            'language_packs' => $resolved !== null ? $this->listLanguagePacks($resolved) : [],
            'candidates' => $candidateReport,
        ];
    }

    /**
     * @return string[]
     */
    private function listLanguagePacks(string $binary): array
    {
        $output = [];
        @exec(escapeshellarg($binary) . ' --list-langs 2>&1', $output, $code);

        if ($code !== 0) {
            return [];
        }

        return array_values(array_filter($output, static fn(string $line): bool => !str_starts_with($line, 'List of')));
    }

    private function fetchTesseractVersion(string $binary): ?string
    {
        $output = [];
        @exec(escapeshellarg($binary) . ' --version 2>&1', $output, $code);

        if ($code !== 0 || $output === []) {
            return null;
        }

        return trim(implode("\n", $output));
    }

    private function canRunBinary(string $path): bool
    {
        if (str_contains($path, DIRECTORY_SEPARATOR) || str_contains($path, '/')) {
            return is_file($path) && is_readable($path);
        }

        return $this->isExecutableBinary($path);
    }

    private function isValidImage(string $path): bool
    {
        $info = @getimagesize($path);

        return is_array($info) && in_array($info[2] ?? 0, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true);
    }

    private function resolveTesseractBinary(): ?string
    {
        return $this->diagnoseTesseract()['resolved_path'];
    }

    private function isExecutableBinary(string $path): bool
    {
        if (str_contains($path, DIRECTORY_SEPARATOR) || str_contains($path, '/')) {
            return is_file($path);
        }

        $which = stripos(PHP_OS, 'WIN') === 0 ? 'where' : 'which';
        $output = [];
        @exec($which . ' ' . escapeshellarg($path) . ' 2>NUL', $output, $code);

        return $code === 0 && !empty($output);
    }

    private function normalizeWhitespace(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * @param array<string, mixed> $debug
     * @return array{text: string, confidence: int, method: string, error: string|null, debug: array<string, mixed>}
     */
    private function buildResult(string $text, int $confidence, string $method, ?string $error, array $debug): array
    {
        $debug['raw_text'] = $text;
        $debug['confidence'] = $confidence;
        $debug['method'] = $method;
        $debug['low_ocr_confidence'] = $confidence < self::LOW_CONFIDENCE_THRESHOLD;

        return [
            'text' => $text,
            'confidence' => $confidence,
            'method' => $method,
            'error' => $error,
            'debug' => $debug,
        ];
    }

    /**
     * @param array<string, mixed> $debug
     */
    private function logOcrDebug(string $event, array $debug, ?int $confidence = null): void
    {
        Yii::info([
            'event' => $event,
            'uploaded_file_path' => $debug['uploaded_file_path'] ?? null,
            'file_size' => $debug['file_size'] ?? null,
            'image_width' => $debug['image_width'] ?? null,
            'image_height' => $debug['image_height'] ?? null,
            'preprocessing_steps' => $debug['preprocessing_steps'] ?? [],
            'ocr_command' => $debug['ocr_command'] ?? null,
            'shell_exit_code' => $debug['shell_exit_code'] ?? null,
            'shell_output' => $debug['shell_output'] ?? [],
            'tesseract_binary' => $debug['tesseract_binary'] ?? null,
            'failure_stage' => $debug['failure_stage'] ?? null,
            'ocr_confidence' => $confidence ?? ($debug['confidence'] ?? null),
            'raw_text_length' => strlen((string) ($debug['raw_text'] ?? '')),
            'raw_text' => $debug['raw_text'] ?? '',
        ], __METHOD__);
    }
}
