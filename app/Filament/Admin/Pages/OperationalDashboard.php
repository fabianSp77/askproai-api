<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Services\Dashboard\DashboardMetricsService;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class OperationalDashboard extends Page
{
    use HasConsistentNavigation;
    
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $slug = 'dashboard';
    protected static string $view = 'filament.admin.pages.operational-dashboard';
    
    protected DashboardMetricsService $metricsService;
    
    public function boot(DashboardMetricsService $metricsService): void
    {
        $this->metricsService = $metricsService;
    }
    
    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['super_admin', 'company_admin', 'branch_manager']);
    }
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\LiveCallMonitor::class,
            \App\Filament\Admin\Widgets\SystemHealthMonitor::class,
            \App\Filament\Admin\Widgets\ConversionFunnelWidget::class,
            \App\Filament\Admin\Widgets\RealtimeMetricsWidget::class,
        ];
    }
    
    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
        ];
    }
    
    #[On('refresh-dashboard')]
    public function refresh(): void
    {
        $this->dispatch('$refresh');
    }
    
    public function getPollingInterval(): ?string
    {
        return '30s'; // Auto-refresh every 30 seconds
    }
}