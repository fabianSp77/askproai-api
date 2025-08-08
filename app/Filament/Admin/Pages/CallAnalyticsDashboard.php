<?php

namespace App\Filament\Admin\Pages;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Services\Cache\CallCacheService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

class CallAnalyticsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'KI-Telefon Analytics';
    protected static ?string $navigationGroup = 'Analytics & Berichte';
    protected static ?int $navigationSort = 200;
    protected static string $view = 'filament.admin.pages.call-analytics-dashboard';
    
    // Date range filter
    public string $dateRange = '7d';
    public ?string $customFrom = null;
    public ?string $customTo = null;
    
    // Comparison mode
    public bool $compareMode = false;
    public string $compareRange = 'previous_period';
    
    // Real-time update
    public bool $autoRefresh = true;
    public int $refreshInterval = 60; // seconds
    
    // Chart visibility toggles
    public bool $showCallVolume = true;
    public bool $showConversionRate = true;
    public bool $showDurationAnalysis = true;
    public bool $showSentimentAnalysis = true;
    public bool $showCostAnalysis = true;
    public bool $showHeatmap = true;
    public bool $showTopPerformers = true;
    public bool $showFailureAnalysis = true;
    
    protected ?CallCacheService $cacheService = null;
    
    public function mount(): void
    {
        $this->cacheService = app(CallCacheService::class);
        
        // Set default date range from URL or session
        $this->dateRange = request('range', session('analytics_date_range', '7d'));
    }
    
    /**
     * Get KPI metrics
     */
    #[Computed]
    public function kpiMetrics(): array
    {
        $companyId = auth()->user()->company_id;
        $dates = $this->getDateRange();
        
        $cacheKey = "analytics:kpi:{$companyId}:{$this->dateRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($companyId, $dates) {
            $current = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$dates['from'], $dates['to']])
                ->selectRaw('
                    COUNT(*) as total_calls,
                    SUM(appointment_made) as appointments_made,
                    AVG(duration_sec) as avg_duration,
                    SUM(cost_cents) / 100 as total_cost,
                    AVG(sentiment_score) as avg_sentiment,
                    COUNT(DISTINCT customer_id) as unique_customers,
                    COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_calls,
                    COUNT(CASE WHEN duration_sec > 300 THEN 1 END) as long_calls
                ')
                ->first();
            
            // Calculate comparison if enabled
            $comparison = null;
            if ($this->compareMode) {
                $compareDates = $this->getComparisonDateRange();
                $comparison = Call::where('company_id', $companyId)
                    ->whereBetween('created_at', [$compareDates['from'], $compareDates['to']])
                    ->selectRaw('
                        COUNT(*) as total_calls,
                        SUM(appointment_made) as appointments_made,
                        AVG(duration_sec) as avg_duration,
                        SUM(cost_cents) / 100 as total_cost
                    ')
                    ->first();
            }
            
            return [
                'total_calls' => [
                    'value' => $current->total_calls ?? 0,
                    'previous' => $comparison->total_calls ?? null,
                    'change' => $this->calculateChange($current->total_calls, $comparison->total_calls ?? 0),
                    'icon' => 'phone',
                    'color' => 'blue',
                ],
                'conversion_rate' => [
                    'value' => $current->total_calls > 0 
                        ? round(($current->appointments_made / $current->total_calls) * 100, 1) 
                        : 0,
                    'previous' => $comparison && $comparison->total_calls > 0
                        ? round(($comparison->appointments_made / $comparison->total_calls) * 100, 1)
                        : null,
                    'suffix' => '%',
                    'icon' => 'check-circle',
                    'color' => 'green',
                ],
                'avg_duration' => [
                    'value' => round(($current->avg_duration ?? 0) / 60, 1),
                    'previous' => $comparison ? round(($comparison->avg_duration ?? 0) / 60, 1) : null,
                    'suffix' => ' Min',
                    'icon' => 'clock',
                    'color' => 'purple',
                ],
                'total_cost' => [
                    'value' => round($current->total_cost ?? 0, 2),
                    'previous' => $comparison ? round($comparison->total_cost ?? 0, 2) : null,
                    'prefix' => '€',
                    'icon' => 'currency-euro',
                    'color' => 'yellow',
                ],
                'unique_customers' => [
                    'value' => $current->unique_customers ?? 0,
                    'icon' => 'users',
                    'color' => 'indigo',
                ],
                'success_rate' => [
                    'value' => $current->total_calls > 0
                        ? round((($current->total_calls - $current->failed_calls) / $current->total_calls) * 100, 1)
                        : 0,
                    'suffix' => '%',
                    'icon' => 'shield-check',
                    'color' => 'emerald',
                ],
            ];
        });
    }
    
    /**
     * Get call volume chart data
     */
    #[Computed]
    public function callVolumeData(): array
    {
        $companyId = auth()->user()->company_id;
        $dates = $this->getDateRange();
        
        $cacheKey = "analytics:volume:{$companyId}:{$this->dateRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($companyId, $dates) {
            $interval = $this->getGroupingInterval();
            
            $data = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$dates['from'], $dates['to']])
                ->selectRaw("
                    DATE_FORMAT(created_at, '{$interval}') as period,
                    COUNT(*) as total_calls,
                    SUM(appointment_made) as appointments,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed
                ")
                ->groupBy('period')
                ->orderBy('period')
                ->get();
            
            $labels = [];
            $totalCalls = [];
            $appointments = [];
            $failed = [];
            
            foreach ($data as $row) {
                $labels[] = $this->formatPeriodLabel($row->period);
                $totalCalls[] = $row->total_calls;
                $appointments[] = $row->appointments;
                $failed[] = $row->failed;
            }
            
            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Alle Anrufe',
                        'data' => $totalCalls,
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'tension' => 0.4,
                    ],
                    [
                        'label' => 'Mit Termin',
                        'data' => $appointments,
                        'borderColor' => 'rgb(34, 197, 94)',
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'tension' => 0.4,
                    ],
                    [
                        'label' => 'Fehlgeschlagen',
                        'data' => $failed,
                        'borderColor' => 'rgb(239, 68, 68)',
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                        'tension' => 0.4,
                    ],
                ],
            ];
        });
    }
    
    /**
     * Get conversion funnel data
     */
    #[Computed]
    public function conversionFunnelData(): array
    {
        $companyId = auth()->user()->company_id;
        $dates = $this->getDateRange();
        
        $cacheKey = "analytics:funnel:{$companyId}:{$this->dateRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($companyId, $dates) {
            $stats = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$dates['from'], $dates['to']])
                ->selectRaw('
                    COUNT(*) as total_calls,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as completed,
                    COUNT(CASE WHEN duration_sec > 30 THEN 1 END) as engaged,
                    COUNT(CASE WHEN customer_id IS NOT NULL THEN 1 END) as identified,
                    SUM(appointment_made) as appointments
                ')
                ->first();
            
            return [
                'stages' => [
                    ['name' => 'Anrufe eingegangen', 'value' => $stats->total_calls, 'percentage' => 100],
                    ['name' => 'Anruf beantwortet', 'value' => $stats->completed, 'percentage' => $stats->total_calls > 0 ? round(($stats->completed / $stats->total_calls) * 100, 1) : 0],
                    ['name' => 'Gespräch geführt (>30s)', 'value' => $stats->engaged, 'percentage' => $stats->total_calls > 0 ? round(($stats->engaged / $stats->total_calls) * 100, 1) : 0],
                    ['name' => 'Kunde identifiziert', 'value' => $stats->identified, 'percentage' => $stats->total_calls > 0 ? round(($stats->identified / $stats->total_calls) * 100, 1) : 0],
                    ['name' => 'Termin gebucht', 'value' => $stats->appointments, 'percentage' => $stats->total_calls > 0 ? round(($stats->appointments / $stats->total_calls) * 100, 1) : 0],
                ],
            ];
        });
    }
    
    /**
     * Get duration distribution data
     */
    #[Computed]
    public function durationDistributionData(): array
    {
        $companyId = auth()->user()->company_id;
        $dates = $this->getDateRange();
        
        $cacheKey = "analytics:duration:{$companyId}:{$this->dateRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($companyId, $dates) {
            $data = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$dates['from'], $dates['to']])
                ->selectRaw('
                    COUNT(CASE WHEN duration_sec < 30 THEN 1 END) as very_short,
                    COUNT(CASE WHEN duration_sec BETWEEN 30 AND 60 THEN 1 END) as short,
                    COUNT(CASE WHEN duration_sec BETWEEN 61 AND 180 THEN 1 END) as medium,
                    COUNT(CASE WHEN duration_sec BETWEEN 181 AND 300 THEN 1 END) as long,
                    COUNT(CASE WHEN duration_sec BETWEEN 301 AND 600 THEN 1 END) as very_long,
                    COUNT(CASE WHEN duration_sec > 600 THEN 1 END) as extreme
                ')
                ->first();
            
            return [
                'labels' => ['<30s', '30-60s', '1-3 Min', '3-5 Min', '5-10 Min', '>10 Min'],
                'datasets' => [[
                    'label' => 'Anzahl Anrufe',
                    'data' => [
                        $data->very_short,
                        $data->short,
                        $data->medium,
                        $data->long,
                        $data->very_long,
                        $data->extreme,
                    ],
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(250, 204, 21, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                    ],
                ]],
            ];
        });
    }
    
    /**
     * Get sentiment analysis data
     */
    #[Computed]
    public function sentimentData(): array
    {
        $companyId = auth()->user()->company_id;
        $dates = $this->getDateRange();
        
        $cacheKey = "analytics:sentiment:{$companyId}:{$this->dateRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($companyId, $dates) {
            $data = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$dates['from'], $dates['to']])
                ->whereNotNull('sentiment')
                ->selectRaw('
                    sentiment,
                    COUNT(*) as count,
                    AVG(sentiment_score) as avg_score,
                    SUM(appointment_made) as appointments
                ')
                ->groupBy('sentiment')
                ->get();
            
            $sentiments = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
            $appointmentsBySentiment = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
            
            foreach ($data as $row) {
                if (isset($sentiments[$row->sentiment])) {
                    $sentiments[$row->sentiment] = $row->count;
                    $appointmentsBySentiment[$row->sentiment] = $row->appointments;
                }
            }
            
            return [
                'labels' => ['Positiv', 'Neutral', 'Negativ'],
                'datasets' => [
                    [
                        'label' => 'Anrufe',
                        'data' => array_values($sentiments),
                        'backgroundColor' => [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(156, 163, 175, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                        ],
                    ],
                    [
                        'label' => 'Termine',
                        'data' => array_values($appointmentsBySentiment),
                        'backgroundColor' => [
                            'rgba(34, 197, 94, 0.5)',
                            'rgba(156, 163, 175, 0.5)',
                            'rgba(239, 68, 68, 0.5)',
                        ],
                    ],
                ],
            ];
        });
    }
    
    /**
     * Get hourly heatmap data
     */
    #[Computed]
    public function heatmapData(): array
    {
        $companyId = auth()->user()->company_id;
        $dates = $this->getDateRange();
        
        $cacheKey = "analytics:heatmap:{$companyId}:{$this->dateRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($companyId, $dates) {
            $data = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$dates['from'], $dates['to']])
                ->selectRaw('
                    DAYOFWEEK(created_at) as day_of_week,
                    HOUR(created_at) as hour,
                    COUNT(*) as count,
                    AVG(CASE WHEN appointment_made = 1 THEN 1 ELSE 0 END) * 100 as conversion_rate
                ')
                ->groupBy(['day_of_week', 'hour'])
                ->get();
            
            // Initialize grid
            $grid = [];
            $days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
            
            for ($day = 0; $day < 7; $day++) {
                for ($hour = 0; $hour < 24; $hour++) {
                    $grid[] = [
                        'x' => $hour,
                        'y' => $day,
                        'value' => 0,
                        'conversion' => 0,
                    ];
                }
            }
            
            // Fill with data
            foreach ($data as $row) {
                $dayIndex = $row->day_of_week - 1; // MySQL DAYOFWEEK starts at 1 for Sunday
                $index = ($dayIndex * 24) + $row->hour;
                if (isset($grid[$index])) {
                    $grid[$index]['value'] = $row->count;
                    $grid[$index]['conversion'] = round($row->conversion_rate, 1);
                }
            }
            
            return [
                'data' => $grid,
                'days' => $days,
                'hours' => range(0, 23),
            ];
        });
    }
    
    /**
     * Get top performers data
     */
    #[Computed]
    public function topPerformersData(): array
    {
        $companyId = auth()->user()->company_id;
        $dates = $this->getDateRange();
        
        $cacheKey = "analytics:performers:{$companyId}:{$this->dateRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($companyId, $dates) {
            // Top customers by call volume
            $topCustomers = Customer::where('company_id', $companyId)
                ->whereHas('calls', function ($q) use ($dates) {
                    $q->whereBetween('created_at', [$dates['from'], $dates['to']]);
                })
                ->withCount(['calls' => function ($q) use ($dates) {
                    $q->whereBetween('created_at', [$dates['from'], $dates['to']]);
                }])
                ->orderBy('calls_count', 'desc')
                ->limit(5)
                ->get(['id', 'name', 'phone']);
            
            // Top conversion days
            $topDays = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$dates['from'], $dates['to']])
                ->selectRaw('
                    DATE(created_at) as date,
                    COUNT(*) as total_calls,
                    SUM(appointment_made) as appointments,
                    (SUM(appointment_made) / COUNT(*)) * 100 as conversion_rate
                ')
                ->groupBy('date')
                ->orderBy('conversion_rate', 'desc')
                ->limit(5)
                ->get();
            
            return [
                'customers' => $topCustomers->map(fn($c) => [
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'calls' => $c->calls_count,
                ]),
                'best_days' => $topDays->map(fn($d) => [
                    'date' => Carbon::parse($d->date)->format('d.m.Y'),
                    'calls' => $d->total_calls,
                    'appointments' => $d->appointments,
                    'conversion' => round($d->conversion_rate, 1),
                ]),
            ];
        });
    }
    
    /**
     * Get cost analysis data
     */
    #[Computed]
    public function costAnalysisData(): array
    {
        $companyId = auth()->user()->company_id;
        $dates = $this->getDateRange();
        
        $cacheKey = "analytics:cost:{$companyId}:{$this->dateRange}";
        
        return Cache::remember($cacheKey, 300, function () use ($companyId, $dates) {
            $interval = $this->getGroupingInterval();
            
            $data = Call::where('company_id', $companyId)
                ->whereBetween('created_at', [$dates['from'], $dates['to']])
                ->selectRaw("
                    DATE_FORMAT(created_at, '{$interval}') as period,
                    SUM(cost_cents) / 100 as total_cost,
                    SUM(CASE WHEN appointment_made = 1 THEN cost_cents ELSE 0 END) / 100 as appointment_cost,
                    COUNT(*) as total_calls,
                    SUM(appointment_made) as appointments
                ")
                ->groupBy('period')
                ->orderBy('period')
                ->get();
            
            $labels = [];
            $totalCosts = [];
            $costPerAppointment = [];
            $avgCostPerCall = [];
            
            foreach ($data as $row) {
                $labels[] = $this->formatPeriodLabel($row->period);
                $totalCosts[] = round($row->total_cost, 2);
                $costPerAppointment[] = $row->appointments > 0 
                    ? round($row->appointment_cost / $row->appointments, 2) 
                    : 0;
                $avgCostPerCall[] = $row->total_calls > 0
                    ? round($row->total_cost / $row->total_calls, 2)
                    : 0;
            }
            
            return [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Gesamtkosten (€)',
                        'data' => $totalCosts,
                        'borderColor' => 'rgb(251, 146, 60)',
                        'backgroundColor' => 'rgba(251, 146, 60, 0.1)',
                        'yAxisID' => 'y',
                    ],
                    [
                        'label' => 'Kosten pro Termin (€)',
                        'data' => $costPerAppointment,
                        'borderColor' => 'rgb(34, 197, 94)',
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'yAxisID' => 'y1',
                    ],
                    [
                        'label' => 'Ø Kosten pro Anruf (€)',
                        'data' => $avgCostPerCall,
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'yAxisID' => 'y1',
                    ],
                ],
            ];
        });
    }
    
    /**
     * Handle date range change
     */
    public function updateDateRange(): void
    {
        session(['analytics_date_range' => $this->dateRange]);
        
        // Clear all computed properties cache
        unset($this->kpiMetrics);
        unset($this->callVolumeData);
        unset($this->conversionFunnelData);
        unset($this->durationDistributionData);
        unset($this->sentimentData);
        unset($this->heatmapData);
        unset($this->topPerformersData);
        unset($this->costAnalysisData);
        
        // Refresh the page data
        $this->dispatch('refreshCharts');
    }
    
    /**
     * Export analytics data
     */
    public function exportAnalytics(string $format = 'xlsx'): void
    {
        // Implementation for exporting analytics data
        session()->flash('success', 'Analytics Export wurde gestartet');
    }
    
    /**
     * Get date range based on selection
     */
    protected function getDateRange(): array
    {
        if ($this->dateRange === 'custom') {
            return [
                'from' => Carbon::parse($this->customFrom)->startOfDay(),
                'to' => Carbon::parse($this->customTo)->endOfDay(),
            ];
        }
        
        $to = Carbon::now()->endOfDay();
        
        $from = match($this->dateRange) {
            'today' => Carbon::today(),
            'yesterday' => Carbon::yesterday(),
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            'this_month' => Carbon::now()->startOfMonth(),
            'last_month' => Carbon::now()->subMonth()->startOfMonth(),
            'this_year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->subDays(7),
        };
        
        return [
            'from' => $from,
            'to' => $to,
        ];
    }
    
    /**
     * Get comparison date range
     */
    protected function getComparisonDateRange(): array
    {
        $current = $this->getDateRange();
        $diff = $current['from']->diffInDays($current['to']);
        
        return [
            'from' => $current['from']->copy()->subDays($diff + 1),
            'to' => $current['from']->copy()->subDay(),
        ];
    }
    
    /**
     * Get appropriate grouping interval based on date range
     */
    protected function getGroupingInterval(): string
    {
        $dates = $this->getDateRange();
        $days = $dates['from']->diffInDays($dates['to']);
        
        if ($days <= 1) {
            return '%Y-%m-%d %H:00'; // Hourly
        } elseif ($days <= 31) {
            return '%Y-%m-%d'; // Daily
        } elseif ($days <= 90) {
            return '%Y-%u'; // Weekly
        } else {
            return '%Y-%m'; // Monthly
        }
    }
    
    /**
     * Format period label for display
     */
    protected function formatPeriodLabel(string $period): string
    {
        $dates = $this->getDateRange();
        $days = $dates['from']->diffInDays($dates['to']);
        
        if ($days <= 1) {
            return Carbon::parse($period)->format('H:00');
        } elseif ($days <= 31) {
            return Carbon::parse($period)->format('d.m');
        } elseif ($days <= 90) {
            return 'KW ' . Carbon::parse($period)->weekOfYear;
        } else {
            return Carbon::parse($period)->format('M Y');
        }
    }
    
    /**
     * Calculate percentage change
     */
    protected function calculateChange($current, $previous): ?float
    {
        if (!$previous || $previous == 0) {
            return null;
        }
        
        return round((($current - $previous) / $previous) * 100, 1);
    }
}