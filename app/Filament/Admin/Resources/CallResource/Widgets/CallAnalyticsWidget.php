<?php

namespace App\Filament\Admin\Resources\CallResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CallAnalyticsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        // Today's calls
        $todaysCalls = Call::whereDate('created_at', $today)->count();
        $yesterdaysCalls = Call::whereDate('created_at', $yesterday)->count();
        $callsChange = $yesterdaysCalls > 0 
            ? round((($todaysCalls - $yesterdaysCalls) / $yesterdaysCalls) * 100)
            : 0;
        
        // Average duration
        $avgDurationToday = Call::whereDate('created_at', $today)
            ->whereNotNull('duration_sec')
            ->avg('duration_sec') ?? 0;
        $avgDurationFormatted = gmdate('i:s', $avgDurationToday);
        
        // Conversion rate (calls that resulted in appointments)
        $totalCallsThisMonth = Call::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $callsWithAppointments = Call::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereNotNull('appointment_id')
            ->count();
        $conversionRate = $totalCallsThisMonth > 0 
            ? round(($callsWithAppointments / $totalCallsThisMonth) * 100, 1)
            : 0;
        
        // Sentiment distribution
        $sentimentData = Call::whereDate('created_at', '>=', now()->subDays(7))
            ->select(DB::raw("JSON_EXTRACT(analysis, '$.sentiment') as sentiment"))
            ->whereNotNull('analysis')
            ->get()
            ->groupBy('sentiment')
            ->map(fn ($group) => $group->count());
        
        $positiveCount = $sentimentData->get('"positive"', 0);
        $negativeCount = $sentimentData->get('"negative"', 0);
        $neutralCount = $sentimentData->get('"neutral"', 0);
        $totalSentiment = $positiveCount + $negativeCount + $neutralCount;
        
        $positiveRate = $totalSentiment > 0 
            ? round(($positiveCount / $totalSentiment) * 100, 1)
            : 0;
        
        return [
            Stat::make('Anrufe heute', $todaysCalls)
                ->description($callsChange >= 0 ? "+{$callsChange}% vs. gestern" : "{$callsChange}% vs. gestern")
                ->descriptionIcon($callsChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart([
                    Call::whereDate('created_at', now()->subDays(6))->count(),
                    Call::whereDate('created_at', now()->subDays(5))->count(),
                    Call::whereDate('created_at', now()->subDays(4))->count(),
                    Call::whereDate('created_at', now()->subDays(3))->count(),
                    Call::whereDate('created_at', now()->subDays(2))->count(),
                    $yesterdaysCalls,
                    $todaysCalls,
                ])
                ->color($callsChange >= 0 ? 'success' : 'danger'),
                
            Stat::make('Ø Anrufdauer', $avgDurationFormatted)
                ->description('Heute')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
                
            Stat::make('Konversionsrate', "{$conversionRate}%")
                ->description("{$callsWithAppointments} von {$totalCallsThisMonth} Anrufen")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($conversionRate > 30 ? 'success' : 'warning'),
                
            Stat::make('Positive Stimmung', "{$positiveRate}%")
                ->description("Letzte 7 Tage")
                ->descriptionIcon('heroicon-m-face-smile')
                ->chart([$negativeCount, $neutralCount, $positiveCount])
                ->color($positiveRate > 60 ? 'success' : 'warning'),
        ];
    }
    
    public static function canView(): bool
    {
        return true;
    }
}