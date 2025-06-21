<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Traits\HasGlobalFilters;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

/**
 * Customer Source Widget
 * 
 * Zeigt die Herkunft der Kunden als Kreisdiagramm:
 * - Phone (Anrufe)
 * - Web (Online-Buchungen)
 * - Empfehlung
 * - Sonstige
 */
class CustomerSourceWidget extends ChartWidget
{
    use HasGlobalFilters;
    protected static ?string $heading = 'Kunden-Quellen';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];
    
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
        
        // Bestimme Zeitraum
        $dateRange = $this->getDateRangeFromFilters();
        
        // Zähle Kunden nach Quelle
        $phoneCalls = Customer::where('company_id', $this->globalFilters['company_id'])
            ->whereBetween('created_at', $dateRange)
            ->whereHas('calls')
            ->count();
            
        $webBookings = Customer::where('company_id', $this->globalFilters['company_id'])
            ->whereBetween('created_at', $dateRange)
            ->whereHas('appointments', function($q) {
                $q->whereNotNull('calcom_booking_id')
                  ->orWhereNotNull('calcom_v2_booking_id');
            })
            ->whereDoesntHave('calls')
            ->count();
            
        // Simuliere weitere Quellen (in Zukunft aus metadata)
        $referrals = intval($phoneCalls * 0.15); // 15% geschätzt
        $others = intval($phoneCalls * 0.1); // 10% geschätzt
        
        $data = [
            'Phone' => $phoneCalls,
            'Web' => $webBookings,
            'Empfehlung' => $referrals,
            'Sonstige' => $others,
        ];
        
        // Filtere leere Werte
        $data = array_filter($data, fn($value) => $value > 0);
        
        // Ensure we have data
        if (empty($data)) {
            $data = ['Keine Daten' => 0];
        }
        
        return [
            'datasets' => [
                [
                    'data' => array_values($data),
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',   // Phone - Blau
                        'rgb(34, 197, 94)',    // Web - Grün
                        'rgb(251, 191, 36)',   // Empfehlung - Gelb
                        'rgb(156, 163, 175)',  // Sonstige - Grau
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => array_keys($data),
        ];
    }
    
    protected function getType(): string
    {
        return 'doughnut';
    }
    
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 15,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }",
                    ],
                ],
            ],
            'cutout' => '60%',
        ];
    }
    
    public function getHeading(): ?string
    {
        if (!isset($this->globalFilters['company_id'])) {
            return "🥧 Kunden-Quellen";
        }
        
        // Get period label
        $periodLabels = [
            'today' => 'Heute',
            'this_week' => 'Diese Woche',
            'this_month' => 'Diesen Monat',
            'last_month' => 'Letzten Monat',
        ];
        
        $period = $this->globalFilters['period'] ?? 'this_month';
        $periodLabel = $periodLabels[$period] ?? 'Diesen Monat';
        
        return "🥧 Kunden-Quellen ({$periodLabel})";
    }
}