<?php

namespace common\services;

use common\models\Position;
use common\models\Student;
use common\models\User;
use Yii;
use yii\web\UploadedFile;

/**
 * Parses and validates organization-defined application questions.
 */
class ApplicationQuestionService
{
    public const TYPE_SHORT = 'short';
    public const TYPE_LONG = 'long';
    public const TYPE_CHOICE = 'choice';
    public const TYPE_FILE = 'file';

    /** @var string[] */
    private const ALLOWED_TYPES = [
        self::TYPE_SHORT,
        self::TYPE_LONG,
        self::TYPE_CHOICE,
        self::TYPE_FILE,
    ];

    /**
     * @return array<int, array{id: string, type: string, label: string, required: bool, placeholder: string, options: string[]}>
     */
    public function getQuestions(Position $position): array
    {
        $raw = $position->application_questions ?? null;
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $questions = [];
        foreach ($decoded as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $normalized = $this->normalizeQuestion($item, $index);
            if ($normalized !== null) {
                $questions[] = $normalized;
            }
        }

        return $questions;
    }

    public function hasQuestions(Position $position): bool
    {
        return $this->getQuestions($position) !== [];
    }

    /**
     * @param array<string, mixed> $textAnswers question id => value
     * @param array<string, UploadedFile> $files question id => file
     * @return array{valid: bool, errors: string[], answers: array<string, mixed>}
     */
    public function validateAnswers(Position $position, array $textAnswers, array $files = []): array
    {
        $errors = [];
        $answers = [];

        foreach ($this->getQuestions($position) as $question) {
            $id = $question['id'];
            $label = $question['label'];
            $type = $question['type'];
            $required = $question['required'];

            if ($type === self::TYPE_FILE) {
                $file = $files[$id] ?? null;
                if (!$file instanceof UploadedFile || $file->error === UPLOAD_ERR_NO_FILE) {
                    if ($required) {
                        $errors[] = "Please upload a file for: {$label}.";
                    }
                    continue;
                }
                if ($file->error !== UPLOAD_ERR_OK) {
                    $errors[] = "Could not upload file for: {$label}.";
                    continue;
                }
                if ($file->size > 5 * 1024 * 1024) {
                    $errors[] = "File for \"{$label}\" must be 5 MB or smaller.";
                    continue;
                }
                $ext = strtolower((string) $file->extension);
                if (!in_array($ext, ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], true)) {
                    $errors[] = "File for \"{$label}\" must be PDF, Word, or image format.";
                    continue;
                }
                $answers[$id] = ['type' => self::TYPE_FILE, 'originalName' => $file->name];
                continue;
            }

            $value = isset($textAnswers[$id]) ? trim((string) $textAnswers[$id]) : '';
            if ($required && $value === '') {
                $errors[] = "Please answer: {$label}.";
                continue;
            }

            if ($type === self::TYPE_CHOICE && $value !== '') {
                $options = $question['options'];
                if ($options !== [] && !in_array($value, $options, true)) {
                    $errors[] = "Invalid selection for: {$label}.";
                    continue;
                }
            }

            if ($value !== '') {
                $answers[$id] = ['type' => $type, 'value' => $value];
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'answers' => $answers,
        ];
    }

    /**
     * Persist uploaded file answers after the application row exists.
     *
     * @param array<string, array{type: string, originalName?: string}> $answers
     * @param array<string, UploadedFile> $files
     */
    public function attachUploadedFiles(int $applicationId, array &$answers, array $files): void
    {
        foreach ($answers as $questionId => &$entry) {
            if (($entry['type'] ?? '') !== self::TYPE_FILE) {
                continue;
            }
            $file = $files[$questionId] ?? null;
            if (!$file instanceof UploadedFile) {
                continue;
            }
            $path = $this->saveAnswerFile($applicationId, (string) $questionId, $file);
            if ($path !== null) {
                $entry['path'] = $path;
                $entry['originalName'] = $file->name;
            }
        }
        unset($entry);
    }

    public function encodeAnswers(array $answers): string
    {
        $encoded = json_encode($answers, JSON_UNESCAPED_UNICODE);
        return $encoded !== false ? $encoded : '{}';
    }

    public function decodeAnswers(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function saveAnswerFile(int $applicationId, string $questionId, UploadedFile $file): ?string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $questionId) ?: 'file';
        $ext = strtolower((string) $file->extension);
        $dir = Yii::getAlias('@frontend/web/uploads/application-answers');
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $basename = 'app_' . $applicationId . '_' . $safeId . '.' . $ext;
        $fullPath = $dir . DIRECTORY_SEPARATOR . $basename;
        if (!$file->saveAs($fullPath, false)) {
            return null;
        }

        return 'uploads/application-answers/' . $basename;
    }

    /**
     * @param array<string, mixed> $item
     * @return array{id: string, type: string, label: string, required: bool, placeholder: string, options: string[]}|null
     */
    private function normalizeQuestion(array $item, int $index): ?array
    {
        $label = trim((string) ($item['label'] ?? $item['question'] ?? ''));
        if ($label === '') {
            return null;
        }

        $type = strtolower(trim((string) ($item['type'] ?? self::TYPE_SHORT)));
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            $type = self::TYPE_SHORT;
        }

        $id = trim((string) ($item['id'] ?? 'q' . ($index + 1)));
        if ($id === '') {
            $id = 'q' . ($index + 1);
        }

        $options = [];
        if (isset($item['options']) && is_array($item['options'])) {
            foreach ($item['options'] as $option) {
                $opt = trim((string) $option);
                if ($opt !== '') {
                    $options[] = $opt;
                }
            }
        }

        return [
            'id' => $id,
            'type' => $type,
            'label' => $label,
            'required' => (bool) ($item['required'] ?? false),
            'placeholder' => trim((string) ($item['placeholder'] ?? '')),
            'options' => $options,
        ];
    }
}
