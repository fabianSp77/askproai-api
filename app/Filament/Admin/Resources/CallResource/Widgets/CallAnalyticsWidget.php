<?php

namespace App\Filament\Admin\Resources\CallResource\Widgets;

use App\Models\Call;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class CallAnalyticsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';
    
    protected function getStats(): array
    {
        $todayCalls = Call::today()->count();
        $yesterdayCalls = Call::whereDate('created_at', today()->subDay())->count();
        $weekCalls = Call::recent(7)->count();
        
        $avgDuration = Call::recent(7)->avg('duration_sec') ?? 0;
        $totalDuration = Call::recent(7)->sum('duration_sec') ?? 0;
        
        $sentimentData = Call::recent(7)
            ->whereNotNull('analysis->sentiment')
            ->get()
            ->groupBy('analysis.sentiment')
            ->map->count();
            
        $positivePercentage = $sentimentData->sum() > 0 
            ? round(($sentimentData->get('positive', 0) / $sentimentData->sum()) * 100) 
            : 0;
            
        $appointmentConversionRate = Call::recent(7)->count() > 0
            ? round((Call::recent(7)->whereNotNull('appointment_id')->count() / Call::recent(7)->count()) * 100)
            : 0;
        
        return [
            Stat::make('Anrufe heute', $todayCalls)
                ->description($todayCalls > $yesterdayCalls ? 'Mehr als gestern' : 'Weniger als gestern')
                ->descriptionIcon($todayCalls > $yesterdayCalls ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($todayCalls > $yesterdayCalls ? 'success' : 'warning')
                ->chart([
                    Call::whereDate('created_at', today()->subDays(6))->count(),
                    Call::whereDate('created_at', today()->subDays(5))->count(),
                    Call::whereDate('created_at', today()->subDays(4))->count(),
                    Call::whereDate('created_at', today()->subDays(3))->count(),
                    Call::whereDate('created_at', today()->subDays(2))->count(),
                    $yesterdayCalls,
                    $todayCalls,
                ]),
                
            Stat::make('Ø Anrufdauer', gmdate('i:s', $avgDuration))
                ->description('Letzte 7 Tage')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info')
                ->extraAttributes([
                    'class' => 'cursor-help',
                    'title' => 'Gesamtdauer: ' . gmdate('H:i:s', $totalDuration),
                ]),
                
            Stat::make('Positive Stimmung', $positivePercentage . '%')
                ->description('Sentiment-Analyse')
                ->descriptionIcon('heroicon-m-face-smile')
                ->color('success')
                ->chart(array_values([
                    $sentimentData->get('negative', 0),
                    $sentimentData->get('neutral', 0),
                    $sentimentData->get('positive', 0),
                ])),
                
            Stat::make('Terminbuchungsrate', $appointmentConversionRate . '%')
                ->description('Anrufe → Termine')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($appointmentConversionRate > 50 ? 'success' : 'warning'),
        ];
    }
}