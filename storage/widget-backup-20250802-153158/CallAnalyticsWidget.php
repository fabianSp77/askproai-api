<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CallAnalyticsWidget extends ChartWidget
{
    protected static ?string $heading = '📞 KI-Telefon Analytics';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = '60s';
    
    protected function getData(): array
    {
        $now = Carbon::now();
        $data = [];
        $labels = [];
        
        // Get hourly call data for the last 24 hours
        for ($i = 23; $i >= 0; $i--) {
            $hour = $now->copy()->subHours($i);
            $labels[] = $hour->format('H:00');
            
            $hourData = Call::whereBetween('created_at', [
                $hour->copy()->startOfHour(),
                $hour->copy()->endOfHour()
            ])->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(CASE WHEN appointment_id IS NOT NULL THEN 1 END) as converted')
            ->selectRaw('AVG(duration_sec) as avg_duration')->first();
            
            $data['calls'][] = $hourData && property_exists($hourData, 'total') ? ($hourData->total ?? 0) : 0;
            $data['conversions'][] = $hourData && property_exists($hourData, 'converted') ? ($hourData->converted ?? 0) : 0;
            $data['avg_duration'][] = ($hourData && property_exists($hourData, 'avg_duration') && $hourData->avg_duration) ? round($hourData->avg_duration / 60, 1) : 0;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Anrufe',
                    'data' => $data['calls'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Terminbuchungen',
                    'data' => $data['conversions'],
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Ø Dauer (Min)',
                    'data' => $data['avg_duration'],
                    'backgroundColor' => 'rgba(251, 191, 36, 0.2)',
                    'borderColor' => 'rgba(251, 191, 36, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }
    
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Uhrzeit',
                    ],
                ],
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Anzahl',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Minuten',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
    
    public function getDescription(): ?string
    {
        $stats = $this->getQuickStats();
        
        return sprintf(
            '📊 Heute: %d Anrufe • %d Termine gebucht • %.1f%% Conversion • Ø %.1f Min/Anruf',
            $stats['total_calls'],
            $stats['appointments'],
            $stats['conversion_rate'],
            $stats['avg_duration']
        );
    }
    
    private function getQuickStats(): array
    {
        $today = Carbon::today();
        
        $todayStats = Call::whereDate('created_at', $today)
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN appointment_id IS NOT NULL THEN 1 END) as appointments'),
                DB::raw('AVG(duration_sec) as avg_duration')
            )->first();
        
        $conversionRate = ($todayStats && property_exists($todayStats, 'total') && property_exists($todayStats, 'appointments') && $todayStats->total > 0) 
            ? round(($todayStats->appointments / $todayStats->total) * 100, 1)
            : 0;
        
        return [
            'total_calls' => $todayStats && property_exists($todayStats, 'total') ? ($todayStats->total ?? 0) : 0,
            'appointments' => $todayStats && property_exists($todayStats, 'appointments') ? ($todayStats->appointments ?? 0) : 0,
            'conversion_rate' => $conversionRate,
            'avg_duration' => ($todayStats && property_exists($todayStats, 'avg_duration') && $todayStats->avg_duration) ? round($todayStats->avg_duration / 60, 1) : 0,
        ];
    }
}