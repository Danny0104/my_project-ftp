<?php

namespace common\services;

/**
 * Rule-based analytics insights (no external AI dependency).
 */
class OrganizationInsightsService
{
    public function build(array $metrics): array
    {
        $insights = [];
        $kpi = $metrics['kpi'] ?? [];
        $total = (int) ($metrics['total_applications'] ?? 0);

        $appDelta = $kpi['total_applications']['delta_pct'] ?? null;
        if ($appDelta !== null && $appDelta >= 10) {
            $insights[] = $this->item('positive', 'fa-arrow-trend-up',
                sprintf('Applications increased by %s%% this period.', $this->fmtPct($appDelta)));
        } elseif ($appDelta !== null && $appDelta <= -10) {
            $insights[] = $this->item('warning', 'fa-arrow-trend-down',
                sprintf('Applications decreased by %s%% compared to the previous period.', $this->fmtPct(abs($appDelta))));
        }

        $topField = $metrics['by_field']['labels'][0] ?? null;
        if ($topField && $total > 0) {
            $topVal = (int) ($metrics['by_field']['values'][0] ?? 0);
            $share = round(100 * $topVal / $total);
            if ($share >= 25) {
                $insights[] = $this->item('positive', 'fa-fire',
                    sprintf('%s internships are trending upward (%d%% of applications).', $topField, $share));
            }
        }

        $convDelta = $kpi['interview_conversion']['delta_pct'] ?? null;
        if ($convDelta !== null && $convDelta <= -5) {
            $insights[] = $this->item('warning', 'fa-triangle-exclamation',
                sprintf('Interview conversion dropped by %s%% — review shortlisted candidates.', $this->fmtPct(abs($convDelta))));
        }

        if ($topField) {
            $insights[] = $this->item('neutral', 'fa-building-columns',
                'Top-performing department: ' . $topField . '.');
        }

        $pending = (int) ($metrics['pending'] ?? 0);
        if ($pending > 0 && $total > 0 && ($pending / $total) > 0.4) {
            $insights[] = $this->item('warning', 'fa-clock',
                sprintf('%d applications are pending review — consider prioritizing your queue.', $pending));
        }

        $scheduled = (int) ($metrics['interviews_scheduled'] ?? 0);
        if ($scheduled > 0) {
            $insights[] = $this->item('neutral', 'fa-calendar-check',
                sprintf('%d interview sessions scheduled — ensure interviewers are prepared.', $scheduled));
        }

        if (empty($insights)) {
            $insights[] = $this->item('neutral', 'fa-chart-line',
                'Analytics are up to date. Adjust filters to explore specific segments.');
        }

        return array_slice($insights, 0, 5);
    }

    private function item(string $type, string $icon, string $text): array
    {
        return ['type' => $type, 'icon' => $icon, 'text' => $text];
    }

    private function fmtPct(float $n): string
    {
        return rtrim(rtrim(number_format($n, 1), '0'), '.');
    }
}
