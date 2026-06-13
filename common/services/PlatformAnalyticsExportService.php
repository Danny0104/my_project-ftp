<?php

namespace common\services;

use common\models\Application;

/**
 * Platform admin export — CSV, Excel, PDF/HTML.
 */
class PlatformAnalyticsExportService
{
    private PlatformAnalyticsService $analytics;

    public function __construct(?PlatformAnalyticsService $analytics = null)
    {
        $this->analytics = $analytics ?? new PlatformAnalyticsService();
    }

    public function exportCsv(int $fromTs, int $toTs, array $filters = []): string
    {
        return $this->analytics->exportCsv($fromTs, $toTs, $filters);
    }

    public function exportExcel(int $fromTs, int $toTs, array $filters = []): string
    {
        $metrics = $this->analytics->getDashboardMetrics($fromTs, $toTs, $filters);
        $apps = Application::find()
            ->alias('a')
            ->innerJoin(['p' => \common\models\Position::tableName()], 'p.id = a.position_id')
            ->with(['student.user', 'position.organization'])
            ->andWhere(['between', 'a.created_at', $fromTs, $toTs])
            ->orderBy(['a.created_at' => SORT_DESC]);

        if (!empty($filters['department'])) {
            $apps->andWhere(['p.field_of_study' => $filters['department']]);
        }
        if (!empty($filters['category'])) {
            $apps->andWhere(['p.category' => $filters['category']]);
        }
        if (!empty($filters['status'])) {
            $apps->andWhere(['a.status' => $filters['status']]);
        }
        if (!empty($filters['organization_id'])) {
            $apps->andWhere(['p.organization_id' => (int) $filters['organization_id']]);
        }

        $rows = $apps->all();
        $period = date('Y-m-d', $fromTs) . ' to ' . date('Y-m-d', $toTs);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        $xml .= '<Worksheet ss:Name="Summary"><Table>';
        $xml .= $this->excelRow(['Platform Analytics Report', $period]);
        $xml .= $this->excelRow(['Total Applications', (int) $metrics['total_applications']]);
        $xml .= $this->excelRow(['Students', (int) $metrics['total_students']]);
        $xml .= $this->excelRow(['Organizations', (int) $metrics['total_organizations']]);
        $xml .= $this->excelRow(['Approved', (int) $metrics['approved']]);
        $xml .= $this->excelRow(['Pending', (int) $metrics['pending']]);
        $xml .= $this->excelRow(['Placement Rate %', (int) $metrics['success_rate']]);
        $xml .= '</Table></Worksheet>';
        $xml .= '<Worksheet ss:Name="Applications"><Table>';
        $xml .= $this->excelRow(['ID', 'Student', 'Organization', 'Position', 'Status', 'Created']);
        foreach ($rows as $app) {
            $xml .= $this->excelRow([
                $app->id,
                $app->student->user->username ?? '',
                $app->position->organization->name ?? '',
                $app->position->title ?? '',
                $app->status,
                date('Y-m-d', (int) $app->created_at),
            ]);
        }
        $xml .= '</Table></Worksheet></Workbook>';
        return $xml;
    }

    public function exportPdfHtml(int $fromTs, int $toTs, array $filters = []): string
    {
        $metrics = $this->analytics->getDashboardMetrics($fromTs, $toTs, $filters);
        $insights = (new OrganizationInsightsService())->build($metrics);
        $period = date('M j, Y', $fromTs) . ' – ' . date('M j, Y', $toTs);
        $generated = date('M j, Y g:i A');

        $kpiRows = [
            ['Total Applications', (int) $metrics['total_applications']],
            ['Students', (int) $metrics['total_students']],
            ['Organizations', (int) $metrics['total_organizations']],
            ['Approved', (int) $metrics['approved']],
            ['Pending', (int) $metrics['pending']],
            ['Placement Rate %', (int) $metrics['success_rate']],
        ];

        $insightHtml = '';
        foreach ($insights as $ins) {
            $insightHtml .= '<li>' . htmlspecialchars($ins['text'], ENT_QUOTES, 'UTF-8') . '</li>';
        }

        $fieldRows = '';
        foreach ($metrics['by_field']['labels'] ?? [] as $i => $label) {
            $fieldRows .= '<tr><td>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td><td>' . (int) ($metrics['by_field']['values'][$i] ?? 0) . '</td></tr>';
        }

        $kpiBody = implode('', array_map(static function ($r) {
            return '<tr><td>' . htmlspecialchars((string) $r[0], ENT_QUOTES, 'UTF-8') . '</td><td><strong>' . (int) $r[1] . '</strong></td></tr>';
        }, $kpiRows));

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Platform Analytics Report</title>
<style>
  body { font-family: Inter, Arial, sans-serif; color: #0f172a; margin: 32px; }
  h1 { font-size: 22px; margin: 0 0 4px; }
  .meta { color: #64748b; font-size: 13px; margin-bottom: 24px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
  th, td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; }
  th { background: #f1f5f9; }
  @media print { .no-print { display: none; } }
</style>
</head>
<body>
  <button class="no-print" onclick="window.print()" style="margin-bottom:16px;padding:8px 14px;cursor:pointer">Print / Save as PDF</button>
  <h1>Reports &amp; Analytics</h1>
  <p class="meta">Field Training Platform · {$period} · Generated {$generated}</p>
  <h2>Key metrics</h2>
  <table><tbody>{$kpiBody}</tbody></table>
  <h2>Insights</h2>
  <ul>{$insightHtml}</ul>
  <h2>Applications by field</h2>
  <table><thead><tr><th>Field</th><th>Count</th></tr></thead><tbody>{$fieldRows}</tbody></table>
</body>
</html>
HTML;
    }

    private function excelRow(array $cells): string
    {
        $row = '<Row>';
        foreach ($cells as $cell) {
            $type = is_int($cell) || is_float($cell) ? 'Number' : 'String';
            $val = htmlspecialchars((string) $cell, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $row .= '<Cell><Data ss:Type="' . $type . '">' . $val . '</Data></Cell>';
        }
        return $row . '</Row>';
    }
}
