<?php

namespace App\Filament\Admin\Resources\CallResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CallTrendsWidget extends ChartWidget
{
    protected static ?string $heading = '30-Tage Conversion Trend';
    
    protected static ?int $sort = 3;
    
    protected static ?string $pollingInterval = '60s';
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        try {
            Log::info('CallTrendsWidget: Starting data collection');
            
            $cacheKey = 'call_trends_widget_' . (auth()->user()?->company_id ?? 'all') . '_' . today()->format('Y-m-d');
            
            return Cache::remember($cacheKey, 600, function () {
                $data = [];
                $labels = [];
                $conversions = [];
                $totalCalls = [];
                
                // Sammle Daten für die letzten 30 Tage
                for ($i = 29; $i >= 0; $i--) {
                    $date = today()->subDays($i);
                    
                    // Gesamtanzahl der Anrufe
                    $calls = Call::whereDate('start_timestamp', $date)
                        ->orWhereDate('created_at', $date)
                        ->count();
                    
                    // Anzahl der Anrufe mit Terminwunsch
                    $appointmentRequests = Call::where(function($query) use ($date) {
                            $query->whereDate('start_timestamp', $date)
                                  ->orWhereDate('created_at', $date);
                        })
                        ->whereJsonContains('analysis->appointment_requested', true)
                        ->count();
                    
                    // Anzahl der erfolgreich gebuchten Termine
                    $booked = Call::where(function($query) use ($date) {
                            $query->whereDate('start_timestamp', $date)
                                  ->orWhereDate('created_at', $date);
                        })
                        ->whereNotNull('appointment_id')
                        ->count();
                    
                    // Conversion Rate berechnen
                    $conversionRate = $appointmentRequests > 0 ? round(($booked / $appointmentRequests) * 100) : 0;
                    
                    $labels[] = $date->format('d.m');
                    $conversions[] = $conversionRate;
                    $totalCalls[] = $calls;
                }
                
                // Berechne Durchschnitte
                $avgConversion = count($conversions) > 0 ? round(array_sum($conversions) / count($conversions)) : 0;
                $avgCalls = count($totalCalls) > 0 ? round(array_sum($totalCalls) / count($totalCalls)) : 0;
                
                Log::info('CallTrendsWidget: Data collection completed', [
                    'days' => 30,
                    'avg_conversion' => $avgConversion,
                    'avg_calls' => $avgCalls
                ]);
                
                return [
                    'datasets' => [
                        [
                            'label' => 'Conversion Rate (%)',
                            'data' => $conversions,
                            'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                            'borderColor' => 'rgb(59, 130, 246)',
                            'borderWidth' => 2,
                            'tension' => 0.4,
                            'fill' => true,
                            'pointRadius' => 3,
                            'pointHoverRadius' => 5,
                            'yAxisID' => 'y',
                        ],
                        [
                            'label' => 'Anzahl Anrufe',
                            'data' => $totalCalls,
                            'backgroundColor' => 'rgba(156, 163, 175, 0.1)',
                            'borderColor' => 'rgb(156, 163, 175)',
                            'borderWidth' => 1,
                            'tension' => 0.4,
                            'fill' => false,
                            'pointRadius' => 2,
                            'pointHoverRadius' => 4,
                            'yAxisID' => 'y1',
                        ],
                    ],
                    'labels' => $labels,
                ];
            });
        } catch (\Exception $e) {
            Log::error('CallTrendsWidget: Error collecting data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'datasets' => [
                    [
                        'label' => 'Keine Daten',
                        'data' => [],
                    ],
                ],
                'labels' => [],
            ];
        }
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'beginAtZero' => true,
                    'max' => 100,
                    'title' => [
                        'display' => true,
                        'text' => 'Conversion Rate (%)',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Anzahl Anrufe',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
    
    public function getDescription(): ?string
    {
        // Berechne Trend für die letzten 7 Tage
        $thisWeek = Call::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereJsonContains('analysis->appointment_requested', true)
            ->count();
            
        $thisWeekBooked = Call::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereNotNull('appointment_id')
            ->count();
            
        $lastWeek = Call::whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->whereJsonContains('analysis->appointment_requested', true)
            ->count();
            
        $lastWeekBooked = Call::whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->whereNotNull('appointment_id')
            ->count();
        
        $thisWeekRate = $thisWeek > 0 ? round(($thisWeekBooked / $thisWeek) * 100) : 0;
        $lastWeekRate = $lastWeek > 0 ? round(($lastWeekBooked / $lastWeek) * 100) : 0;
        
        $trend = $thisWeekRate - $lastWeekRate;
        
        if ($trend > 0) {
            return "📈 Conversion Rate diese Woche: <strong>{$thisWeekRate}%</strong> (+{$trend}% vs. letzte Woche)";
        } elseif ($trend < 0) {
            return "📉 Conversion Rate diese Woche: <strong>{$thisWeekRate}%</strong> ({$trend}% vs. letzte Woche)";
        } else {
            return "➡️ Conversion Rate diese Woche: <strong>{$thisWeekRate}%</strong> (unverändert)";
        }
    }
}