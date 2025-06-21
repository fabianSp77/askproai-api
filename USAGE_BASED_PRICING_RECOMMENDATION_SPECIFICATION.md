# Usage-Based Pricing Recommendation System Specification

## Executive Summary

This specification outlines a comprehensive usage-based pricing recommendation system for AskProAI that activates after the QuickSetupWizard. The system collects usage data during the first 2 weeks of operation, analyzes patterns using AI, and provides personalized pricing plan recommendations optimized for each business's unique characteristics.

## System Overview

### Timeline & Activation

```
Day 0: QuickSetupWizard completed → Company activated with default trial pricing
Day 1-14: Data collection period → Track all usage metrics
Day 7: First interim analysis → Early insights notification
Day 14: Full analysis → Pricing recommendations generated
Day 15+: Continuous monitoring → Dynamic plan adjustments
```

### Core Components

1. **Usage Tracker Service** - Real-time usage data collection
2. **Analytics Engine** - Pattern analysis and insights generation
3. **ML Recommendation Service** - Pricing plan optimization
4. **Pricing Configurator UI** - Interactive plan customization
5. **A/B Testing Framework** - Plan performance validation

## Data Collection Architecture

### 1. Usage Metrics Tables

```sql
-- Core usage tracking table
CREATE TABLE usage_metrics (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL,
    branch_id BIGINT NULL,
    metric_type ENUM('call', 'appointment', 'customer', 'revenue', 'conversion'),
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,4) NOT NULL,
    metadata JSON NULL,
    recorded_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_company_metric_time (company_id, metric_type, recorded_at),
    INDEX idx_branch_metric_time (branch_id, metric_type, recorded_at),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- Aggregated daily metrics for faster queries
CREATE TABLE usage_daily_aggregates (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL,
    branch_id BIGINT NULL,
    date DATE NOT NULL,
    total_calls INTEGER DEFAULT 0,
    total_minutes DECIMAL(10,2) DEFAULT 0,
    successful_bookings INTEGER DEFAULT 0,
    failed_bookings INTEGER DEFAULT 0,
    unique_customers INTEGER DEFAULT 0,
    total_revenue DECIMAL(10,2) DEFAULT 0,
    peak_hour_calls INTEGER DEFAULT 0,
    after_hours_calls INTEGER DEFAULT 0,
    avg_call_duration DECIMAL(6,2) DEFAULT 0,
    conversion_rate DECIMAL(5,2) DEFAULT 0,
    
    UNIQUE KEY unique_company_branch_date (company_id, branch_id, date),
    INDEX idx_company_date (company_id, date),
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- Usage patterns analysis results
CREATE TABLE usage_patterns (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL,
    pattern_type ENUM('hourly', 'daily', 'weekly', 'seasonal'),
    pattern_data JSON NOT NULL,
    confidence_score DECIMAL(3,2) DEFAULT 0.00,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_until TIMESTAMP NULL,
    
    INDEX idx_company_pattern (company_id, pattern_type),
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- Pricing recommendations
CREATE TABLE pricing_recommendations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL,
    recommendation_type ENUM('initial', 'adjustment', 'optimization'),
    current_plan_id BIGINT NULL,
    recommended_plan_id BIGINT NULL,
    custom_pricing JSON NULL,
    reasoning JSON NOT NULL,
    confidence_score DECIMAL(3,2) DEFAULT 0.00,
    projected_savings DECIMAL(10,2) NULL,
    projected_revenue_impact DECIMAL(10,2) NULL,
    status ENUM('pending', 'viewed', 'accepted', 'rejected', 'expired') DEFAULT 'pending',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    actioned_at TIMESTAMP NULL,
    
    INDEX idx_company_status (company_id, status),
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- A/B testing experiments
CREATE TABLE pricing_experiments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL,
    experiment_name VARCHAR(100) NOT NULL,
    variant_a JSON NOT NULL,
    variant_b JSON NOT NULL,
    metrics_tracked JSON NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('planned', 'active', 'completed', 'cancelled') DEFAULT 'planned',
    winner VARCHAR(1) NULL,
    statistical_significance DECIMAL(3,2) NULL,
    
    INDEX idx_company_status (company_id, status),
    FOREIGN KEY (company_id) REFERENCES companies(id)
);
```

### 2. Tracked Metrics

#### Call Metrics
- Total call volume (per hour/day/week)
- Call duration distribution
- Peak vs off-peak patterns
- After-hours call percentage
- Cost per call analysis
- Call source tracking (direct, referral, etc.)

#### Conversion Metrics
- Call-to-booking conversion rate
- Conversion by time of day
- Conversion by service type
- Lost opportunity analysis
- Cancellation patterns

#### Customer Metrics
- New vs returning customer ratio
- Customer lifetime value projections
- Service preferences
- Geographic distribution
- No-show rates

#### Revenue Metrics
- Revenue per appointment
- Revenue by service type
- Seasonal variations
- Growth trajectory
- Price sensitivity indicators

## Machine Learning Service

### UsagePatternAnalyzer

```php
namespace App\Services\Analytics;

class UsagePatternAnalyzer
{
    private array $patterns = [];
    private float $confidenceThreshold = 0.75;
    
    public function analyzeCompanyUsage(Company $company, Carbon $startDate, Carbon $endDate): UsageAnalysis
    {
        // 1. Load raw metrics
        $metrics = $this->loadMetrics($company, $startDate, $endDate);
        
        // 2. Detect patterns
        $this->patterns = [
            'hourly' => $this->detectHourlyPatterns($metrics),
            'daily' => $this->detectDailyPatterns($metrics),
            'weekly' => $this->detectWeeklyPatterns($metrics),
            'seasonal' => $this->detectSeasonalIndicators($metrics),
        ];
        
        // 3. Calculate business characteristics
        $characteristics = $this->calculateBusinessCharacteristics($metrics);
        
        // 4. Generate insights
        $insights = $this->generateInsights($this->patterns, $characteristics);
        
        return new UsageAnalysis([
            'company_id' => $company->id,
            'period' => ['start' => $startDate, 'end' => $endDate],
            'patterns' => $this->patterns,
            'characteristics' => $characteristics,
            'insights' => $insights,
            'confidence_scores' => $this->calculateConfidenceScores(),
        ]);
    }
    
    private function detectHourlyPatterns(array $metrics): array
    {
        // Analyze call distribution by hour
        $hourlyDistribution = $this->aggregateByHour($metrics['calls']);
        
        // Identify peak hours (top 20% of traffic)
        $peakHours = $this->identifyPeakPeriods($hourlyDistribution, 0.2);
        
        // Calculate after-hours percentage
        $businessHours = $this->getBusinessHours($metrics['company']);
        $afterHoursPercentage = $this->calculateAfterHoursPercentage($hourlyDistribution, $businessHours);
        
        return [
            'peak_hours' => $peakHours,
            'quiet_hours' => $this->identifyQuietPeriods($hourlyDistribution),
            'after_hours_percentage' => $afterHoursPercentage,
            'distribution' => $hourlyDistribution,
            'pattern_strength' => $this->calculatePatternStrength($hourlyDistribution),
        ];
    }
    
    private function calculateBusinessCharacteristics(array $metrics): array
    {
        return [
            'business_type' => $this->classifyBusinessType($metrics),
            'volume_category' => $this->categorizeVolume($metrics),
            'growth_trajectory' => $this->calculateGrowthTrajectory($metrics),
            'seasonality_factor' => $this->calculateSeasonalityFactor($metrics),
            'customer_behavior' => $this->analyzeCustomerBehavior($metrics),
            'service_complexity' => $this->assessServiceComplexity($metrics),
        ];
    }
    
    private function classifyBusinessType(array $metrics): string
    {
        // High volume + Short calls + Regular hours = Medical/Healthcare
        // Low volume + Long calls + Flexible hours = Consulting
        // Medium volume + Medium calls + Extended hours = Beauty/Wellness
        
        $avgCallDuration = $metrics['avg_call_duration'];
        $callVolume = $metrics['total_calls'];
        $afterHoursRatio = $metrics['after_hours_ratio'];
        
        if ($callVolume > 100 && $avgCallDuration < 180) {
            return 'high_volume_transactional';
        } elseif ($callVolume < 30 && $avgCallDuration > 300) {
            return 'low_volume_consultative';
        } elseif ($afterHoursRatio > 0.3) {
            return 'extended_hours_service';
        } else {
            return 'standard_appointment_based';
        }
    }
}
```

### PricingRecommendationEngine

```php
namespace App\Services\Pricing;

class PricingRecommendationEngine
{
    private UsagePatternAnalyzer $analyzer;
    private PricingOptimizer $optimizer;
    private IndustryBenchmarkService $benchmarks;
    
    public function generateRecommendation(Company $company, UsageAnalysis $analysis): PricingRecommendation
    {
        // 1. Calculate current costs
        $currentCosts = $this->calculateCurrentCosts($company, $analysis);
        
        // 2. Project future usage
        $projectedUsage = $this->projectFutureUsage($analysis);
        
        // 3. Compare with industry benchmarks
        $benchmarkData = $this->benchmarks->getForIndustry($company->industry);
        
        // 4. Generate pricing options
        $pricingOptions = $this->generatePricingOptions($company, $analysis, $projectedUsage);
        
        // 5. Score and rank options
        $rankedOptions = $this->rankPricingOptions($pricingOptions, $currentCosts, $benchmarkData);
        
        // 6. Create recommendation
        return new PricingRecommendation([
            'company_id' => $company->id,
            'analysis_id' => $analysis->id,
            'current_costs' => $currentCosts,
            'projected_usage' => $projectedUsage,
            'recommended_plan' => $rankedOptions[0],
            'alternative_plans' => array_slice($rankedOptions, 1, 2),
            'custom_pricing_suggestion' => $this->generateCustomPricing($analysis),
            'reasoning' => $this->explainRecommendation($rankedOptions[0], $analysis),
            'projected_savings' => $this->calculateProjectedSavings($currentCosts, $rankedOptions[0]),
            'confidence_score' => $this->calculateConfidence($analysis),
        ]);
    }
    
    private function generatePricingOptions(Company $company, UsageAnalysis $analysis, array $projectedUsage): array
    {
        $options = [];
        
        // Option 1: Volume-based pricing
        if ($analysis->characteristics['volume_category'] === 'high') {
            $options[] = $this->createVolumePlan($projectedUsage);
        }
        
        // Option 2: Flat-rate pricing
        if ($analysis->patterns['hourly']['pattern_strength'] > 0.8) {
            $options[] = $this->createFlatRatePlan($projectedUsage);
        }
        
        // Option 3: Hybrid pricing (base + usage)
        $options[] = $this->createHybridPlan($projectedUsage);
        
        // Option 4: Time-based pricing
        if ($analysis->patterns['hourly']['after_hours_percentage'] > 20) {
            $options[] = $this->createTimeBasedPlan($projectedUsage);
        }
        
        // Option 5: Custom pricing based on specific patterns
        if ($this->hasUniquePatterns($analysis)) {
            $options[] = $this->createCustomPlan($analysis, $projectedUsage);
        }
        
        return $options;
    }
    
    private function createVolumePlan(array $projectedUsage): array
    {
        $monthlyMinutes = $projectedUsage['monthly_minutes'];
        
        return [
            'type' => 'volume_tiered',
            'name' => 'Volumen-Tarif',
            'pricing' => [
                'tiers' => [
                    ['from' => 0, 'to' => 500, 'price_per_minute' => 0.15],
                    ['from' => 501, 'to' => 1000, 'price_per_minute' => 0.12],
                    ['from' => 1001, 'to' => 2000, 'price_per_minute' => 0.10],
                    ['from' => 2001, 'to' => null, 'price_per_minute' => 0.08],
                ],
                'monthly_base_fee' => 0,
                'setup_fee' => 0,
            ],
            'benefits' => [
                'Günstiger bei hohem Volumen',
                'Keine Grundgebühr',
                'Transparente Staffelpreise',
            ],
            'estimated_monthly_cost' => $this->calculateTieredCost($monthlyMinutes),
        ];
    }
}
```

## Dashboard Components

### 1. Usage Analytics Dashboard

```php
namespace App\Filament\Admin\Widgets;

class UsageAnalyticsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.usage-analytics';
    
    public function getViewData(): array
    {
        $company = auth()->user()->company;
        $analyzer = app(UsagePatternAnalyzer::class);
        
        // Get analysis for last 14 days
        $analysis = $analyzer->analyzeCompanyUsage(
            $company,
            now()->subDays(14),
            now()
        );
        
        return [
            'currentUsage' => $this->getCurrentUsageStats($company),
            'patterns' => $analysis->patterns,
            'insights' => $analysis->insights,
            'projections' => $this->getUsageProjections($analysis),
            'recommendations' => $this->getActiveRecommendations($company),
        ];
    }
    
    private function getCurrentUsageStats(Company $company): array
    {
        return [
            'today' => [
                'calls' => $this->getTodaysCalls($company),
                'bookings' => $this->getTodaysBookings($company),
                'minutes' => $this->getTodaysMinutes($company),
                'cost' => $this->getTodaysCost($company),
            ],
            'week' => [
                'calls' => $this->getWeekCalls($company),
                'bookings' => $this->getWeekBookings($company),
                'minutes' => $this->getWeekMinutes($company),
                'cost' => $this->getWeekCost($company),
                'trend' => $this->getWeekTrend($company),
            ],
            'month' => [
                'calls' => $this->getMonthCalls($company),
                'bookings' => $this->getMonthBookings($company),
                'minutes' => $this->getMonthMinutes($company),
                'cost' => $this->getMonthCost($company),
                'projection' => $this->getMonthProjection($company),
            ],
        ];
    }
}
```

### 2. Pricing Recommendation Widget

```blade.php
{{-- resources/views/filament/admin/widgets/pricing-recommendation.blade.php --}}
<x-filament-widgets::widget>
    <x-filament::card>
        @if($recommendation)
            <div class="space-y-6">
                {{-- Recommendation Header --}}
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            Empfohlener Tarif
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Basierend auf Ihrer Nutzung der letzten {{ $analysisPeriod }} Tage
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-sm text-gray-500">Vertrauen</span>
                        <div class="flex items-center mt-1">
                            <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full"
                                     style="width: {{ $recommendation->confidence_score * 100 }}%">
                                </div>
                            </div>
                            <span class="ml-2 text-sm font-medium">
                                {{ number_format($recommendation->confidence_score * 100, 0) }}%
                            </span>
                        </div>
                    </div>
                </div>
                
                {{-- Current vs Recommended Comparison --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Current Plan --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">Aktueller Tarif</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Typ:</span>
                                <span class="text-sm font-medium">{{ $currentPlan->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Monatliche Kosten:</span>
                                <span class="text-sm font-medium">€{{ number_format($currentCosts, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Preis pro Minute:</span>
                                <span class="text-sm font-medium">€{{ number_format($currentPlan->price_per_minute, 3) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Recommended Plan --}}
                    <div class="bg-blue-50 rounded-lg p-4 ring-2 ring-blue-200">
                        <h3 class="text-sm font-medium text-blue-900 mb-3">Empfohlener Tarif</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Typ:</span>
                                <span class="text-sm font-medium">{{ $recommendation->recommended_plan['name'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Geschätzte Kosten:</span>
                                <span class="text-sm font-medium text-green-600">
                                    €{{ number_format($recommendation->estimated_monthly_cost, 2) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Ersparnis:</span>
                                <span class="text-sm font-medium text-green-600">
                                    €{{ number_format($recommendation->projected_savings, 2) }}/Monat
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Key Insights --}}
                <div class="bg-blue-50 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-900 mb-2">Wichtige Erkenntnisse</h3>
                    <ul class="space-y-1">
                        @foreach($recommendation->reasoning['insights'] as $insight)
                            <li class="text-sm text-blue-800 flex items-start">
                                <x-heroicon-o-check-circle class="w-4 h-4 text-blue-600 mt-0.5 mr-2 flex-shrink-0" />
                                {{ $insight }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                
                {{-- Action Buttons --}}
                <div class="flex gap-3">
                    <x-filament::button
                        wire:click="openPricingConfigurator"
                        color="primary"
                    >
                        Tarif anpassen
                    </x-filament::button>
                    
                    <x-filament::button
                        wire:click="acceptRecommendation"
                        color="success"
                        outlined
                    >
                        Empfehlung annehmen
                    </x-filament::button>
                    
                    <x-filament::button
                        wire:click="startAbTest"
                        color="secondary"
                        outlined
                    >
                        A/B Test starten
                    </x-filament::button>
                </div>
            </div>
        @else
            {{-- Data Collection Phase --}}
            <div class="text-center py-8">
                <x-heroicon-o-chart-bar class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">
                    Daten werden gesammelt
                </h3>
                <p class="text-sm text-gray-600 mb-4">
                    Wir analysieren Ihre Nutzungsmuster. Erste Empfehlungen in:
                </p>
                <div class="inline-flex items-center justify-center">
                    <span class="text-2xl font-bold text-blue-600">{{ $daysRemaining }}</span>
                    <span class="text-sm text-gray-600 ml-2">Tagen</span>
                </div>
                
                {{-- Progress Bar --}}
                <div class="mt-6 max-w-xs mx-auto">
                    <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 rounded-full transition-all duration-300"
                             style="width: {{ $dataCollectionProgress }}%">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        {{ $dataPointsCollected }} von {{ $dataPointsRequired }} Datenpunkten gesammelt
                    </p>
                </div>
            </div>
        @endif
    </x-filament::card>
</x-filament-widgets::widget>
```

### 3. Interactive Pricing Configurator

```php
namespace App\Filament\Admin\Pages;

class PricingConfigurator extends Page
{
    protected static string $view = 'filament.admin.pages.pricing-configurator';
    
    public array $pricingModel = [
        'type' => 'hybrid',
        'monthly_base_fee' => 49.00,
        'included_minutes' => 500,
        'price_per_minute' => 0.10,
        'overage_price' => 0.12,
    ];
    
    public function mount(): void
    {
        $recommendation = $this->getLatestRecommendation();
        if ($recommendation) {
            $this->pricingModel = $recommendation->recommended_plan['pricing'];
        }
    }
    
    public function updateProjections(): void
    {
        $this->projectedCosts = $this->calculateProjectedCosts();
        $this->comparisonData = $this->generateComparisonData();
        $this->emit('projectionsUpdated', $this->projectedCosts);
    }
    
    protected function getFormSchema(): array
    {
        return [
            Section::make('Tarifmodell')
                ->schema([
                    Select::make('pricingModel.type')
                        ->label('Tariftyp')
                        ->options([
                            'usage_based' => 'Nutzungsbasiert',
                            'flat_rate' => 'Flatrate',
                            'hybrid' => 'Hybrid (Grundgebühr + Nutzung)',
                            'tiered' => 'Gestaffelt',
                            'time_based' => 'Zeitbasiert',
                        ])
                        ->reactive()
                        ->afterStateUpdated(fn () => $this->updateProjections()),
                    
                    Grid::make(2)
                        ->schema([
                            TextInput::make('pricingModel.monthly_base_fee')
                                ->label('Monatliche Grundgebühr')
                                ->prefix('€')
                                ->numeric()
                                ->visible(fn () => in_array($this->pricingModel['type'], ['flat_rate', 'hybrid']))
                                ->reactive()
                                ->afterStateUpdated(fn () => $this->updateProjections()),
                            
                            TextInput::make('pricingModel.included_minutes')
                                ->label('Inklusive Minuten')
                                ->numeric()
                                ->visible(fn () => $this->pricingModel['type'] === 'hybrid')
                                ->reactive()
                                ->afterStateUpdated(fn () => $this->updateProjections()),
                            
                            TextInput::make('pricingModel.price_per_minute')
                                ->label('Preis pro Minute')
                                ->prefix('€')
                                ->numeric()
                                ->step(0.001)
                                ->visible(fn () => $this->pricingModel['type'] !== 'flat_rate')
                                ->reactive()
                                ->afterStateUpdated(fn () => $this->updateProjections()),
                        ]),
                ]),
            
            Section::make('Simulationsparameter')
                ->schema([
                    RangeSlider::make('simulation.monthly_minutes')
                        ->label('Geschätzte monatliche Minuten')
                        ->min(0)
                        ->max(5000)
                        ->step(100)
                        ->default($this->getAverageMonthlyMinutes())
                        ->reactive()
                        ->afterStateUpdated(fn () => $this->updateProjections()),
                    
                    Toggle::make('simulation.include_seasonality')
                        ->label('Saisonale Schwankungen berücksichtigen')
                        ->default(true)
                        ->reactive()
                        ->afterStateUpdated(fn () => $this->updateProjections()),
                ]),
        ];
    }
}
```

## API Endpoints

### 1. Usage Analytics API

```php
// routes/api.php
Route::prefix('api/v1/analytics')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/usage/summary', [UsageAnalyticsController::class, 'summary']);
    Route::get('/usage/patterns', [UsageAnalyticsController::class, 'patterns']);
    Route::get('/usage/projections', [UsageAnalyticsController::class, 'projections']);
    Route::get('/usage/export', [UsageAnalyticsController::class, 'export']);
});

Route::prefix('api/v1/pricing')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/recommendations', [PricingRecommendationController::class, 'index']);
    Route::get('/recommendations/{id}', [PricingRecommendationController::class, 'show']);
    Route::post('/recommendations/{id}/accept', [PricingRecommendationController::class, 'accept']);
    Route::post('/recommendations/{id}/reject', [PricingRecommendationController::class, 'reject']);
    Route::post('/simulate', [PricingRecommendationController::class, 'simulate']);
    Route::post('/experiments', [PricingExperimentController::class, 'create']);
    Route::get('/experiments/{id}/results', [PricingExperimentController::class, 'results']);
});
```

### 2. Controllers

```php
namespace App\Http\Controllers\Api;

class UsageAnalyticsController extends Controller
{
    private UsagePatternAnalyzer $analyzer;
    private UsageMetricsRepository $repository;
    
    public function summary(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        $period = $request->get('period', 'week'); // day, week, month, custom
        
        $summary = $this->repository->getUsageSummary($company, $period);
        
        return response()->json([
            'data' => [
                'period' => $period,
                'metrics' => $summary,
                'comparison' => $this->getComparison($company, $period),
                'trends' => $this->getTrends($company, $period),
            ],
        ]);
    }
    
    public function patterns(Request $request): JsonResponse
    {
        $company = $request->user()->company;
        $analysis = $this->analyzer->analyzeCompanyUsage(
            $company,
            Carbon::parse($request->get('start_date', now()->subDays(14))),
            Carbon::parse($request->get('end_date', now()))
        );
        
        return response()->json([
            'data' => [
                'patterns' => $analysis->patterns,
                'characteristics' => $analysis->characteristics,
                'insights' => $analysis->insights,
                'confidence_scores' => $analysis->confidence_scores,
            ],
        ]);
    }
}
```

## Email Campaign Integration

### 1. Recommendation Notification

```php
namespace App\Mail;

class PricingRecommendationReady extends Mailable
{
    use Queueable, SerializesModels;
    
    public function build()
    {
        return $this->subject('Ihre personalisierte Tarifempfehlung ist bereit!')
            ->markdown('emails.pricing-recommendation', [
                'company' => $this->company,
                'recommendation' => $this->recommendation,
                'savingsAmount' => $this->recommendation->projected_savings,
                'actionUrl' => route('admin.pricing.recommendations.show', $this->recommendation),
            ]);
    }
}
```

### 2. Usage Milestone Alerts

```php
namespace App\Services\Notifications;

class UsageMilestoneService
{
    public function checkMilestones(Company $company): void
    {
        $usage = $this->getCurrentUsage($company);
        
        // First week milestone
        if ($this->isFirstWeekComplete($company) && !$this->hasNotifiedFirstWeek($company)) {
            $this->sendFirstWeekInsights($company, $usage);
        }
        
        // High usage alert
        if ($this->isApproachingLimit($usage) && !$this->hasNotifiedToday($company, 'limit_warning')) {
            $this->sendUsageLimitWarning($company, $usage);
        }
        
        // Cost optimization opportunity
        if ($this->hasCostOptimizationOpportunity($usage)) {
            $this->sendOptimizationSuggestion($company, $usage);
        }
    }
}
```

## Implementation Timeline

### Phase 1: Foundation (Week 1)
- Create database tables
- Implement UsageTrackerService
- Set up basic metrics collection
- Create usage_metrics API endpoints

### Phase 2: Analytics (Week 2)
- Implement UsagePatternAnalyzer
- Create pattern detection algorithms
- Build analytics dashboard widget
- Set up data aggregation jobs

### Phase 3: ML & Recommendations (Week 3)
- Implement PricingRecommendationEngine
- Create recommendation algorithms
- Build recommendation UI components
- Set up A/B testing framework

### Phase 4: Interactive Tools (Week 4)
- Build PricingConfigurator page
- Create simulation tools
- Implement comparison features
- Add export functionality

### Phase 5: Integration & Polish (Week 5)
- Email campaign setup
- API documentation
- Performance optimization
- User testing and refinement

## Performance Considerations

### 1. Data Collection Optimization
- Use batch inserts for metrics (every 100 records)
- Implement Redis caching for real-time metrics
- Use database partitioning for usage_metrics table
- Archive old data after 90 days

### 2. Query Optimization
```sql
-- Optimized index for pattern analysis
CREATE INDEX idx_usage_pattern_analysis 
ON usage_metrics (company_id, metric_type, recorded_at)
INCLUDE (metric_value, metadata);

-- Materialized view for daily summaries
CREATE MATERIALIZED VIEW mv_daily_usage_summary AS
SELECT 
    company_id,
    DATE(recorded_at) as date,
    metric_type,
    COUNT(*) as event_count,
    SUM(metric_value) as total_value,
    AVG(metric_value) as avg_value
FROM usage_metrics
GROUP BY company_id, DATE(recorded_at), metric_type;
```

### 3. Caching Strategy
- Cache analysis results for 1 hour
- Cache recommendations for 24 hours
- Use Redis for real-time metrics
- Implement cache warming for dashboards

## Security & Privacy

### 1. Data Protection
- Encrypt sensitive pricing data at rest
- Use row-level security for multi-tenancy
- Implement audit logging for pricing changes
- Anonymize data for benchmarking

### 2. Access Control
```php
// Policy for pricing recommendations
class PricingRecommendationPolicy
{
    public function view(User $user, PricingRecommendation $recommendation): bool
    {
        return $user->company_id === $recommendation->company_id
            && $user->hasPermission('view_pricing_recommendations');
    }
    
    public function accept(User $user, PricingRecommendation $recommendation): bool
    {
        return $user->company_id === $recommendation->company_id
            && $user->hasPermission('manage_pricing')
            && $recommendation->status === 'pending'
            && $recommendation->expires_at->isFuture();
    }
}
```

## Monitoring & Success Metrics

### 1. System Metrics
- Data collection reliability (target: 99.9%)
- Analysis job completion rate
- Recommendation generation time (<5 seconds)
- API response times (<200ms p95)

### 2. Business Metrics
- Recommendation acceptance rate (target: >40%)
- Average cost savings achieved
- Customer satisfaction scores
- Churn reduction after optimization

### 3. Alerting Rules
```yaml
alerts:
  - name: HighDataCollectionFailureRate
    expr: rate(usage_metrics_collection_failures[5m]) > 0.1
    severity: warning
    
  - name: RecommendationGenerationSlow
    expr: histogram_quantile(0.95, recommendation_generation_duration) > 5
    severity: warning
    
  - name: LowRecommendationAcceptance
    expr: recommendation_acceptance_rate < 0.2
    severity: info
```

## Future Enhancements

### 1. Advanced ML Features
- Predictive churn analysis based on usage patterns
- Automated pricing experiments
- Multi-variate optimization
- Competitor pricing intelligence

### 2. Enhanced Integrations
- Direct integration with billing systems
- Automated invoice adjustments
- Usage-based discounting
- Partner commission calculations

### 3. Extended Analytics
- Customer segment analysis
- Service profitability tracking
- Geographic usage patterns
- Cross-branch optimization

This comprehensive specification provides a complete blueprint for implementing a sophisticated usage-based pricing recommendation system that will help AskProAI customers optimize their costs while maximizing value from the platform.