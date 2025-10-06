<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Invoice;
use App\Models\Customer;
use App\Services\Cache\CacheManager;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '300s'; // Changed from 10s to prevent overload

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        try {
        // Cache for 5 minutes with 5-minute key granularity
        $cacheMinute = floor(now()->minute / 5) * 5;
        $cacheKey = 'stats-overview-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT);

        return Cache::remember($cacheKey, 300, function () {
            // Calls Statistics
            $callsToday = Call::whereDate('created_at', today())->count();
            $callsYesterday = Call::whereDate('created_at', today()->subDay())->count();
            $missedCallsToday = Call::whereDate('created_at', today())
                ->where('status', 'missed')
                ->count();
            $avgDuration = Call::whereNotNull('duration_sec')
                ->whereDate('created_at', today())
                ->avg('duration_sec');
            $avgDurationFormatted = $this->formatDuration($avgDuration ?? 0);

        // Appointments Statistics
        $appointmentsToday = Appointment::whereDate('starts_at', today())->count();
        $appointmentsUpcoming = Appointment::where('starts_at', '>', now())
            ->where('status', 'scheduled')
            ->count();
        $appointmentsCompleted = Appointment::whereDate('starts_at', today())
            ->where('status', 'completed')
            ->count();
        $noShowsWeek = Appointment::where('status', 'no-show')
            ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();
        $noShowsToday = Appointment::where('status', 'no-show')
            ->whereDate('starts_at', today())
            ->count();

        // Revenue Statistics
        $revenueToday = Invoice::whereDate('issue_date', today())
            ->where('status', 'paid')
            ->sum('total_amount');
        $revenueWeek = Invoice::whereBetween('issue_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('status', 'paid')
            ->sum('total_amount');

        // Conversion Rate
        $newCustomersToday = Customer::whereDate('created_at', today())->count();
        $conversionRate = $callsToday > 0
            ? round(($appointmentsToday / $callsToday) * 100, 1)
            : 0;

            // Generate trend data
            $callsTrend = $this->generateTrend('calls', 7);
            $appointmentsTrend = $this->generateTrend('appointments', 7);
            $revenueTrend = $this->generateTrend('revenue', 7);

            return [
            Stat::make('Anrufe heute', Number::format($callsToday))
                ->description($this->getCallDescription($callsToday, $callsYesterday, $missedCallsToday))
                ->descriptionIcon($this->getCallIcon($missedCallsToday))
                ->chart($callsTrend)
                ->color($this->getCallColor($missedCallsToday, $callsToday))
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-primary-500 transition-all',
                    'wire:click' => "\$emit('openResource', 'calls')",
                ]),

            Stat::make('Termine heute', Number::format($appointmentsToday))
                ->description($this->getAppointmentDescription($appointmentsCompleted, $appointmentsUpcoming))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->chart($appointmentsTrend)
                ->color($this->getAppointmentColor($appointmentsToday))
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-info-500 transition-all',
                    'wire:click' => "\$emit('openResource', 'appointments')",
                ]),

            Stat::make('Ø Gesprächsdauer', $avgDurationFormatted)
                ->description($this->getDurationDescription($avgDuration))
                ->descriptionIcon('heroicon-m-clock')
                ->color($this->getDurationColor($avgDuration))
                ->chart($this->generateDurationTrend()),

            Stat::make('No-Shows', Number::format($noShowsWeek))
                ->description($this->getNoShowDescription($noShowsToday, $noShowsWeek))
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($this->getNoShowColor($noShowsWeek))
                ->extraAttributes([
                    'class' => $noShowsWeek > 5 ? 'animate-pulse' : '',
                ]),

            Stat::make('Umsatz heute', Number::currency($revenueToday, 'EUR'))
                ->description("Diese Woche: " . Number::currency($revenueWeek, 'EUR'))
                ->descriptionIcon('heroicon-m-currency-euro')
                ->chart($revenueTrend)
                ->color($revenueToday > 0 ? 'success' : 'gray'),

            Stat::make('Konversionsrate', "{$conversionRate}%")
                ->description("{$appointmentsToday} Termine aus {$callsToday} Anrufen")
                ->descriptionIcon($conversionRate > 30 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($this->getConversionColor($conversionRate)),
            ];
        });

        } catch (\Exception $e) {
            \Log::error('StatsOverview Widget Error: ' . $e->getMessage());
            return [
                Stat::make('Fehler', 'Widget-Fehler')
                    ->description('Bitte neu laden')
                    ->color('danger'),
            ];
        }
    }

    protected function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds) . ' Sek';
        }
        $minutes = floor($seconds / 60);
        $remainingSeconds = round($seconds % 60);
        return sprintf('%d:%02d Min', $minutes, $remainingSeconds);
    }

    protected function getCallDescription(int $today, int $yesterday, int $missed): string
    {
        $trend = $today > $yesterday ? "↑" : ($today < $yesterday ? "↓" : "→");
        $diff = abs($today - $yesterday);
        $missedText = $missed > 0 ? " | {$missed} verpasst" : "";
        return "{$trend} {$diff} vs. gestern{$missedText}";
    }

    protected function getCallIcon(int $missed): string
    {
        return $missed > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-phone-arrow-down-left';
    }

    protected function getCallColor(int $missed, int $total): string
    {
        if ($missed > 0 && $total > 0) {
            $missedPercent = ($missed / $total) * 100;
            if ($missedPercent > 20) return 'danger';
            if ($missedPercent > 10) return 'warning';
        }
        return 'success';
    }

    protected function getAppointmentDescription(int $completed, int $upcoming): string
    {
        return "{$completed} abgeschlossen | {$upcoming} anstehend";
    }

    protected function getAppointmentColor(int $count): string
    {
        if ($count >= 10) return 'success';
        if ($count >= 5) return 'primary';
        if ($count >= 1) return 'warning';
        return 'gray';
    }

    protected function getDurationDescription(?float $seconds): string
    {
        if (!$seconds) return 'Keine Daten';
        if ($seconds < 60) return 'Kurze Gespräche';
        if ($seconds < 180) return 'Normale Dauer';
        if ($seconds < 300) return 'Ausführliche Gespräche';
        return 'Lange Gespräche';
    }

    protected function getDurationColor(?float $seconds): string
    {
        if (!$seconds) return 'gray';
        if ($seconds < 60) return 'warning';
        if ($seconds < 300) return 'success';
        return 'info';
    }

    protected function getNoShowDescription(int $today, int $week): string
    {
        $percent = Appointment::whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $rate = $percent > 0 ? round(($week / $percent) * 100, 1) : 0;
        return "Heute: {$today} | Rate: {$rate}%";
    }

    protected function getNoShowColor(int $count): string
    {
        if ($count === 0) return 'success';
        if ($count <= 2) return 'primary';
        if ($count <= 5) return 'warning';
        return 'danger';
    }

    protected function getConversionColor(float $rate): string
    {
        if ($rate >= 50) return 'success';
        if ($rate >= 30) return 'primary';
        if ($rate >= 15) return 'warning';
        return 'danger';
    }

    protected function generateTrend(string $type, int $days): array
    {
        // Optimized single query per type instead of looping
        $startDate = today()->subDays($days - 1)->startOfDay();
        $endDate = today()->endOfDay();

        $rawData = [];
        switch ($type) {
            case 'calls':
                $rawData = Call::whereBetween('created_at', [$startDate, $endDate])
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('count', 'date')
                    ->toArray();
                break;
            case 'appointments':
                $rawData = Appointment::whereBetween('starts_at', [$startDate, $endDate])
                    ->selectRaw('DATE(starts_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('count', 'date')
                    ->toArray();
                break;
            case 'revenue':
                $rawData = Invoice::whereBetween('issue_date', [$startDate, $endDate])
                    ->where('status', 'paid')
                    ->selectRaw('DATE(issue_date) as date, SUM(total_amount) as total')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('total', 'date')
                    ->toArray();
                // Convert to proper format (divide by 100 for cents to euros)
                $rawData = array_map(fn($val) => $val / 100, $rawData);
                break;
        }

        // Fill missing dates with 0
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = today()->subDays($i)->format('Y-m-d');
            $data[] = $rawData[$date] ?? 0;
        }
        return $data;
    }

    protected function generateDurationTrend(): array
    {
        // Optimized single query instead of 7 queries
        $rawData = Call::whereBetween('created_at', [
                today()->subDays(6)->startOfDay(),
                today()->endOfDay()
            ])
            ->whereNotNull('duration_sec')
            ->selectRaw('DATE(created_at) as date, AVG(duration_sec) as avg_duration')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('avg_duration', 'date')
            ->toArray();

        // Fill missing dates with 0
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i)->format('Y-m-d');
            $data[] = round($rawData[$date] ?? 0);
        }
        return $data;
    }
}
