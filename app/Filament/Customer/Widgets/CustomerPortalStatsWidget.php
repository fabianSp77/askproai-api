<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Invoice;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;

class CustomerPortalStatsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '300s';

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        // Cache for 5 minutes with company-scoped key
        $companyId = auth()->user()->company_id;
        $cacheMinute = floor(now()->minute / 5) * 5;
        $cacheKey = "customer-portal-stats-{$companyId}-" . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT);

        return Cache::remember($cacheKey, 300, function () use ($companyId) {
            return $this->calculateStats($companyId);
        });
    }

    private function calculateStats(int $companyId): array
    {
        try {
            // Appointments Today
            $appointmentsToday = Appointment::where('company_id', $companyId)
                ->whereDate('starts_at', today())
                ->whereNotIn('status', ['cancelled', 'no-show'])
                ->count();

            // Appointments This Week
            $appointmentsWeek = Appointment::where('company_id', $companyId)
                ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->whereNotIn('status', ['cancelled', 'no-show'])
                ->count();

            // Last Week for Trend
            $appointmentsLastWeek = Appointment::where('company_id', $companyId)
                ->whereBetween('starts_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
                ->whereNotIn('status', ['cancelled', 'no-show'])
                ->count();

            $appointmentTrend = $appointmentsLastWeek > 0
                ? round((($appointmentsWeek - $appointmentsLastWeek) / $appointmentsLastWeek) * 100, 1)
                : 0;

            // Outstanding Invoices
            $outstandingInvoices = Invoice::where('company_id', $companyId)
                ->unpaid()
                ->count();

            $outstandingAmount = Invoice::where('company_id', $companyId)
                ->unpaid()
                ->sum('balance_due') ?? 0;

            // Account Balance (from transactions)
            $currentBalance = Transaction::where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->value('balance_after_cents') ?? 0;

            $balanceEuro = $currentBalance / 100;

            // Last 7 days balance trend
            $balanceWeekAgo = Transaction::where('company_id', $companyId)
                ->where('created_at', '<=', now()->subWeek())
                ->orderBy('created_at', 'desc')
                ->value('balance_after_cents') ?? 0;

            $balanceTrend = $balanceWeekAgo > 0
                ? round((($currentBalance - $balanceWeekAgo) / $balanceWeekAgo) * 100, 1)
                : 0;

            // Calls Last 24h
            $callsToday = Call::where('company_id', $companyId)
                ->where('created_at', '>=', now()->subDay())
                ->count();

            $callsYesterday = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [now()->subDays(2), now()->subDay()])
                ->count();

            $callTrend = $callsYesterday > 0
                ? round((($callsToday - $callsYesterday) / $callsYesterday) * 100, 1)
                : 0;

            // Appointment trend data for chart (last 7 days)
            $appointmentChartData = Appointment::where('company_id', $companyId)
                ->whereBetween('starts_at', [
                    today()->subDays(6)->startOfDay(),
                    today()->endOfDay()
                ])
                ->selectRaw('DATE(starts_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray();

            $appointmentChart = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = today()->subDays($i)->format('Y-m-d');
                $appointmentChart[] = $appointmentChartData[$date] ?? 0;
            }

        } catch (\Exception $e) {
            \Log::error('CustomerPortalStatsWidget Error: ' . $e->getMessage());
            return [
                Stat::make('Fehler', 'Widget-Fehler')
                    ->description('Bitte Dashboard neu laden')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('Aktive Termine', Number::format($appointmentsWeek))
                ->description("Heute: {$appointmentsToday} | Diese Woche: {$appointmentsWeek}")
                ->descriptionIcon($appointmentTrend > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($appointmentChart)
                ->color($this->getGrowthColor($appointmentTrend))
                ->icon('heroicon-o-calendar')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-primary-500 transition-all',
                ]),

            Stat::make('Offene Rechnungen', Number::format($outstandingInvoices))
                ->description(Number::currency($outstandingAmount, 'EUR', 'de') . ' ausstehend')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($outstandingInvoices > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-document-text')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-warning-500 transition-all',
                ]),

            Stat::make('Aktueller Kontostand', Number::currency($balanceEuro, 'EUR', 'de'))
                ->description($this->getBalanceDescription($balanceTrend))
                ->descriptionIcon($balanceTrend > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($this->getBalanceColor($balanceEuro))
                ->icon('heroicon-o-banknotes')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-success-500 transition-all',
                ]),

            Stat::make('Anrufe (24h)', Number::format($callsToday))
                ->description($this->getCallDescription($callTrend, $callsYesterday))
                ->descriptionIcon($callTrend > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($this->getGrowthColor($callTrend))
                ->icon('heroicon-o-phone')
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-info-500 transition-all',
                ]),
        ];
    }

    protected function getGrowthColor(float $growth): string
    {
        if ($growth > 5) return 'success';
        if ($growth > 0) return 'primary';
        if ($growth < -5) return 'danger';
        return 'warning';
    }

    protected function getBalanceColor(float $balance): string
    {
        if ($balance > 100) return 'success';
        if ($balance > 50) return 'primary';
        if ($balance > 10) return 'warning';
        return 'danger';
    }

    protected function getBalanceDescription(float $trend): string
    {
        $trendText = $trend > 0 ? "↑ {$trend}%" : ($trend < 0 ? "↓ " . abs($trend) . "%" : "→ 0%");
        return "Änderung (7 Tage): {$trendText}";
    }

    protected function getCallDescription(float $trend, int $yesterday): string
    {
        $trendText = $trend > 0 ? "↑ {$trend}%" : ($trend < 0 ? "↓ " . abs($trend) . "%" : "→ 0%");
        return "Gestern: {$yesterday} | {$trendText}";
    }
}
