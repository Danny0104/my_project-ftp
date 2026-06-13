<?php

namespace common\services;

/**
 * CSV, Excel (SpreadsheetML), and printable PDF/HTML report exports.
 */
class OrganizationAnalyticsExportService
{
    private OrganizationAnalyticsService $analytics;
    private OrganizationScopeService $scope;

    public function __construct(
        ?OrganizationAnalyticsService $analytics = null,
        ?OrganizationScopeService $scope = null
    ) {
        $this->analytics = $analytics ?? new OrganizationAnalyticsService();
        $this->scope = $scope ?? new OrganizationScopeService();
    }

    public function exportCsv(int $organizationId, int $fromTs, int $toTs, array $filters = []): string
    {
        return $this->analytics->exportCsv($organizationId, $fromTs, $toTs, $filters);
    }

    public function exportExcel(int $organizationId, int $fromTs, int $toTs, array $filters = []): string
    {
        $metrics = $this->analytics->getDashboardMetrics($organizationId, $fromTs, $toTs, $filters);
        $apps = $this->scope->applicationQuery($organizationId)
            ->with(['student.user', 'position'])
            ->andWhere(['between', 'a.created_at', $fromTs, $toTs]);

        if (!empty($filters['department'])) {
            $apps->andWhere(['p.field_of_study' => $filters['department']]);
        }
        if (!empty($filters['category'])) {
            $apps->andWhere(['p.category' => $filters['category']]);
        }
        if (!empty($filters['status'])) {
            $apps->andWhere(['a.status' => $filters['status']]);
        }

        $rows = $apps->orderBy(['a.created_at' => SORT_DESC])->all();

        $esc = static function ($v): string {
            return htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        };

        $period = date('Y-m-d', $fromTs) . ' to ' . date('Y-m-d', $toTs);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        $xml .= '<Worksheet ss:Name="Summary"><Table>';
        $xml .= $this->excelRow(['Analytics Report', $period]);
        $xml .= $this->excelRow(['Total Applications', (int) $metrics['total_applications']]);
        $xml .= $this->excelRow(['Approved', (int) $metrics['approved']]);
        $xml .= $this->excelRow(['Pending', (int) $metrics['pending']]);
        $xml .= $this->excelRow(['Rejected', (int) $metrics['rejected']]);
        $xml .= $this->excelRow(['Success Rate %', (int) $metrics['success_rate']]);
        $xml .= '</Table></Worksheet>';

        $xml .= '<Worksheet ss:Name="Applications"><Table>';
        $xml .= $this->excelRow(['ID', 'Student', 'Position', 'Status', 'Created']);
        foreach ($rows as $app) {
            $xml .= $this->excelRow([
                $app->id,
                $app->student->user->username ?? '',
                $app->position->title ?? '',
                $app->status,
                date('Y-m-d', (int) $app->created_at),
            ]);
        }
        $xml .= '</Table></Worksheet>';
        $xml .= '</Workbook>';

        return $xml;
    }

    public function exportPdfHtml(int $organizationId, int $fromTs, int $toTs, array $filters = [], string $orgName = 'Organization'): string
    {
        $metrics = $this->analytics->getDashboardMetrics($organizationId, $fromTs, $toTs, $filters);
        $insights = (new OrganizationInsightsService())->build($metrics);
        $period = date('M j, Y', $fromTs) . ' – ' . date('M j, Y', $toTs);
        $generated = date('M j, Y g:i A');

        $kpiRows = [
            ['Total Applications', (int) $metrics['total_applications']],
            ['Approved Students', (int) $metrics['approved']],
            ['Active Internships', (int) $metrics['active_positions']],
            ['Pending Reviews', (int) $metrics['pending']],
            ['Rejected', (int) $metrics['rejected']],
            ['Interview Conversion %', (int) $metrics['interview_conversion_rate']],
            ['Offer Acceptance %', (int) ($metrics['offer_acceptance_rate'] ?? 0)],
            ['Completion Rate %', (int) ($metrics['completion_rate'] ?? 0)],
        ];

        $insightHtml = '';
        foreach ($insights as $ins) {
            $insightHtml .= '<li>' . htmlspecialchars($ins['text'], ENT_QUOTES, 'UTF-8') . '</li>';
        }

        $fieldRows = '';
        foreach ($metrics['by_field']['labels'] ?? [] as $i => $label) {
            $fieldRows .= '<tr><td>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td><td>' . (int) ($metrics['by_field']['values'][$i] ?? 0) . '</td></tr>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Analytics Report — {$this->e($orgName)}</title>
<style>
  body { font-family: Inter, Arial, sans-serif; color: #0f172a; margin: 32px; }
  h1 { font-size: 22px; margin: 0 0 4px; }
  .meta { color: #64748b; font-size: 13px; margin-bottom: 24px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
  th, td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; }
  th { background: #f1f5f9; }
  ul { padding-left: 18px; }
  @media print { body { margin: 16px; } .no-print { display: none; } }
</style>
</head>
<body>
  <button class="no-print" onclick="window.print()" style="margin-bottom:16px;padding:8px 14px;cursor:pointer">Print / Save as PDF</button>
  <h1>Analytics &amp; Reports</h1>
  <p class="meta">{$this->e($orgName)} · {$this->e($period)} · Generated {$this->e($generated)}</p>
  <h2>Key metrics</h2>
  <table><tbody>
HTML
        . implode('', array_map(static function ($r) {
            return '<tr><td>' . htmlspecialchars((string) $r[0], ENT_QUOTES, 'UTF-8') . '</td><td><strong>' . (int) $r[1] . '</strong></td></tr>';
        }, $kpiRows))
        . <<<HTML
  </tbody></table>
  <h2>Insights</h2>
  <ul>{$insightHtml}</ul>
  <h2>Applications by field</h2>
  <table><thead><tr><th>Field</th><th>Count</th></tr></thead><tbody>{$fieldRows}</tbody></table>
</body>
</html>
HTML;
    }

    private function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    private function excelRow(array $cells): string
    {
        $row = '<Row>';
        foreach ($cells as $cell) {
            $type = is_numeric($cell) && !is_string($cell) ? 'Number' : 'String';
            $val = htmlspecialchars((string) $cell, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $row .= '<Cell><Data ss:Type="' . $type . '">' . $val . '</Data></Cell>';
        }
        $row .= '</Row>';
        return $row;
    }
}
