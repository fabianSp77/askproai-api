<?php

namespace App\Filament\Admin\Resources\ResellerResource\Pages;

use App\Filament\Admin\Resources\ResellerResource;
use App\Filament\Admin\Resources\ResellerResource\Widgets;
use App\Models\Company;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Support\Str;

class ResellerDashboard extends Page
{
    use InteractsWithRecord;
    
    protected static string $resource = ResellerResource::class;

    protected static string $view = 'filament.admin.resources.reseller-resource.pages.reseller-dashboard';

    public function getTitle(): string
    {
        return $this->getRecord()->name . ' - Dashboard';
    }

    public function getSubheading(): string
    {
        return 'Performance overview and client management';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back_to_resellers')
                ->label('Back to Resellers')
                ->icon('heroicon-o-arrow-left')
                ->url(ResellerResource::getUrl('index')),

            Actions\Action::make('edit_reseller')
                ->label('Edit Reseller')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                ->url(ResellerResource::getUrl('edit', ['record' => $this->getRecord()])),

            Actions\Action::make('add_client')
                ->label('Add New Client')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(fn () => route('filament.admin.resources.companies.create', [
                    'parent_company_id' => $this->getRecord()->id
                ])),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\ResellerPerformanceWidget::class,
            Widgets\ResellerRevenueChart::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            Widgets\ResellerClientsTable::class,
        ];
    }

    public function mount(int | string $record): void
    {
        $this->record = static::getResource()::resolveRecordRouteBinding($record);

        abort_unless(static::getResource()::canView($this->getRecord()), 403);
    }
    
    protected function getViewData(): array
    {
        return [
            'record' => $this->getRecord(),
        ];
    }

    public function exportReport(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $reseller = $this->getRecord();
        $analyticsService = app(\App\Services\ResellerAnalyticsService::class);
        $metricsService = app(\App\Services\ResellerMetricsService::class);
        
        $metrics = $analyticsService->getResellerMetrics($reseller);
        $monthlyData = $analyticsService->getMonthlyRevenueData($reseller);
        $clients = $reseller->childCompanies()
            ->with(['branches', 'staff', 'customers', 'appointments'])
            ->get();

        $fileName = 'reseller-report-' . \Str::slug($reseller->name) . '-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($reseller, $metrics, $monthlyData, $clients) {
            $handle = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header information
            fputcsv($handle, ['Reseller Report: ' . $reseller->name]);
            fputcsv($handle, ['Generated on: ' . now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);
            
            // Summary metrics
            fputcsv($handle, ['Summary Metrics']);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Total Clients', $metrics['total_clients']]);
            fputcsv($handle, ['Active Clients', $metrics['active_clients']]);
            fputcsv($handle, ['Inactive Clients', $metrics['inactive_clients']]);
            fputcsv($handle, ['YTD Revenue', '€' . number_format($metrics['revenue_ytd'], 2)]);
            fputcsv($handle, ['Commission Earned', '€' . number_format($metrics['commission_earned'], 2)]);
            fputcsv($handle, ['Avg Revenue per Client', '€' . number_format($metrics['average_revenue_per_client'], 2)]);
            fputcsv($handle, ['Client Retention Rate', number_format($metrics['client_retention_rate'], 1) . '%']);
            fputcsv($handle, []);
            
            // Monthly revenue data
            fputcsv($handle, ['Monthly Revenue Breakdown']);
            fputcsv($handle, ['Month', 'Client Revenue', 'Commission Earned']);
            foreach ($monthlyData['labels'] as $index => $month) {
                fputcsv($handle, [
                    $month,
                    '€' . number_format($monthlyData['revenues'][$index] ?? 0, 2),
                    '€' . number_format($monthlyData['commissions'][$index] ?? 0, 2)
                ]);
            }
            fputcsv($handle, []);
            
            // Client details
            fputcsv($handle, ['Client Companies']);
            fputcsv($handle, [
                'Company Name',
                'Email',
                'Industry',
                'Status',
                'Branches',
                'Staff',
                'Customers',
                'Appointments',
                'Created Date'
            ]);
            
            foreach ($clients as $client) {
                fputcsv($handle, [
                    $client->name,
                    $client->email ?? '',
                    $client->industry ?? '',
                    $client->is_active ? 'Active' : 'Inactive',
                    $client->branches->count(),
                    $client->staff->count(),
                    $client->customers->count(),
                    $client->appointments->count(),
                    $client->created_at->format('Y-m-d')
                ]);
            }
            
            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}