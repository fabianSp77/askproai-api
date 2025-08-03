<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Traits\HasGlobalFilters;
use App\Models\Call;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

/**
 * Call-Dauer Histogramm Widget
 * 
 * Zeigt die Verteilung der Anrufdauer in Kategorien:
 * - Sehr kurz (< 1 min) - Oft Aufleger
 * - Kurz (1-5 min) - Standard-Anfragen
 * - Mittel (5-15 min) - Detaillierte Beratungen  
 * - Lang (>15 min) - Komplexe Fälle
 */
class CallDurationHistogramWidget extends ChartWidget
{
    use HasGlobalFilters;
    protected static ?string $heading = 'Anrufdauer-Verteilung';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $pollingInterval = '300s';
    
    protected static ?string $maxHeight = '300px';
    
    public function mount(): void
    {
        parent::mount();
        $this->mountHasGlobalFilters();
    }
    
    #[On('refreshWidget')]
    public function refresh(): void
    {
        $this->updateChartData();
    }
    
    protected function getData(): array
    {
        // Ensure globalFilters is initialized
        if (!isset($this->globalFilters['company_id'])) {
            return [
                'datasets' => [
                    [
                        'data' => [],
                        'backgroundColor' => [],
                    ],
                ],
                'labels' => [],
            ];
        }
        
        // Definiere Dauer-Kategorien
        $categories = [
            'very_short' => ['label' => '< 1 min', 'min' => 0, 'max' => 60, 'color' => 'rgb(239, 68, 68)'],
            'short' => ['label' => '1-5 min', 'min' => 60, 'max' => 300, 'color' => 'rgb(251, 191, 36)'],
            'medium' => ['label' => '5-15 min', 'min' => 300, 'max' => 900, 'color' => 'rgb(34, 197, 94)'],
            'long' => ['label' => '> 15 min', 'min' => 900, 'max' => null, 'color' => 'rgb(59, 130, 246)'],
        ];
        
        $data = [];
        $backgroundColors = [];
        $labels = [];
        
        foreach ($categories as $key => $category) {
            $query = Call::query()
                ->where('company_id', $this->globalFilters['company_id'])
                ->whereNotNull('duration_sec')
                ->where('duration_sec', '>=', $category['min']);
                
            if ($category['max']) {
                $query->where('duration_sec', '<', $category['max']);
            }
            
            // Apply global filters
            if ($this->globalFilters['branch_id']) {
                $query->where('branch_id', $this->globalFilters['branch_id']);
            }
            
            // Apply date range from global filters
            $dateRange = $this->getDateRangeFromFilters();
            $query->whereBetween('created_at', $dateRange);
            
            $count = $query->count();
            $data[] = $count;
            $labels[] = $category['label'];
            $backgroundColors[] = $category['color'];
        }
        
        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }
    
    protected function getType(): string
    {
        return 'bar';
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
                    'callbacks' => [
                        'label' => "function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed.y / total) * 100).toFixed(1);
                            return context.parsed.y + ' Anrufe (' + percentage + '%)';
                        }",
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
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
    
    public function getHeading(): ?string
    {
        if (!isset($this->globalFilters['company_id'])) {
            return "📊 Anrufdauer-Verteilung";
        }
        
        $query = Call::query()
            ->where('company_id', $this->globalFilters['company_id'])
            ->whereNotNull('duration_sec');
            
        if (isset($this->globalFilters['branch_id']) && $this->globalFilters['branch_id']) {
            $query->where('branch_id', $this->globalFilters['branch_id']);
        }
        
        $dateRange = $this->getDateRangeFromFilters();
        $avgDuration = $query->whereBetween('created_at', $dateRange)->avg('duration_sec');
            
        $formatted = $avgDuration ? sprintf('%d:%02d', floor($avgDuration / 60), $avgDuration % 60) : '0:00';
        
        return "📊 Anrufdauer-Verteilung (Ø {$formatted} min)";
    }
}