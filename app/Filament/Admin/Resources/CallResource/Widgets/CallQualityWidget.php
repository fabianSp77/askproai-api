<?php

namespace App\Filament\Admin\Resources\CallResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CallQualityWidget extends ChartWidget
{
    protected static ?string $heading = 'Anrufqualität & Stimmungsanalyse';
    
    protected static ?int $sort = 2;
    
    protected static ?string $pollingInterval = '30s';
    
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        try {
            Log::info('CallQualityWidget: Starting data collection');
            
            $cacheKey = 'call_quality_widget_' . auth()->user()->company_id . '_' . today()->format('Y-m-d');
            
            return Cache::remember($cacheKey, 300, function () {
                $today = today();
                
                // Sentiment-Verteilung heute
                $sentiments = Call::where(function($query) use ($today) {
                        $query->whereDate('start_timestamp', $today)
                              ->orWhereDate('created_at', $today);
                    })
                    ->selectRaw("
                        SUM(CASE WHEN JSON_EXTRACT(analysis, '$.sentiment') = 'positive' THEN 1 ELSE 0 END) as positive,
                        SUM(CASE WHEN JSON_EXTRACT(analysis, '$.sentiment') = 'negative' THEN 1 ELSE 0 END) as negative,
                        SUM(CASE WHEN JSON_EXTRACT(analysis, '$.sentiment') = 'neutral' THEN 1 ELSE 0 END) as neutral
                    ")
                    ->first();
                
                // Kritische Anrufe (negative Stimmung + hohe Dringlichkeit)
                $criticalCalls = Call::where(function($query) use ($today) {
                        $query->whereDate('start_timestamp', $today)
                              ->orWhereDate('created_at', $today);
                    })
                    ->where(function($query) {
                        $query->whereJsonContains('analysis->sentiment', 'negative')
                              ->orWhereJsonContains('analysis->urgency', 'high');
                    })
                    ->whereNull('appointment_id')
                    ->count();
                
                $data = [
                    'datasets' => [
                        [
                            'label' => 'Stimmungsverteilung',
                            'data' => [
                                $sentiments->positive ?? 0,
                                $sentiments->neutral ?? 0,
                                $sentiments->negative ?? 0,
                            ],
                            'backgroundColor' => [
                                'rgb(34, 197, 94)',  // Grün für positiv
                                'rgb(156, 163, 175)', // Grau für neutral
                                'rgb(239, 68, 68)',   // Rot für negativ
                            ],
                            'borderColor' => [
                                'rgb(34, 197, 94)',
                                'rgb(156, 163, 175)',
                                'rgb(239, 68, 68)',
                            ],
                            'hoverOffset' => 4,
                        ],
                    ],
                    'labels' => [
                        'Positiv (' . ($sentiments->positive ?? 0) . ')',
                        'Neutral (' . ($sentiments->neutral ?? 0) . ')',
                        'Negativ (' . ($sentiments->negative ?? 0) . ')',
                    ],
                ];
                
                Log::info('CallQualityWidget: Data collection completed', [
                    'positive' => $sentiments->positive ?? 0,
                    'neutral' => $sentiments->neutral ?? 0,
                    'negative' => $sentiments->negative ?? 0,
                    'critical' => $criticalCalls
                ]);
                
                return $data;
            });
        } catch (\Exception $e) {
            Log::error('CallQualityWidget: Error collecting data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'datasets' => [
                    [
                        'label' => 'Keine Daten',
                        'data' => [0],
                    ],
                ],
                'labels' => ['Keine Daten verfügbar'],
            ];
        }
    }

    protected function getType(): string
    {
        return 'doughnut';
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
                    'callbacks' => [
                        'label' => "
                            function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                                label += percentage + '%';
                                return label;
                            }
                        ",
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'responsive' => true,
        ];
    }
    
    public function getDescription(): ?string
    {
        $criticalCalls = Call::where(function($query) {
                $query->whereDate('start_timestamp', today())
                      ->orWhereDate('created_at', today());
            })
            ->where(function($query) {
                $query->whereJsonContains('analysis->sentiment', 'negative')
                      ->orWhereJsonContains('analysis->urgency', 'high');
            })
            ->whereNull('appointment_id')
            ->count();
        
        if ($criticalCalls > 0) {
            return "⚠️ <strong>{$criticalCalls} kritische Anrufe</strong> benötigen sofortige Aufmerksamkeit!";
        }
        
        return "Stimmungsanalyse der heutigen Anrufe";
    }
}