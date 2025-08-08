<?php

namespace App\Filament\Admin\Pages;

use App\Models\Company;
use App\Services\ResellerAnalyticsService;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResellerOverview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.admin.pages.reseller-overview';
    
    protected static ?string $navigationGroup = "👥 Partner & Reseller";
    
    protected static ?int $navigationSort = 605;

    protected static ?string $title = 'Reseller Overview';

    protected static ?string $navigationLabel = "Reseller Übersicht";

    public function getSubheading(): string
    {
        return 'Comprehensive view of your reseller network and performance';
    }

    public function getResellerHierarchy(): array
    {
        $analyticsService = app(ResellerAnalyticsService::class);
        return $analyticsService->getResellerHierarchy();
    }

    public function getResellerStats(): array
    {
        // Use the cached metrics service
        $metricsService = app(\App\Services\ResellerMetricsService::class);
        $stats = $metricsService->getAggregatedStats();
        
        $analyticsService = app(ResellerAnalyticsService::class);
        $totalCommission = $analyticsService->getTotalCommissionPayout();

        return [
            'total_resellers' => $stats['total_resellers'] ?? 0,
            'active_resellers' => $stats['active_resellers'] ?? 0,
            'total_clients' => $stats['total_clients'] ?? 0,
            'total_revenue' => $stats['total_revenue'] ?? 0,
            'total_commission' => $totalCommission,
            'avg_clients_per_reseller' => ($stats['total_resellers'] ?? 0) > 0 
                ? round(($stats['total_clients'] ?? 0) / ($stats['total_resellers'] ?? 0), 1) 
                : 0,
        ];
    }

    public function getTopPerformers(): array
    {
        $analyticsService = app(ResellerAnalyticsService::class);
        return $analyticsService->getTopResellers(5)->toArray();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'Super Admin']) ?? false;
    }
}