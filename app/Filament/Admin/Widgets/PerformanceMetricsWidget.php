<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PerformanceMetricsWidget extends ChartWidget
{
    protected static ?string $heading = 'Performance Metriken';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 4;
    
    protected static ?string $pollingInterval = '60s';
    
    protected static ?string $maxHeight = '300px';
    
    public ?string $filter = 'week';
    
    protected function getData(): array
    {
        $endDate = Carbon::now();
        $startDate = match($this->filter) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->subDays(7),
            'month' => Carbon::now()->subDays(30),
            'year' => Carbon::now()->subYear(),
            default => Carbon::now()->subDays(7),
        };
        
        // Generate date labels
        $labels = [];
        $appointmentData = [];
        $callData = [];
        $customerData = [];
        $conversionData = [];
        
        $period = match($this->filter) {
            'today' => 'hour',
            'week' => 'day',
            'month' => 'day',
            'year' => 'month',
            default => 'day',
        };
        
        // Fetch data based on period
        if ($period === 'hour') {
            for ($i = 0; $i < 24; $i++) {
                $hour = Carbon::today()->addHours($i);
                $labels[] = $hour->format('H:00');
                
                $appointmentData[] = Appointment::whereBetween('starts_at', [
                    $hour,
                    $hour->copy()->addHour()
                ])->count();
                
                $callData[] = Call::whereBetween('created_at', [
                    $hour,
                    $hour->copy()->addHour()
                ])->count();
                
                $customerData[] = Customer::whereBetween('created_at', [
                    $hour,
                    $hour->copy()->addHour()
                ])->count();
                
                // Calculate conversion rate (appointments from calls)
                $calls = Call::whereBetween('created_at', [
                    $hour,
                    $hour->copy()->addHour()
                ])->count();
                
                $appointmentsFromCalls = Call::whereBetween('created_at', [
                    $hour,
                    $hour->copy()->addHour()
                ])->where(function($query) {
                    $query->whereNotNull('appointment_id')
                          ->orWhereHas('appointmentViaCallId');
                })->count();
                
                $conversionData[] = $calls > 0 ? round(($appointmentsFromCalls / $calls) * 100, 1) : 0;
            }
        } else {
            $current = $startDate->copy();
            while ($current <= $endDate) {
                if ($period === 'day') {
                    $labels[] = $current->format('d.m');
                    $nextPeriod = $current->copy()->addDay();
                } else {
                    $labels[] = $current->format('M Y');
                    $nextPeriod = $current->copy()->addMonth();
                }
                
                $appointmentData[] = Appointment::whereBetween('starts_at', [
                    $current,
                    $nextPeriod
                ])->count();
                
                $callData[] = Call::whereBetween('created_at', [
                    $current,
                    $nextPeriod
                ])->count();
                
                $customerData[] = Customer::whereBetween('created_at', [
                    $current,
                    $nextPeriod
                ])->count();
                
                // Calculate conversion rate
                $calls = Call::whereBetween('created_at', [
                    $current,
                    $nextPeriod
                ])->count();
                
                $appointmentsFromCalls = Call::whereBetween('created_at', [
                    $current,
                    $nextPeriod
                ])->where(function($query) {
                    $query->whereNotNull('appointment_id')
                          ->orWhereHas('appointmentViaCallId');
                })->count();
                
                $conversionData[] = $calls > 0 ? round(($appointmentsFromCalls / $calls) * 100, 1) : 0;
                
                $current = $nextPeriod;
            }
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Termine',
                    'data' => $appointmentData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Anrufe',
                    'data' => $callData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Neue Kunden',
                    'data' => $customerData,
                    'borderColor' => 'rgb(251, 146, 60)',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Konversionsrate (%)',
                    'data' => $conversionData,
                    'borderColor' => 'rgb(168, 85, 247)',
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'yAxisID' => 'y1',
                    'borderDash' => [5, 5],
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
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "
                            function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    if (context.datasetIndex === 3) {
                                        label += context.parsed.y + '%';
                                    } else {
                                        label += context.parsed.y;
                                    }
                                }
                                return label;
                            }
                        ",
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'grid' => [
                        'color' => 'rgba(156, 163, 175, 0.2)',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                    'max' => 100,
                    'ticks' => [
                        'callback' => "function(value) { return value + '%'; }",
                    ],
                ],
            ],
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getFilters(): ?array
    {
        return [
            'today' => 'Heute',
            'week' => 'Letzte 7 Tage',
            'month' => 'Letzte 30 Tage',
            'year' => 'Letztes Jahr',
        ];
    }
    
    public function getDescription(): ?string
    {
        return 'Übersicht der wichtigsten Leistungsindikatoren mit Trends';
    }
}