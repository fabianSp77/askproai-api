<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Traits\HasGlobalFilters;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

/**
 * Trend-Chart-Widget für Termine
 * 
 * Zeigt Umsatz-Trend der letzten 30 Tage
 * Mit interaktiver Zoom- und Filter-Funktion
 */
class AppointmentTrendWidget extends ChartWidget
{
    use HasGlobalFilters;
    protected static ?string $heading = 'Umsatz-Trend';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '300s'; // 5 Minuten
    
    protected static ?string $maxHeight = '300px';
    
    public ?string $filter = '30d';
    
    protected ?DashboardMetricsService $metricsService = null;
    
    public function mount(): void
    {
        parent::mount();
        $this->mountHasGlobalFilters();
    }
    
    protected function getMetricsService(): DashboardMetricsService
    {
        if (!$this->metricsService) {
            $this->metricsService = app(DashboardMetricsService::class);
        }
        return $this->metricsService;
    }
    
    #[On('refreshWidget')]
    public function refresh(): void
    {
        $this->updateChartData();
    }
    
    protected function getData(): array
    {
        // Ensure globalFilters is initialized
        if (!isset($this->globalFilters['company_id']) || !$this->globalFilters['company_id']) {
            return [
                'datasets' => [
                    [
                        'label' => 'Umsatz',
                        'data' => [],
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'borderColor' => 'rgb(59, 130, 246)',
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => [],
            ];
        }
        
        // Use global filters
        $trendData = $this->getMetricsService()->getTrendData('revenue', $this->filter, $this->globalFilters);
        
        return [
            'datasets' => [
                [
                    'label' => 'Umsatz',
                    'data' => collect($trendData)->pluck('value')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'fill' => true,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => 'rgb(59, 130, 246)',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                ],
            ],
            'labels' => collect($trendData)->map(function($item) {
                return \Carbon\Carbon::parse($item['date'])->format('d.m');
            })->toArray(),
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleFont' => [
                        'size' => 14,
                    ],
                    'bodyFont' => [
                        'size' => 13,
                    ],
                    'padding' => 10,
                    'displayColors' => false,
                    'callbacks' => [
                        'label' => "function(context) {
                            return 'Umsatz: ' + new Intl.NumberFormat('de-DE', { 
                                style: 'currency', 
                                currency: 'EUR',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(context.parsed.y);
                        }",
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                        ],
                        'callback' => "function(value) {
                            return new Intl.NumberFormat('de-DE', { 
                                style: 'currency', 
                                currency: 'EUR',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0,
                                notation: 'compact'
                            }).format(value);
                        }",
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
    
    protected function getFilters(): ?array
    {
        return [
            '7d' => '7 Tage',
            '30d' => '30 Tage',
            '90d' => '90 Tage',
        ];
    }
    
    public function getHeading(): ?string
    {
        $cacheKey = 'appointment_revenue_total_' . $this->filter . '_' . md5(serialize($this->globalFilters));
        
        $total = Cache::remember($cacheKey, 300, function() {
            $trendData = $this->getMetricsService()->getTrendData('revenue', $this->filter, $this->globalFilters);
            return collect($trendData)->sum('value');
        });
        
        $formatted = number_format($total, 0, ',', '.') . '€';
        
        return "📈 Umsatz-Trend ({$formatted} gesamt)";
    }
    
    protected function getContentHeight(): ?int
    {
        return 300;
    }
}