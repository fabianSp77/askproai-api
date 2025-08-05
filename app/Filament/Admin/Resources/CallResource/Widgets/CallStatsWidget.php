<?php

namespace App\Filament\Admin\Resources\CallResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Call;
use Carbon\Carbon;

class CallStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $todayCallsCount = Call::whereDate('created_at', today())->count();
        $yesterdayCallsCount = Call::whereDate('created_at', today()->subDay())->count();
        
        $weekCallsCount = Call::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        
        $avgDuration = Call::whereDate('created_at', today())
            ->avg('duration_sec') ?? 0;
            
        $conversionRate = Call::whereDate('created_at', today())
            ->where(function($q) { $q->whereNotNull('metadata')->where('metadata', 'like', '%appointment%'); })
            ->count();
        
        $conversionPercentage = $todayCallsCount > 0 
            ? round(($conversionRate / $todayCallsCount) * 100) 
            : 0;
            
        $sentimentStats = Call::whereDate('created_at', today())
            ->selectRaw("
                SUM(CASE WHEN JSON_EXTRACT(analysis, '$.sentiment') = 'positive' THEN 1 ELSE 0 END) as positive,
                SUM(CASE WHEN JSON_EXTRACT(analysis, '$.sentiment') = 'negative' THEN 1 ELSE 0 END) as negative,
                SUM(CASE WHEN JSON_EXTRACT(analysis, '$.sentiment') = 'neutral' THEN 1 ELSE 0 END) as neutral
            ")
            ->first();
            
        $positivePercentage = $todayCallsCount > 0 
            ? round(($sentimentStats->positive / $todayCallsCount) * 100) 
            : 0;
        
        return [
            Stat::make('Anrufe heute', $todayCallsCount)
                ->description($todayCallsCount > $yesterdayCallsCount ? 
                    '+' . ($todayCallsCount - $yesterdayCallsCount) . ' vs. gestern' : 
                    ($todayCallsCount - $yesterdayCallsCount) . ' vs. gestern')
                ->descriptionIcon($todayCallsCount > $yesterdayCallsCount ? 
                    'heroicon-s-arrow-trending-up' : 
                    'heroicon-s-arrow-trending-down')
                ->chart($this->getCallsChart())
                ->color($todayCallsCount > $yesterdayCallsCount ? 'success' : 'danger'),
                
            Stat::make('Diese Woche', $weekCallsCount)
                ->description('Anrufe gesamt')
                ->descriptionIcon('heroicon-s-phone')
                ->color('primary'),
                
            Stat::make('Ø Dauer', gmdate('i:s', $avgDuration))
                ->description('Minuten pro Anruf')
                ->descriptionIcon('heroicon-s-clock')
                ->color('info'),
                
            Stat::make('Conversion', $conversionPercentage . '%')
                ->description($conversionRate . ' Termine gebucht')
                ->descriptionIcon('heroicon-s-calendar')
                ->chart($this->getConversionChart())
                ->color($conversionPercentage > 50 ? 'success' : 'warning'),
                
            Stat::make('Positive Stimmung', $positivePercentage . '%')
                ->description('der heutigen Anrufe')
                ->descriptionIcon('heroicon-s-face-smile')
                ->color('success'),
        ];
    }
    
    private function getCallsChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = Call::whereDate('created_at', today()->subDays($i))->count();
        }
        return $data;
    }
    
    private function getConversionChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $totalCalls = Call::whereDate('created_at', $date)->count();
            $conversions = Call::whereDate('created_at', $date)
                ->where(function($q) { $q->whereNotNull('metadata')->where('metadata', 'like', '%appointment%'); })
                ->count();
                
            $data[] = $totalCalls > 0 ? round(($conversions / $totalCalls) * 100) : 0;
        }
        return $data;
    }
}