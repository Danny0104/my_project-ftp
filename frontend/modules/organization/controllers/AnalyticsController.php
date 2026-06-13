<?php

namespace frontend\modules\organization\controllers;

use common\services\OrganizationAnalyticsExportService;
use common\services\OrganizationAnalyticsService;
use frontend\assets\OrganizationAnalyticsAsset;
use Yii;
use yii\web\Response;

class AnalyticsController extends BaseController
{
    protected function navKey(): string
    {
        return 'analytics';
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        if (in_array($action->id, ['index', 'data'], true)) {
            OrganizationAnalyticsAsset::register($this->view);
        }
        return true;
    }

    public function actionIndex()
    {
        [$fromTs, $toTs, $from, $to] = $this->resolveDateRange();
        $filters = $this->resolveFilters();

        $service = new OrganizationAnalyticsService();
        $orgId = $this->orgId();
        $metrics = $service->getDashboardMetrics($orgId, $fromTs, $toTs, $filters);
        $filterOptions = $service->getFilterOptions($orgId);

        $this->view->title = 'Analytics & Reports';

        return $this->render('index', [
            'metrics' => $metrics,
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
        ]);
    }

    public function actionData()
    {
        [$fromTs, $toTs] = $this->resolveDateRange();
        $filters = $this->resolveFilters();

        $service = new OrganizationAnalyticsService();
        return $this->jsonSuccess([
            'metrics' => $service->getDashboardMetrics($this->orgId(), $fromTs, $toTs, $filters),
        ]);
    }

    public function actionExport()
    {
        $format = strtolower((string) Yii::$app->request->get('format', 'csv'));
        [$fromTs, $toTs] = $this->resolveDateRange();
        $filters = $this->resolveFilters();

        $exporter = new OrganizationAnalyticsExportService();
        $orgId = $this->orgId();
        $orgName = $this->organization->name ?? 'Organization';

        $this->audit('analytics.export', ['format' => $format]);

        Yii::$app->response->format = Response::FORMAT_RAW;

        $dateSlug = date('Y-m-d');
        switch ($format) {
            case 'xlsx':
            case 'excel':
                $body = $exporter->exportExcel($orgId, $fromTs, $toTs, $filters);
                Yii::$app->response->headers->set('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
                Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="analytics-report-' . $dateSlug . '.xls"');
                return $body;

            case 'pdf':
                $body = $exporter->exportPdfHtml($orgId, $fromTs, $toTs, $filters, $orgName);
                Yii::$app->response->headers->set('Content-Type', 'text/html; charset=UTF-8');
                Yii::$app->response->headers->set('Content-Disposition', 'inline; filename="analytics-report-' . $dateSlug . '.html"');
                return $body;

            case 'csv':
            default:
                $body = $exporter->exportCsv($orgId, $fromTs, $toTs, $filters);
                Yii::$app->response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
                Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="analytics-report-' . $dateSlug . '.csv"');
                return $body;
        }
    }

    /** @return array{0:int,1:int,2:string,3:string} */
    private function resolveDateRange(): array
    {
        $from = Yii::$app->request->get('from');
        $to = Yii::$app->request->get('to');

        if (!$from && !$to) {
            $fromTs = strtotime('first day of this month 00:00:00');
            $toTs = strtotime('last day of this month 23:59:59');
        } else {
            $toTs = $to ? strtotime($to . ' 23:59:59') : time();
            $fromTs = $from ? strtotime($from . ' 00:00:00') : strtotime('-1 month', $toTs);
        }

        return [$fromTs, $toTs, date('Y-m-d', $fromTs), date('Y-m-d', $toTs)];
    }

    /** @return array{department?:string,category?:string,status?:string} */
    private function resolveFilters(): array
    {
        $req = Yii::$app->request;
        $filters = [];
        $department = trim((string) $req->get('department', ''));
        $category = trim((string) $req->get('category', ''));
        $status = trim((string) $req->get('status', ''));
        if ($department !== '') {
            $filters['department'] = $department;
        }
        if ($category !== '') {
            $filters['category'] = $category;
        }
        if ($status !== '') {
            $filters['status'] = $status;
        }
        return $filters;
    }
}
