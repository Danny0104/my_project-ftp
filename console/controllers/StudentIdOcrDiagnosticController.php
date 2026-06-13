<?php

namespace console\controllers;

use common\services\StudentIdOcrService;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Verifies Tesseract installation and OCR pipeline readiness.
 *
 * Usage: php yii student-id-ocr-diagnostic/run
 */
class StudentIdOcrDiagnosticController extends Controller
{
    public function actionRun(): int
    {
        $service = new StudentIdOcrService();
        $report = $service->diagnoseTesseract();

        $this->stdout("=== Student ID OCR Diagnostic ===\n\n");

        foreach ($report as $key => $value) {
            if ($key === 'candidates' && is_array($value)) {
                $this->stdout("CANDIDATES:\n");
                foreach ($value as $candidate) {
                    if (!is_array($candidate)) {
                        continue;
                    }
                    $this->stdout(sprintf(
                        "  - %s (exists=%s, executable=%s, version=%s)\n",
                        $candidate['path'] ?? '?',
                        !empty($candidate['exists']) ? 'yes' : 'no',
                        !empty($candidate['executable']) ? 'yes' : 'no',
                        $candidate['version'] ?? 'n/a'
                    ));
                }
                continue;
            }

            if (is_array($value)) {
                $this->stdout(strtoupper((string) $key) . ":\n");
                foreach ($value as $line) {
                    if (is_array($line)) {
                        continue;
                    }
                    $this->stdout('  - ' . $line . "\n");
                }
                continue;
            }

            $this->stdout(strtoupper((string) $key) . ': ' . (is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value) . "\n");
        }

        $this->stdout("\n");
        if (!$report['available']) {
            $this->stderr("Tesseract is NOT available. Install from https://github.com/UB-Mannheim/tesseract/wiki\n");
            $this->stderr("Then set studentId.tesseractPath in common/config/params-local.php\n");

            return ExitCode::UNAVAILABLE;
        }

        $this->stdout("Tesseract is ready.\n");

        return ExitCode::OK;
    }
}
