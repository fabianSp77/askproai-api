<?php

namespace App\Filament\Resources\PolicyConfigurationResource\Pages;

use App\Filament\Resources\PolicyConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPolicyConfigurations extends ListRecords
{
    protected static string $resource = PolicyConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return $this->exportAnalyticsCsv();
                }),

            Actions\Action::make('export_json')
                ->label('Export JSON')
                ->icon('heroicon-o-code-bracket')
                ->color('info')
                ->action(function () {
                    return $this->exportAnalyticsJson();
                }),

            Actions\CreateAction::make()
                ->label('Neue Richtlinie')
                ->icon('heroicon-o-plus'),
        ];
    }

    protected function exportAnalyticsCsv()
    {
        $companyId = auth()->user()->company_id;
        $data = $this->prepareAnalyticsData($companyId);
        $csvData = $this->convertToCsv($data);
        $filename = 'policy_analytics_' . now()->format('Y-m-d_His') . '.csv';

        return \Illuminate\Support\Facades\Response::streamDownload(function () use ($csvData) {
            echo $csvData;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function exportAnalyticsJson()
    {
        $companyId = auth()->user()->company_id;
        $data = $this->prepareAnalyticsData($companyId);
        $filename = 'policy_analytics_' . now()->format('Y-m-d_His') . '.json';

        return \Illuminate\Support\Facades\Response::streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function prepareAnalyticsData(int $companyId): array
    {
        // Count active policies (not soft-deleted)
        $activePolicies = \App\Models\PolicyConfiguration::where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->count();

        $violations30Days = \App\Models\AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('stat_type', 'violation')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('count');

        $totalAppointments30Days = \App\Models\Appointment::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $complianceRate = $totalAppointments30Days > 0
            ? round((($totalAppointments30Days - $violations30Days) / $totalAppointments30Days) * 100, 1)
            : 100;

        // SECURITY: Use JSON_UNQUOTE for cleaner extraction and prevent potential issues
        $violationsByType = \App\Models\AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('stat_type', 'violation')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(\DB::raw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.policy_type")) as policy_type'), \DB::raw('SUM(count) as total'))
            ->groupBy('policy_type')
            ->get()
            ->map(function ($item) {
                // Whitelist validation for export data integrity
                $allowedTypes = ['cancellation', 'reschedule', 'no_show', 'late_arrival', 'payment'];
                $policyType = in_array($item->policy_type, $allowedTypes) ? $item->policy_type : 'other';

                return [
                    'policy_type' => $policyType,
                    'total_violations' => $item->total,
                ];
            })
            ->toArray();

        $dailyTrend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $violations = \App\Models\AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
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

    protected function convertToCsv(array $data): string
    {
        $csv = [];

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

        $csv[] = 'VIOLATIONS BY POLICY TYPE';
        $csv[] = 'Policy Type,Total Violations';
        foreach ($data['violations_by_type'] as $violation) {
            $csv[] = $violation['policy_type'] . ',' . $violation['total_violations'];
        }
        $csv[] = '';

        $csv[] = 'DAILY VIOLATION TREND';
        $csv[] = 'Date,Violations';
        foreach ($data['daily_trend'] as $day) {
            $csv[] = $day['date'] . ',' . $day['violations'];
        }
        $csv[] = '';

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

    protected function getHeaderWidgets(): array
    {
        // Show only the most important widget at the top
        // Other widgets moved to footer to keep table visible
        return [
            \App\Filament\Widgets\PolicyAnalyticsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        // Optional analytics widgets at the bottom (collapsible section would be better)
        return [
            \App\Filament\Widgets\TimeBasedAnalyticsWidget::class,
            \App\Filament\Widgets\PolicyEffectivenessWidget::class,
            \App\Filament\Widgets\PolicyTrendWidget::class,
            \App\Filament\Widgets\PolicyChartsWidget::class,
            \App\Filament\Widgets\CustomerComplianceWidget::class,
            \App\Filament\Widgets\PolicyViolationsTableWidget::class,
            \App\Filament\Widgets\StaffPerformanceWidget::class,
        ];
    }
}
