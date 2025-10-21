<?php

namespace App\Filament\Resources\PolicyConfigurationResource\Pages;

use App\Filament\Resources\PolicyConfigurationResource;
use App\Models\PolicyConfiguration;
use App\Models\AppointmentModificationStat;
use App\Models\Appointment;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class ExportPolicyAnalytics extends Page
{
    protected static string $resource = PolicyConfigurationResource::class;

    protected static string $view = 'filament.resources.policy-configuration-resource.pages.export-policy-analytics';

    protected static ?string $title = 'Analytics Export';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * Export analytics data to CSV
     */
    public function exportToCsv()
    {
        $companyId = auth()->user()->company_id;
        $data = $this->prepareAnalyticsData($companyId);

        $csvData = $this->convertToCsv($data);

        $filename = 'policy_analytics_' . now()->format('Y-m-d_His') . '.csv';

        return Response::streamDownload(function () use ($csvData) {
            echo $csvData;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export analytics data to JSON
     */
    public function exportToJson()
    {
        $companyId = auth()->user()->company_id;
        $data = $this->prepareAnalyticsData($companyId);

        $filename = 'policy_analytics_' . now()->format('Y-m-d_His') . '.json';

        return Response::streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Prepare analytics data for export
     */
    protected function prepareAnalyticsData(int $companyId): array
    {
        // 1. Active Policies
        $activePolicies = PolicyConfiguration::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        // 2. Total Violations
        $violations30Days = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('stat_type', 'violation')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('count');

        // 3. Compliance Rate
        $totalAppointments30Days = Appointment::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $complianceRate = $totalAppointments30Days > 0
            ? round((($totalAppointments30Days - $violations30Days) / $totalAppointments30Days) * 100, 1)
            : 100;

        // 4. Violations by Type
        $violationsByType = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('stat_type', 'violation')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(\DB::raw('JSON_EXTRACT(metadata, "$.policy_type") as policy_type'), \DB::raw('SUM(count) as total'))
            ->groupBy('policy_type')
            ->get()
            ->map(function ($item) {
                return [
                    'policy_type' => str_replace('"', '', $item->policy_type),
                    'total_violations' => $item->total,
                ];
            })
            ->toArray();

        // 5. Daily Trend (last 30 days)
        $dailyTrend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $violations = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->where('stat_type', 'violation')
                ->whereDate('created_at', $date)
                ->sum('count');

            $dailyTrend[] = [
                'date' => $date->format('Y-m-d'),
                'violations' => $violations,
            ];
        }

        // 6. Top Violating Customers
        $topViolators = \App\Models\Customer::where('company_id', $companyId)
            ->withCount([
                'appointmentModificationStats as violation_count' => function ($query) {
                    $query->where('stat_type', 'violation');
                }
            ])
            ->having('violation_count', '>', 0)
            ->orderByDesc('violation_count')
            ->limit(10)
            ->get()
            ->map(function ($customer) {
                return [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'email' => $customer->email,
                    'violation_count' => $customer->violation_count,
                ];
            })
            ->toArray();

        return [
            'export_date' => now()->toIso8601String(),
            'company_id' => $companyId,
            'summary' => [
                'active_policies' => $activePolicies,
                'total_violations_30d' => $violations30Days,
                'compliance_rate' => $complianceRate,
                'total_appointments_30d' => $totalAppointments30Days,
            ],
            'violations_by_type' => $violationsByType,
            'daily_trend' => $dailyTrend,
            'top_violators' => $topViolators,
        ];
    }

    /**
     * Convert data to CSV format
     */
    protected function convertToCsv(array $data): string
    {
        $csv = [];

        // Summary Section
        $csv[] = 'POLICY ANALYTICS EXPORT';
        $csv[] = 'Export Date,' . $data['export_date'];
        $csv[] = 'Company ID,' . $data['company_id'];
        $csv[] = '';
        $csv[] = 'SUMMARY (Last 30 Days)';
        $csv[] = 'Metric,Value';
        $csv[] = 'Active Policies,' . $data['summary']['active_policies'];
        $csv[] = 'Total Violations,' . $data['summary']['total_violations_30d'];
        $csv[] = 'Compliance Rate,' . $data['summary']['compliance_rate'] . '%';
        $csv[] = 'Total Appointments,' . $data['summary']['total_appointments_30d'];
        $csv[] = '';

        // Violations by Type
        $csv[] = 'VIOLATIONS BY POLICY TYPE';
        $csv[] = 'Policy Type,Total Violations';
        foreach ($data['violations_by_type'] as $violation) {
            $csv[] = $violation['policy_type'] . ',' . $violation['total_violations'];
        }
        $csv[] = '';

        // Daily Trend
        $csv[] = 'DAILY VIOLATION TREND';
        $csv[] = 'Date,Violations';
        foreach ($data['daily_trend'] as $day) {
            $csv[] = $day['date'] . ',' . $day['violations'];
        }
        $csv[] = '';

        // Top Violators
        $csv[] = 'TOP VIOLATING CUSTOMERS';
        $csv[] = 'Customer ID,Name,Email,Violation Count';
        foreach ($data['top_violators'] as $customer) {
            $csv[] = $customer['customer_id'] . ',' .
                     '"' . $customer['customer_name'] . '",' .
                     $customer['email'] . ',' .
                     $customer['violation_count'];
        }

        return implode("\n", $csv);
    }
}
