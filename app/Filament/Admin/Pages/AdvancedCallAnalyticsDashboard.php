<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Company;
use App\Models\Staff;
use App\Services\AdvancedCallAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AdvancedCallAnalyticsDashboard extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = "heroicon-o-chart-bar-square";
    protected static ?string $navigationGroup = "📊 Analytics";
    protected static ?string $navigationLabel = "Advanced Call Analytics";
    protected static ?int $navigationSort = 15;
    protected static string $view = 'filament.admin.pages.advanced-call-analytics-dashboard';
    
    // Form properties
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?int $companyId = null;
    public ?int $staffId = null;
    public string $analysisType = 'overview';
    public string $comparisonMode = 'team';
    
    // Analytics data
    public array $dashboardData = [];
    public array $agentKPIs = [];
    public array $patternAnalysis = [];
    public array $satisfactionMetrics = [];
    public array $funnelData = [];
    public array $realTimeKPIs = [];
    public array $comparativeAnalysis = [];
    public array $performanceAlerts = [];
    
    private AdvancedCallAnalyticsService $analyticsService;
    
    public function mount(): void
    {
        $this->analyticsService = app(AdvancedCallAnalyticsService::class);
        
        // Default to last 30 days
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        
        // Set company context
        $user = auth()->user();
        if ($user && !$user->hasRole(['Super Admin', 'super_admin'])) {
            $companies = Company::whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get();
            
            if ($companies->count() === 1) {
                $this->companyId = $companies->first()->id;
            }
        }
        
        $this->loadAnalyticsData();
    }
    
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        $user = auth()->user();
        $isSuperAdmin = $user && $user->hasRole(['Super Admin', 'super_admin']);
        
        return $form
            ->schema([
                Select::make('companyId')
                    ->label('Unternehmen')
                    ->options(function () use ($user, $isSuperAdmin) {
                        if ($isSuperAdmin) {
                            return Company::pluck('name', 'id')->toArray();
                        }
                        
                        return Company::whereHas('users', function ($query) use ($user) {
                            $query->where('user_id', $user->id);
                        })->pluck('name', 'id');
                    })
                    ->placeholder($isSuperAdmin ? '📊 Alle Unternehmen' : 'Unternehmen wählen')
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        $this->companyId = $state ? (int)$state : null;
                        $this->staffId = null; // Reset staff selection
                        $this->loadAnalyticsData();
                    }),
                    
                Select::make('staffId')
                    ->label('Mitarbeiter/Agent')
                    ->options(fn() => $this->companyId 
                        ? Staff::where('company_id', $this->companyId)->where('is_active', true)->pluck('name', 'id')
                        : []
                    )
                    ->placeholder('Alle Mitarbeiter')
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        $this->staffId = $state ? (int)$state : null;
                        $this->loadAnalyticsData();
                    }),
                    
                ToggleButtons::make('analysisType')
                    ->label('Analyse-Typ')
                    ->options([
                        'overview' => 'Übersicht',
                        'performance' => 'Performance',
                        'patterns' => 'Muster',
                        'satisfaction' => 'Zufriedenheit',
                        'funnel' => 'Conversion',
                        'comparative' => 'Vergleich',
                        'realtime' => 'Live-Daten',
                    ])
                    ->default('overview')
                    ->inline()
                    ->reactive()
                    ->afterStateUpdated(fn() => $this->loadAnalyticsData()),
                    
                ToggleButtons::make('comparisonMode')
                    ->label('Vergleichsmodus')
                    ->options([
                        'team' => 'vs Team',
                        'company' => 'vs Firma',
                        'industry' => 'vs Branche',
                        'historical' => 'vs Historie',
                    ])
                    ->default('team')
                    ->visible(fn() => $this->analysisType === 'comparative')
                    ->inline()
                    ->reactive()
                    ->afterStateUpdated(fn() => $this->loadAnalyticsData()),
                    
                DatePicker::make('dateFrom')
                    ->label('Von')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn() => $this->loadAnalyticsData()),
                    
                DatePicker::make('dateTo')
                    ->label('Bis')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn() => $this->loadAnalyticsData()),
            ])
            ->columns([
                'default' => 1,
                'sm' => 2,
                'md' => 3,
                'lg' => 6,
            ]);
    }
    
    public function loadAnalyticsData(): void
    {
        if (!$this->dateFrom || !$this->dateTo) {
            return;
        }
        
        $filters = [
            'company_id' => $this->companyId,
            'staff_id' => $this->staffId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
        
        try {
            switch ($this->analysisType) {
                case 'overview':
                    $this->loadOverviewData($filters);
                    break;
                case 'performance':
                    $this->loadPerformanceData($filters);
                    break;
                case 'patterns':
                    $this->loadPatternData($filters);
                    break;
                case 'satisfaction':
                    $this->loadSatisfactionData($filters);
                    break;
                case 'funnel':
                    $this->loadFunnelData($filters);
                    break;
                case 'comparative':
                    $this->loadComparativeData($filters);
                    break;
                case 'realtime':
                    $this->loadRealTimeData($filters);
                    break;
            }
            
            // Always load performance alerts if company is selected
            if ($this->companyId) {
                $this->performanceAlerts = $this->analyticsService->generatePerformanceAlerts(
                    $this->companyId, 
                    $this->staffId
                );
            }
            
        } catch (\Exception $e) {
            \Log::error('Analytics loading error', [
                'error' => $e->getMessage(),
                'filters' => $filters,
                'analysis_type' => $this->analysisType
            ]);
            
            $this->dashboardData = [
                'error' => 'Fehler beim Laden der Analytics-Daten: ' . $e->getMessage()
            ];
        }
    }
    
    private function loadOverviewData(array $filters): void
    {
        $this->agentKPIs = $this->analyticsService->getAgentPerformanceKPIs(
            $filters['company_id'],
            $filters['staff_id'],
            $filters['date_from'],
            $filters['date_to']
        );
        
        $this->realTimeKPIs = $this->analyticsService->getRealTimeKPIs($filters);
        
        // Summary metrics
        $this->dashboardData = [
            'summary' => [
                'total_calls' => $this->agentKPIs['total_calls'] ?? 0,
                'answer_rate' => $this->agentKPIs['answer_rate'] ?? 0,
                'conversion_rate' => $this->agentKPIs['conversion_rate'] ?? 0,
                'customer_satisfaction' => $this->agentKPIs['avg_sentiment_score'] ?? 0,
                'roi_percentage' => $this->agentKPIs['roi_percentage'] ?? 0,
                'performance_rating' => $this->agentKPIs['performance_rating'] ?? 'Keine Daten',
            ],
            'trends' => $this->calculateTrends($filters),
            'quick_insights' => $this->generateQuickInsights($this->agentKPIs),
        ];
    }
    
    private function loadPerformanceData(array $filters): void
    {
        $this->agentKPIs = $this->analyticsService->getAgentPerformanceKPIs(
            $filters['company_id'],
            $filters['staff_id'],
            $filters['date_from'],
            $filters['date_to']
        );
        
        // Performance benchmarks
        $this->dashboardData = [
            'kpis' => $this->agentKPIs,
            'benchmarks' => $this->getBenchmarkComparisons($this->agentKPIs),
            'improvement_areas' => $this->identifyImprovementAreas($this->agentKPIs),
            'performance_trends' => $this->getPerformanceTrends($filters),
        ];
    }
    
    private function loadPatternData(array $filters): void
    {
        $this->patternAnalysis = $this->analyticsService->getCallPatternAnalysis($filters);
        
        $this->dashboardData = [
            'patterns' => $this->patternAnalysis,
            'predictions' => $this->patternAnalysis['volume_prediction'] ?? [],
            'optimization_recommendations' => $this->patternAnalysis['capacity_recommendations'] ?? [],
        ];
    }
    
    private function loadSatisfactionData(array $filters): void
    {
        $this->satisfactionMetrics = $this->analyticsService->getCustomerSatisfactionMetrics($filters);
        
        $this->dashboardData = [
            'satisfaction' => $this->satisfactionMetrics,
            'satisfaction_score' => $this->satisfactionMetrics['overall_satisfaction_score'] ?? 0,
            'nps_score' => $this->satisfactionMetrics['nps_equivalent'] ?? 0,
            'improvement_recommendations' => $this->satisfactionMetrics['improvement_areas'] ?? [],
        ];
    }
    
    private function loadFunnelData(array $filters): void
    {
        $this->funnelData = $this->analyticsService->getConversionFunnelData($filters);
        
        $this->dashboardData = [
            'funnel' => $this->funnelData,
            'conversion_opportunities' => $this->funnelData['optimization_opportunities'] ?? [],
            'benchmark_performance' => $this->funnelData['benchmark_comparison'] ?? [],
        ];
    }
    
    private function loadComparativeData(array $filters): void
    {
        $this->comparativeAnalysis = $this->analyticsService->getComparativeAnalytics($filters);
        
        $this->dashboardData = [
            'comparative' => $this->comparativeAnalysis,
            'percentiles' => $this->comparativeAnalysis['agent_percentiles'] ?? [],
            'top_performers' => $this->comparativeAnalysis['top_performers'] ?? [],
            'performance_distribution' => $this->comparativeAnalysis['performance_distribution'] ?? [],
        ];
    }
    
    private function loadRealTimeData(array $filters): void
    {
        $this->realTimeKPIs = $this->analyticsService->getRealTimeKPIs($filters);
        
        $this->dashboardData = [
            'realtime' => $this->realTimeKPIs,
            'active_calls' => $this->realTimeKPIs['active_calls'] ?? 0,
            'today_performance' => $this->realTimeKPIs['today_summary'] ?? [],
            'system_health' => $this->realTimeKPIs['system_health'] ?? [],
            'live_alerts' => $this->realTimeKPIs['performance_alerts'] ?? [],
        ];
    }
    
    private function calculateTrends(array $filters): array
    {
        $previousPeriod = [
            'date_from' => Carbon::parse($filters['date_from'])->subDays(30)->format('Y-m-d'),
            'date_to' => Carbon::parse($filters['date_from'])->subDay()->format('Y-m-d'),
            'company_id' => $filters['company_id'],
            'staff_id' => $filters['staff_id'],
        ];
        
        $previousKPIs = $this->analyticsService->getAgentPerformanceKPIs(
            $previousPeriod['company_id'],
            $previousPeriod['staff_id'],
            $previousPeriod['date_from'],
            $previousPeriod['date_to']
        );
        
        $currentKPIs = $this->agentKPIs;
        
        return [
            'calls_trend' => $this->calculateTrendPercentage($previousKPIs['total_calls'], $currentKPIs['total_calls']),
            'conversion_trend' => $this->calculateTrendPercentage($previousKPIs['conversion_rate'], $currentKPIs['conversion_rate']),
            'satisfaction_trend' => $this->calculateTrendPercentage($previousKPIs['avg_sentiment_score'], $currentKPIs['avg_sentiment_score']),
            'cost_trend' => $this->calculateTrendPercentage($previousKPIs['cost_per_call'], $currentKPIs['cost_per_call'], true), // Inverted - lower is better
        ];
    }
    
    private function calculateTrendPercentage(?float $previous, ?float $current, bool $inverted = false): array
    {
        if (!$previous || !$current || $previous == 0) {
            return ['percentage' => 0, 'direction' => 'stable', 'status' => 'neutral'];
        }
        
        $change = (($current - $previous) / $previous) * 100;
        if ($inverted) $change = -$change;
        
        return [
            'percentage' => round(abs($change), 1),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            'status' => $change > 5 ? 'positive' : ($change < -5 ? 'negative' : 'neutral'),
        ];
    }
    
    private function generateQuickInsights(array $kpis): array
    {
        $insights = [];
        
        if (($kpis['answer_rate'] ?? 0) > 90) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Exzellente Annahmequote von ' . ($kpis['answer_rate'] ?? 0) . '%'
            ];
        } elseif (($kpis['answer_rate'] ?? 0) < 70) {
            $insights[] = [
                'type' => 'warning',
                'message' => 'Annahmequote unter 70% - Personalkapazitäten prüfen'
            ];
        }
        
        if (($kpis['conversion_rate'] ?? 0) > 25) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Überdurchschnittliche Konversionsrate von ' . ($kpis['conversion_rate'] ?? 0) . '%'
            ];
        } elseif (($kpis['conversion_rate'] ?? 0) < 10) {
            $insights[] = [
                'type' => 'error',
                'message' => 'Niedrige Konversionsrate - Schulungsbedarf identifiziert'
            ];
        }
        
        if (($kpis['roi_percentage'] ?? 0) > 200) {
            $insights[] = [
                'type' => 'success',
                'message' => 'Ausgezeichneter ROI von ' . ($kpis['roi_percentage'] ?? 0) . '%'
            ];
        } elseif (($kpis['roi_percentage'] ?? 0) < 0) {
            $insights[] = [
                'type' => 'error',
                'message' => 'Negativer ROI - Kostenoptimierung erforderlich'
            ];
        }
        
        return array_slice($insights, 0, 3); // Top 3 insights
    }
    
    private function getBenchmarkComparisons(array $kpis): array
    {
        $benchmarks = [
            'answer_rate' => ['excellent' => 90, 'good' => 80, 'poor' => 70],
            'conversion_rate' => ['excellent' => 25, 'good' => 15, 'poor' => 10],
            'avg_sentiment_score' => ['excellent' => 4.5, 'good' => 3.5, 'poor' => 2.5],
            'cost_per_call' => ['excellent' => 1.0, 'good' => 2.0, 'poor' => 3.0], // Lower is better
        ];
        
        $comparisons = [];
        
        foreach ($benchmarks as $metric => $thresholds) {
            $value = $kpis[$metric] ?? 0;
            $isReverse = $metric === 'cost_per_call'; // Lower is better for cost
            
            if ($isReverse) {
                if ($value <= $thresholds['excellent']) $rating = 'excellent';
                elseif ($value <= $thresholds['good']) $rating = 'good';
                elseif ($value <= $thresholds['poor']) $rating = 'average';
                else $rating = 'poor';
            } else {
                if ($value >= $thresholds['excellent']) $rating = 'excellent';
                elseif ($value >= $thresholds['good']) $rating = 'good';
                elseif ($value >= $thresholds['poor']) $rating = 'average';
                else $rating = 'poor';
            }
            
            $comparisons[$metric] = [
                'value' => $value,
                'rating' => $rating,
                'benchmark' => $thresholds,
            ];
        }
        
        return $comparisons;
    }
    
    private function identifyImprovementAreas(array $kpis): array
    {
        $areas = [];
        
        if (($kpis['answer_rate'] ?? 0) < 80) {
            $areas[] = [
                'area' => 'Annahmequote',
                'current' => $kpis['answer_rate'] ?? 0,
                'target' => 85,
                'actions' => [
                    'Personalbesetzung in Stoßzeiten erhöhen',
                    'Anrufweiterleitung optimieren',
                    'Wartezeiten analysieren'
                ]
            ];
        }
        
        if (($kpis['conversion_rate'] ?? 0) < 15) {
            $areas[] = [
                'area' => 'Konversionsrate',
                'current' => $kpis['conversion_rate'] ?? 0,
                'target' => 20,
                'actions' => [
                    'Verkaufstraining durchführen',
                    'Gesprächsleitfäden überarbeiten',
                    'Einwandbehandlung verbessern'
                ]
            ];
        }
        
        if (($kpis['avg_sentiment_score'] ?? 0) < 3.5) {
            $areas[] = [
                'area' => 'Kundenzufriedenheit',
                'current' => $kpis['avg_sentiment_score'] ?? 0,
                'target' => 4.0,
                'actions' => [
                    'Empathie-Training anbieten',
                    'Aktives Zuhören schulen',
                    'Beschwerdemanagement verbessern'
                ]
            ];
        }
        
        return $areas;
    }
    
    private function getPerformanceTrends(array $filters): array
    {
        // Get daily performance data for the last 7 days
        $trends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::parse($filters['date_to'])->subDays($i);
            $dayFilters = array_merge($filters, [
                'date_from' => $date->format('Y-m-d'),
                'date_to' => $date->format('Y-m-d')
            ]);
            
            $dayKPIs = $this->analyticsService->getAgentPerformanceKPIs(
                $dayFilters['company_id'],
                $dayFilters['staff_id'],
                $dayFilters['date_from'],
                $dayFilters['date_to']
            );
            
            $trends[] = [
                'date' => $date->format('M d'),
                'calls' => $dayKPIs['total_calls'] ?? 0,
                'conversion_rate' => $dayKPIs['conversion_rate'] ?? 0,
                'satisfaction' => $dayKPIs['avg_sentiment_score'] ?? 0,
            ];
        }
        
        return $trends;
    }
    
    public function refreshData(): void
    {
        // Clear relevant caches
        $cachePattern = "agent_kpis_{$this->companyId}_{$this->staffId}_*";
        Cache::flush(); // Simple cache clear - in production, use more targeted clearing
        
        $this->loadAnalyticsData();
        
        $this->dispatch('data-refreshed');
    }
    
    public function exportData(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Export current analytics data
        $exportData = [
            'meta' => [
                'company_id' => $this->companyId,
                'staff_id' => $this->staffId,
                'date_from' => $this->dateFrom,
                'date_to' => $this->dateTo,
                'analysis_type' => $this->analysisType,
                'exported_at' => Carbon::now()->toISOString(),
            ],
            'data' => [
                'kpis' => $this->agentKPIs,
                'dashboard_data' => $this->dashboardData,
                'performance_alerts' => $this->performanceAlerts,
            ]
        ];
        
        $filename = "call_analytics_" . Carbon::now()->format('Y-m-d_H-i-s') . ".json";
        
        return response()->streamDownload(function () use ($exportData) {
            echo json_encode($exportData, JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }
    
    public function getTitle(): string
    {
        return 'Advanced Call Analytics';
    }
    
    public function getSubheading(): ?string
    {
        $companyName = $this->companyId ? Company::find($this->companyId)?->name : 'Alle Unternehmen';
        $staffName = $this->staffId ? Staff::find($this->staffId)?->name : 'Alle Mitarbeiter';
        
        return "Analysezeitraum: {$this->dateFrom} bis {$this->dateTo} | {$companyName} | {$staffName}";
    }
    
    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'analysisType' => $this->analysisType,
            'companyId' => $this->companyId,
            'staffId' => $this->staffId,
            'dateRange' => [
                'from' => $this->dateFrom,
                'to' => $this->dateTo,
            ],
            'kpis' => $this->agentKPIs,
            'dashboardData' => $this->dashboardData,
            'patternAnalysis' => $this->patternAnalysis,
            'satisfactionMetrics' => $this->satisfactionMetrics,
            'funnelData' => $this->funnelData,
            'realTimeKPIs' => $this->realTimeKPIs,
            'comparativeAnalysis' => $this->comparativeAnalysis,
            'performanceAlerts' => $this->performanceAlerts,
            'hasAlerts' => !empty($this->performanceAlerts['alerts']),
            'criticalAlerts' => $this->performanceAlerts['critical_alerts'] ?? 0,
        ]);
    }
}