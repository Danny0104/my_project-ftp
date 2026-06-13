<?php

namespace common\services;

/**
 * Parses OCR text into structured student ID fields.
 */
class StudentIdTextParser
{
    /** @var array<string, string> */
    private const UNIVERSITY_ALIASES = [
        'udsm' => 'University of Dar es Salaam',
        'university of dar es salaam' => 'University of Dar es Salaam',
        'dit' => 'Dar es Salaam Institute of Technology',
        'dar es salaam institute of technology' => 'Dar es Salaam Institute of Technology',
        'ifm' => 'Institute of Finance Management',
        'institute of finance management' => 'Institute of Finance Management',
        'udom' => 'University of Dodoma',
        'university of dodoma' => 'University of Dodoma',
        'sua' => 'Sokoine University of Agriculture',
        'sokoine university of agriculture' => 'Sokoine University of Agriculture',
        'out' => 'Open University of Tanzania',
        'open university of tanzania' => 'Open University of Tanzania',
        'suza' => 'State University of Zanzibar',
        'state university of zanzibar' => 'State University of Zanzibar',
        'mzumbe university' => 'Mzumbe University',
        'mu' => 'Mzumbe University',
        'eastc' => 'Eastern Africa Statistical Training Centre',
        'eastern africa statistical training centre' => 'Eastern Africa Statistical Training Centre',
        'the eastern africa statistical training centre' => 'Eastern Africa Statistical Training Centre',
    ];

    /** @var string[] */
    private const UNIVERSITY_ABBREVIATIONS = [
        'udsm', 'dit', 'ifm', 'udom', 'sua', 'out', 'suza', 'mu', 'eastc',
    ];

    /**
     * @return array{
     *   student_name: string|null,
     *   registration_number: string|null,
     *   student_number: string|null,
     *   university_name: string|null,
     *   program: string|null,
     *   faculty: string|null,
     *   department: string|null,
     *   expiry_date: string|null
     * }
     */
    public function parse(string $text): array
    {
        return $this->parseWithDiagnostics($text)['fields'];
    }

    /**
     * @return array{
     *   fields: array{
     *     student_name: string|null,
     *     registration_number: string|null,
     *     student_number: string|null,
     *     university_name: string|null,
     *     program: string|null,
     *     faculty: string|null,
     *     department: string|null,
     *     expiry_date: string|null
     *   },
     *   parser_result: array<string, array{value: string|null, reason: string, file: string, line: int}>
     * }
     */
    public function parseWithDiagnostics(string $text): array
    {
        $normalized = $this->normalizeForParsing($text);
        $emptyInput = trim($text) === '';

        $name = $this->extractName($normalized);
        $registration = $this->extractRegistrationNumber($normalized);
        $studentNumber = $this->extractStudentNumber($normalized);
        $university = $this->extractUniversity($normalized);
        $program = $this->extractProgram($normalized);
        $faculty = $this->extractLabeledValue($normalized, ['faculty', 'school of', 'college of']);
        $department = $this->extractLabeledValue($normalized, ['department', 'dept']);
        $expiry = $this->extractExpiryDate($normalized);

        $fields = [
            'student_name' => $name,
            'registration_number' => $registration,
            'student_number' => $studentNumber,
            'university_name' => $university,
            'program' => $program,
            'faculty' => $faculty,
            'department' => $department,
            'expiry_date' => $expiry,
        ];

        $parserResult = [
            'student_name' => $this->fieldDiagnostic(
                $name,
                $emptyInput
                    ? 'OCR returned empty text — no input for name extraction'
                    : ($name === null
                        ? 'No label match (name/student name/full name) and no line matched name heuristic in StudentIdTextParser::extractName()'
                        : 'Matched via extractName()'),
                __FILE__,
                74
            ),
            'registration_number' => $this->fieldDiagnostic(
                $registration,
                $emptyInput
                    ? 'OCR returned empty text — no input for registration extraction'
                    : ($registration === null
                        ? 'No pattern matched in extractRegistrationNumber() (lines 105–123)'
                        : 'Matched registration pattern'),
                __FILE__,
                105
            ),
            'student_number' => $this->fieldDiagnostic(
                $studentNumber,
                $emptyInput
                    ? 'OCR returned empty text'
                    : ($studentNumber === null ? 'No student no. label found (extractStudentNumber line 125)' : 'Matched student number label'),
                __FILE__,
                125
            ),
            'university_name' => $this->fieldDiagnostic(
                $university,
                $emptyInput
                    ? 'OCR returned empty text — no input for university extraction'
                    : ($university === null
                        ? 'No alias, label, or institution pattern matched in extractUniversity() (lines 155–179)'
                        : 'Matched university alias or label'),
                __FILE__,
                155
            ),
            'program' => $this->fieldDiagnostic(
                $program,
                $emptyInput
                    ? 'OCR returned empty text — no input for program extraction'
                    : ($program === null
                        ? 'No program label or degree pattern matched in extractProgram() (lines 134–152)'
                        : 'Matched program label or degree pattern'),
                __FILE__,
                134
            ),
            'faculty' => $this->fieldDiagnostic(
                $faculty,
                $emptyInput ? 'OCR returned empty text' : ($faculty === null ? 'No faculty/school label found' : 'Matched faculty label'),
                __FILE__,
                182
            ),
            'department' => $this->fieldDiagnostic(
                $department,
                $emptyInput ? 'OCR returned empty text' : ($department === null ? 'No department label found' : 'Matched department label'),
                __FILE__,
                182
            ),
            'expiry_date' => $this->fieldDiagnostic(
                $expiry,
                $emptyInput
                    ? 'OCR returned empty text'
                    : ($expiry === null ? 'No expiry/valid-until date pattern matched (extractExpiryDate lines 198–211)' : 'Matched expiry date pattern'),
                __FILE__,
                198
            ),
        ];

        return [
            'fields' => $fields,
            'parser_result' => $parserResult,
        ];
    }

    /**
     * @return array{value: string|null, reason: string, file: string, line: int}
     */
    private function fieldDiagnostic(?string $value, string $reason, string $file, int $line): array
    {
        return [
            'value' => $value,
            'reason' => $reason,
            'file' => $file,
            'line' => $line,
        ];
    }

    private function normalizeForParsing(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;

        return trim($text);
    }

    private function extractName(string $text): ?string
    {
        if (preg_match('/(?:name|student name|full name)\s*[:\-]\s*([a-z][a-z \'.\-]{2,80}?)(?=\s*(?:registration|reg\b|student no|programme|program|institution|university|\n|\r|$))/i', $text, $m)) {
            return $this->cleanName($m[1]);
        }

        foreach (preg_split('/\r?\n+/', $text) as $line) {
            $line = trim($line);
            if ($line === '' || strlen($line) < 4) {
                continue;
            }
            if (preg_match('/^(university|institution|faculty|department|registration|programme|program|student no|valid|expiry|id card)/i', $line)) {
                continue;
            }
            $nameCandidate = preg_replace('/[^a-z\s\.]/', '', $line) ?? $line;
            $nameCandidate = trim(preg_replace('/\s+/', ' ', $nameCandidate) ?? $nameCandidate);
            if (preg_match('/^[a-z]+(?:[\s\.]+[a-z]\.?){0,3}[\s\.]+[a-z]+\.?$/i', $nameCandidate)) {
                return $this->cleanName($nameCandidate);
            }
        }

        return null;
    }

    private function cleanName(string $name): string
    {
        $name = preg_replace('/\s+/', ' ', trim($name)) ?? trim($name);

        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }

    private function extractRegistrationNumber(string $text): ?string
    {
        $patterns = [
            '/(?:registration(?:\s+no\.?)?|reg\.?\s*no\.?|reg no)\s*[:\-]?\s*([a-z0-9\/\-\.]{4,40})/i',
            '/\b([a-z]{2,8}\/[a-z]{2,8}\/\d{2,4}\/\d{2,8})\b/i',
            '/\b(\d{4}[\-\/\.][a-z0-9]{1,3}[\-\/\.]\d{4,12})\b/i',
            '/\b([a-z]{2,5}\/\d{4}\/\d{4}\/\d{2,5})\b/i',
            '/\b([a-z]{2,4}\d{6,12})\b/i',
            '/\b(\d{8,12})\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return strtoupper(str_replace(' ', '', $m[1]));
            }
        }

        return null;
    }

    private function extractStudentNumber(string $text): ?string
    {
        if (preg_match('/(?:student\s*(?:no\.?|number|#)|std\s*no)\s*[:\-]?\s*([a-z0-9\/\-\.]{4,40})/i', $text, $m)) {
            return strtoupper(str_replace(' ', '', $m[1]));
        }

        return null;
    }

    private function extractProgram(string $text): ?string
    {
        $program = $this->extractLabeledValue($text, [
            'programme',
            'program',
            'course of study',
            'course',
            'degree',
        ]);

        if ($program !== null) {
            return $program;
        }

        if (preg_match('/\b(bachelor[^\n]{5,80}|master[^\n]{5,80}|diploma[^\n]{5,80}|bsc[^\n]{3,60})\b/i', $text, $m)) {
            return mb_convert_case(trim($m[1]), MB_CASE_TITLE, 'UTF-8');
        }

        return null;
    }

    private function extractUniversity(string $text): ?string
    {
        foreach (self::UNIVERSITY_ALIASES as $needle => $canonical) {
            if (str_contains($text, $needle)) {
                return $canonical;
            }
        }

        $institution = $this->extractLabeledValue($text, [
            'institution',
            'university',
            'college',
            'school',
            'campus',
        ]);
        if ($institution !== null) {
            $canonical = $this->resolveUniversityCanonical($institution);
            return $canonical ?? mb_convert_case(trim($institution), MB_CASE_UPPER, 'UTF-8');
        }

        if (preg_match('/(?:university|college|institute|centre|center)\s+of\s+[a-z\s]{3,80}/i', $text, $m)) {
            return mb_convert_case(trim($m[0]), MB_CASE_TITLE, 'UTF-8');
        }

        return null;
    }

    private function extractLabeledValue(string $text, array $labels): ?string
    {
        foreach ($labels as $label) {
            $quoted = preg_quote($label, '/');
            if (preg_match('/' . $quoted . '\s*[:\-]?\s*([^\n\r]{2,120})/i', $text, $m)) {
                $value = trim($m[1]);
                $value = trim((string) preg_split('/\r?\n/', $value)[0]);
                if ($value !== '') {
                    return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
                }
            }
        }

        return null;
    }

    private function extractExpiryDate(string $text): ?string
    {
        $patterns = [
            '/(?:expiry|expires|valid until|valid till|valid to)\s*[:\-]?\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/i',
            '/(?:expiry|expires|valid until|valid till|valid to)\s*[:\-]?\s*(\d{4}[\/\-\.]\d{1,2}[\/\-\.]\d{1,2})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    public function normalizeUniversity(string $value): string
    {
        $canonical = $this->resolveUniversityCanonical($value);
        if ($canonical !== null) {
            return $canonical;
        }

        return trim($value);
    }

    public function resolveUniversityCanonical(string $value): ?string
    {
        $key = mb_strtolower(trim($value), 'UTF-8');
        $key = preg_replace('/\s+/', ' ', $key) ?? $key;

        if (isset(self::UNIVERSITY_ALIASES[$key])) {
            return self::UNIVERSITY_ALIASES[$key];
        }

        foreach (self::UNIVERSITY_ALIASES as $needle => $canonical) {
            if ($needle === $key || str_contains($key, $needle) || str_contains($needle, $key)) {
                return $canonical;
            }
        }

        return null;
    }

    public function isUniversityAbbreviation(string $value): bool
    {
        $key = mb_strtolower(trim($value), 'UTF-8');

        return in_array($key, self::UNIVERSITY_ABBREVIATIONS, true);
    }

    public function normalizeRegistrationNumber(string $value): string
    {
        $value = mb_strtoupper(trim($value), 'UTF-8');
        $value = preg_replace('/[^A-Z0-9]/', '', $value) ?? $value;

        return $value;
    }

    public function normalizeProgram(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = trim($value);

        $replacements = [
            '/\bbachelors\b/' => 'bachelor',
            '/\bbachelor of degree\b/' => 'bachelor degree',
            '/\bbachelor s degree\b/' => 'bachelor degree',
            '/\bbsc\b/' => 'bachelor science',
            '/\bb\.?\s*sc\b/' => 'bachelor science',
            '/\bdegree in\b/' => 'in',
            '/\bin\b/' => ' ',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $value = preg_replace($pattern, $replacement, $value) ?? $value;
        }

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    public function normalizeFieldOfStudy(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
