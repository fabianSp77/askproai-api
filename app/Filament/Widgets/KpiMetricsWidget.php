<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Invoice;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;
use Carbon\Carbon;

class KpiMetricsWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '300s';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Cache for 5 minutes with 5-minute key granularity
        $cacheMinute = floor(now()->minute / 5) * 5;
        $cacheKey = 'kpi-metrics-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT);

        return Cache::remember($cacheKey, 300, function () {
            return $this->calculateStats();
        });
    }

    private function calculateStats(): array
    {
        try {
        // Calculate Customer Lifetime Value (CLV)
        $totalRevenue = Invoice::where('status', 'paid')->sum('total_amount');
        $totalCustomers = Customer::where('status', 'active')->count();
        $avgClv = $totalCustomers > 0 ? $totalRevenue / $totalCustomers : 0;

        // Calculate Churn Rate
        $lastMonthCustomers = Customer::where('created_at', '<=', now()->subMonth())->count();
        $lostCustomers = Customer::where('status', 'inactive')
            ->where('updated_at', '>=', now()->subMonth())
            ->count();
        $churnRate = $lastMonthCustomers > 0
            ? round(($lostCustomers / $lastMonthCustomers) * 100, 1)
            : 0;

        // Calculate Average Service Value
        $completedAppointments = Appointment::where('status', 'completed')->count();
        $avgServiceValue = $completedAppointments > 0
            ? $totalRevenue / $completedAppointments
            : 0;

        // Calculate First Response Time - using duration as proxy
        $avgResponseTime = Call::whereDate('created_at', today())
            ->whereNotNull('duration_sec')
            ->avg('duration_sec') ?? 0;

        // Calculate Monthly Recurring Revenue
        $mrr = Invoice::where('status', 'paid')
            ->whereBetween('issue_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total_amount');

        // Generate trend data - Optimized to avoid N+1 queries
        $revenueTrend = $this->generateRevenueTrend();
        $customerTrend = $this->generateCustomerTrend();

        } catch (\Exception $e) {
            \Log::error('KpiMetricsWidget Error: ' . $e->getMessage());
            return [
                Stat::make('Fehler', 'Widget-Fehler')
                    ->description('KPI Widget konnte nicht geladen werden')
                    ->color('danger'),
            ];
        }

        return [
            Stat::make('Customer Lifetime Value', Number::currency($avgClv, 'EUR'))
                ->description($this->getClvDescription($avgClv))
                ->descriptionIcon('heroicon-m-currency-euro')
                ->chart($revenueTrend)
                ->color($this->getClvColor($avgClv))
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-primary-500 transition-all',
                ]),

            Stat::make('Churn Rate', "{$churnRate}%")
                ->description($this->getChurnDescription($churnRate, $lostCustomers))
                ->descriptionIcon($churnRate > 5 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($customerTrend)
                ->color($this->getChurnColor($churnRate)),

            Stat::make('Ø Service-Wert', Number::currency($avgServiceValue, 'EUR'))
                ->description("Pro abgeschlossenem Termin")
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color($avgServiceValue > 100 ? 'success' : 'warning'),

            Stat::make('Ø Reaktionszeit', $this->formatResponseTime($avgResponseTime))
                ->description($this->getResponseTimeDescription($avgResponseTime))
                ->descriptionIcon('heroicon-m-clock')
                ->color($this->getResponseTimeColor($avgResponseTime)),

            Stat::make('Monthly Recurring', Number::currency($mrr, 'EUR'))
                ->description("Wiederkehrende Einnahmen")
                ->descriptionIcon('heroicon-m-arrow-path')
                ->chart($revenueTrend)
                ->color($mrr > 0 ? 'success' : 'gray'),
        ];
    }

    protected function formatResponseTime(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds) . ' Sek';
        }
        return round($seconds / 60, 1) . ' Min';
    }

    protected function getClvDescription(float $clv): string
    {
        if ($clv > 1000) return "Exzellenter Kundenwert";
        if ($clv > 500) return "Guter Kundenwert";
        if ($clv > 200) return "Durchschnittlicher Wert";
        return "Verbesserungspotential";
    }

    protected function getClvColor(float $clv): string
    {
        if ($clv > 1000) return 'success';
        if ($clv > 500) return 'primary';
        if ($clv > 200) return 'warning';
        return 'danger';
    }

    protected function getChurnDescription(float $rate, int $lost): string
    {
        return "{$lost} Kunden verloren | " .
            ($rate <= 5 ? "Gesunde Rate" : ($rate <= 10 ? "Erhöhte Rate" : "Kritische Rate"));
    }

    protected function getChurnColor(float $rate): string
    {
        if ($rate <= 5) return 'success';
        if ($rate <= 10) return 'warning';
        return 'danger';
    }

    protected function getResponseTimeDescription(float $seconds): string
    {
        if ($seconds < 30) return "Sehr schnell";
        if ($seconds < 60) return "Schnell";
        if ($seconds < 180) return "Akzeptabel";
        return "Zu langsam";
    }

    protected function getResponseTimeColor(float $seconds): string
    {
        if ($seconds < 30) return 'success';
        if ($seconds < 60) return 'primary';
        if ($seconds < 180) return 'warning';
        return 'danger';
    }

    protected function generateRevenueTrend(): array
    {
        // Optimized single query instead of 7 queries
        $rawData = Invoice::whereBetween('issue_date', [
                today()->subDays(6)->startOfDay(),
                today()->endOfDay()
            ])
            ->where('status', 'paid')
            ->selectRaw('DATE(issue_date) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Fill missing dates with 0 and convert to proper format
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i)->format('Y-m-d');
            $data[] = round(($rawData[$date] ?? 0) / 100, 0);
        }
        return $data;
    }

    protected function generateCustomerTrend(): array
    {
        // Optimized single query instead of 7 queries
        $rawData = Customer::whereBetween('created_at', [
                today()->subDays(6)->startOfDay(),
                today()->endOfDay()
            ])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Fill missing dates with 0
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i)->format('Y-m-d');
            $data[] = $rawData[$date] ?? 0;
        }
        return $data;
    }
}