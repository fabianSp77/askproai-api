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
        
        // Get current user's company_id (if not super admin)
        $user = auth()->user();
        $companyId = null;
        
        if ($user && !$user->hasRole('super_admin')) {
            $companyId = $user->company_id;
        }
        
        // Build base query
        $baseQuery = $companyId 
            ? Call::where('company_id', $companyId)
            : Call::withoutGlobalScope(\App\Scopes\TenantScope::class);
        
        // Today's calls
        $todaysCalls = (clone $baseQuery)->whereDate('created_at', $today)->count();
        $yesterdaysCalls = (clone $baseQuery)->whereDate('created_at', $yesterday)->count();
        $callsChange = $yesterdaysCalls > 0 
            ? round((($todaysCalls - $yesterdaysCalls) / $yesterdaysCalls) * 100)
            : 0;
        
        // Average duration
        $avgDurationToday = (clone $baseQuery)
            ->whereDate('created_at', $today)
            ->whereNotNull('duration_sec')
            ->avg('duration_sec') ?? 0;
        $avgDurationFormatted = gmdate('i:s', $avgDurationToday);
        
        // Conversion rate (calls that resulted in appointments)
        $totalCallsThisMonth = (clone $baseQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $callsWithAppointments = (clone $baseQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereNotNull('appointment_id')
            ->count();
        $conversionRate = $totalCallsThisMonth > 0 
            ? round(($callsWithAppointments / $totalCallsThisMonth) * 100, 1)
            : 0;
        
        // Sentiment distribution
        $sentimentCalls = (clone $baseQuery)
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->whereNotNull('analysis')
            ->get();
        
        $sentimentCounts = [
            'positive' => 0,
            'negative' => 0,
            'neutral' => 0
        ];
        
        foreach ($sentimentCalls as $call) {
            if ($call->analysis && isset($call->analysis['sentiment'])) {
                $sentiment = $call->analysis['sentiment'];
                if (isset($sentimentCounts[$sentiment])) {
                    $sentimentCounts[$sentiment]++;
                }
            }
        }
        
        $positiveCount = $sentimentCounts['positive'];
        $negativeCount = $sentimentCounts['negative'];
        $neutralCount = $sentimentCounts['neutral'];
        $totalSentiment = $positiveCount + $negativeCount + $neutralCount;
        
        $positiveRate = $totalSentiment > 0 
            ? round(($positiveCount / $totalSentiment) * 100, 1)
            : 0;
        
        // Chart data for last 7 days
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $chartData[] = (clone $baseQuery)
                ->whereDate('created_at', now()->subDays($i))
                ->count();
        }
        
        return [
            Stat::make('Anrufe heute', $todaysCalls)
                ->description($callsChange >= 0 ? "+{$callsChange}% vs. gestern" : "{$callsChange}% vs. gestern")
                ->descriptionIcon($callsChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($chartData)
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