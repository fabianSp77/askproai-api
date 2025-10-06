<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;
use Carbon\Carbon;

class DashboardStats extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '300s';

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        // Cache for 5 minutes with 5-minute key granularity
        $cacheMinute = floor(now()->minute / 5) * 5;
        $cacheKey = 'dashboard-stats-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT);

        return Cache::remember($cacheKey, 300, function () {
            return $this->calculateStats();
        });
    }

    private function calculateStats(): array
    {
        try {
        // Customer Statistics
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $newCustomersToday = Customer::whereDate('created_at', today())->count();
        $newCustomersWeek = Customer::where('created_at', '>=', now()->startOfWeek())->count();
        $withBirthday = Schema::hasColumn('customers', 'birthday')
            ? Customer::whereNotNull('birthday')->count()
            : 0;

        // Company & Branch Statistics
        $totalCompanies = Company::count();
        $activeBranches = Branch::where('is_active', true)->count();

        // Service Statistics
        $totalServices = Service::count();
        $activeServices = Service::where('is_active', true)->count();

        // Calculate trends (last 7 days) - Optimized single query per model
        $customerTrendData = Customer::whereBetween('created_at', [
                today()->subDays(6)->startOfDay(),
                today()->endOfDay()
            ])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $companyTrendData = Company::whereBetween('created_at', [
                today()->subDays(6)->startOfDay(),
                today()->endOfDay()
            ])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Fill missing dates with 0
        $customerTrend = [];
        $companyTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i)->format('Y-m-d');
            $customerTrend[] = $customerTrendData[$date] ?? 0;
            $companyTrend[] = $companyTrendData[$date] ?? 0;
        }

        // Calculate percentage changes
        $lastWeekCustomers = Customer::whereBetween('created_at', [
            now()->subWeeks(2)->startOfWeek(),
            now()->subWeek()->endOfWeek()
        ])->count();

        $thisWeekCustomers = Customer::whereBetween('created_at', [
            now()->startOfWeek(),
            now()
        ])->count();

        $customerGrowth = $lastWeekCustomers > 0
            ? round((($thisWeekCustomers - $lastWeekCustomers) / $lastWeekCustomers) * 100, 1)
            : 0;

        } catch (\Exception $e) {
            \Log::error('DashboardStats Widget Error: ' . $e->getMessage());
            return [
                Stat::make('Fehler', 'Widget-Fehler')
                    ->description('Bitte Dashboard neu laden')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('Kunden Gesamt', Number::format($totalCustomers))
                ->description($this->getCustomerDescription($activeCustomers, $totalCustomers, $customerGrowth))
                ->descriptionIcon($customerGrowth > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($customerTrend)
                ->color($this->getGrowthColor($customerGrowth))
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-primary-500 transition-all',
                    'wire:click' => "\$emit('openResource', 'customers')",
                ]),

            Stat::make('Neue Kunden', Number::format($newCustomersWeek))
                ->description("Heute: {$newCustomersToday} | Diese Woche: {$newCustomersWeek}")
                ->descriptionIcon('heroicon-m-user-plus')
                ->color($newCustomersToday > 0 ? 'success' : 'gray')
                ->chart($customerTrend)
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-success-500 transition-all',
                ]),

            Stat::make('Unternehmen', Number::format($totalCompanies))
                ->description("{$activeBranches} aktive Filialen")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->chart($companyTrend)
                ->color('primary')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-primary-500 transition-all',
                    'wire:click' => "\$emit('openResource', 'companies')",
                ]),

            Stat::make('Services', Number::format($totalServices))
                ->description($this->getServiceDescription($activeServices, $totalServices))
                ->descriptionIcon('heroicon-m-briefcase')
                ->color($this->getServiceColor($activeServices, $totalServices))
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-info-500 transition-all',
                    'wire:click' => "\$emit('openResource', 'services')",
                ]),
        ];
    }

    protected function getCustomerDescription(int $active, int $total, float $growth): string
    {
        $activePercent = $total > 0 ? round(($active / $total) * 100, 1) : 0;
        $growthText = $growth > 0 ? "↑ {$growth}%" : ($growth < 0 ? "↓ " . abs($growth) . "%" : "→ 0%");

        return "{$activePercent}% aktiv | Wachstum: {$growthText}";
    }

    protected function getServiceDescription(int $active, int $total): string
    {
        $activePercent = $total > 0 ? round(($active / $total) * 100, 1) : 0;
        return "{$active} aktiv ({$activePercent}%)";
    }

    protected function getGrowthColor(float $growth): string
    {
        if ($growth > 5) return 'success';
        if ($growth > 0) return 'primary';
        if ($growth < -5) return 'danger';
        return 'warning';
    }

    protected function getServiceColor(int $active, int $total): string
    {
        $percent = $total > 0 ? ($active / $total) * 100 : 0;
        if ($percent >= 80) return 'success';
        if ($percent >= 60) return 'primary';
        if ($percent >= 40) return 'warning';
        return 'danger';
    }
}
