<?php

namespace App\Services\MCP;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Staff;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsMCPServer extends BaseMCPServer
{
    protected string $name = 'analytics';
    protected string $version = '1.0.0';
    protected string $description = 'Advanced analytics and predictive insights for business intelligence';
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getVersion(): string
    {
        return $this->version;
    }
    
    public function getTools(): array
    {
        return [
            [
                'name' => 'getBusinessMetrics',
                'description' => 'Get comprehensive business metrics with trends',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'description' => 'Company ID'],
                        'branch_id' => ['type' => 'integer', 'description' => 'Optional branch ID'],
                        'date_from' => ['type' => 'string', 'description' => 'Start date (Y-m-d)'],
                        'date_to' => ['type' => 'string', 'description' => 'End date (Y-m-d)'],
                        'metrics' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Metrics to include: revenue, appointments, calls, customers, conversion, utilization'
                        ],
                        'comparison_period' => ['type' => 'string', 'description' => 'previous_period, previous_year, custom']
                    ],
                    'required' => ['company_id', 'date_from', 'date_to']
                ]
            ],
            [
                'name' => 'predictRevenue',
                'description' => 'Predict future revenue based on historical data and trends',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer'],
                        'branch_id' => ['type' => 'integer'],
                        'prediction_days' => ['type' => 'integer', 'description' => 'Days to predict (7-90)'],
                        'confidence_level' => ['type' => 'number', 'description' => 'Confidence level (0.8-0.95)'],
                        'include_seasonality' => ['type' => 'boolean'],
                        'include_growth_trend' => ['type' => 'boolean']
                    ],
                    'required' => ['company_id', 'prediction_days']
                ]
            ],
            [
                'name' => 'predictAppointmentDemand',
                'description' => 'Predict appointment demand for optimal staff scheduling',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer'],
                        'branch_id' => ['type' => 'integer'],
                        'date_from' => ['type' => 'string'],
                        'date_to' => ['type' => 'string'],
                        'service_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                        'granularity' => ['type' => 'string', 'description' => 'hourly, daily, weekly']
                    ],
                    'required' => ['company_id', 'date_from', 'date_to']
                ]
            ],
            [
                'name' => 'analyzeCustomerBehavior',
                'description' => 'Analyze customer behavior patterns and predict churn',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer'],
                        'analysis_type' => [
                            'type' => 'string',
                            'enum' => ['churn_risk', 'lifetime_value', 'segmentation', 'preferences']
                        ],
                        'include_predictions' => ['type' => 'boolean'],
                        'limit' => ['type' => 'integer', 'description' => 'Number of customers to analyze']
                    ],
                    'required' => ['company_id', 'analysis_type']
                ]
            ],
            [
                'name' => 'getPerformanceInsights',
                'description' => 'Get AI-powered performance insights and recommendations',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer'],
                        'branch_id' => ['type' => 'integer'],
                        'focus_areas' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'staff_productivity, service_efficiency, revenue_optimization, customer_satisfaction'
                        ],
                        'include_recommendations' => ['type' => 'boolean']
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'detectAnomalies',
                'description' => 'Detect anomalies in business metrics',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer'],
                        'metric_type' => ['type' => 'string', 'enum' => ['revenue', 'appointments', 'calls', 'cancellations']],
                        'sensitivity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                        'lookback_days' => ['type' => 'integer', 'description' => 'Days to analyze (7-90)']
                    ],
                    'required' => ['company_id', 'metric_type']
                ]
            ],
            [
                'name' => 'optimizeScheduling',
                'description' => 'Get AI-powered scheduling optimization recommendations',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer'],
                        'branch_id' => ['type' => 'integer'],
                        'optimization_goal' => [
                            'type' => 'string',
                            'enum' => ['maximize_revenue', 'minimize_wait_time', 'balance_workload', 'reduce_overtime']
                        ],
                        'constraints' => ['type' => 'object', 'description' => 'Scheduling constraints']
                    ],
                    'required' => ['company_id', 'optimization_goal']
                ]
            ],
            [
                'name' => 'generateReport',
                'description' => 'Generate comprehensive analytics report',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer'],
                        'report_type' => [
                            'type' => 'string',
                            'enum' => ['executive_summary', 'detailed_analytics', 'predictive_insights', 'custom']
                        ],
                        'date_range' => ['type' => 'string', 'description' => 'this_month, last_month, custom'],
                        'format' => ['type' => 'string', 'enum' => ['json', 'pdf', 'excel']],
                        'sections' => ['type' => 'array', 'items' => ['type' => 'string']]
                    ],
                    'required' => ['company_id', 'report_type']
                ]
            ]
        ];
    }
    
    public function executeTool(string $name, array $arguments): array
    {
        $this->validateAccess($arguments['company_id'] ?? null);
        
        return match ($name) {
            'getBusinessMetrics' => $this->getBusinessMetrics($arguments),
            'predictRevenue' => $this->predictRevenue($arguments),
            'predictAppointmentDemand' => $this->predictAppointmentDemand($arguments),
            'analyzeCustomerBehavior' => $this->analyzeCustomerBehavior($arguments),
            'getPerformanceInsights' => $this->getPerformanceInsights($arguments),
            'detectAnomalies' => $this->detectAnomalies($arguments),
            'optimizeScheduling' => $this->optimizeScheduling($arguments),
            'generateReport' => $this->generateReport($arguments),
            default => ['error' => "Unknown tool: {$name}"]
        };
    }
    
    protected function getBusinessMetrics(array $args): array
    {
        $cacheKey = "analytics:metrics:{$args['company_id']}:" . md5(json_encode($args));
        
        return Cache::remember($cacheKey, 300, function () use ($args) {
            $dateFrom = Carbon::parse($args['date_from']);
            $dateTo = Carbon::parse($args['date_to']);
            $metrics = $args['metrics'] ?? ['revenue', 'appointments', 'calls', 'customers', 'conversion'];
            
            $result = [
                'period' => [
                    'from' => $dateFrom->toDateString(),
                    'to' => $dateTo->toDateString(),
                    'days' => $dateFrom->diffInDays($dateTo) + 1
                ],
                'metrics' => []
            ];
            
            // Revenue metrics
            if (in_array('revenue', $metrics)) {
                $revenue = $this->calculateRevenue($args['company_id'], $args['branch_id'] ?? null, $dateFrom, $dateTo);
                $result['metrics']['revenue'] = $revenue;
            }
            
            // Appointment metrics
            if (in_array('appointments', $metrics)) {
                $appointments = $this->calculateAppointmentMetrics($args['company_id'], $args['branch_id'] ?? null, $dateFrom, $dateTo);
                $result['metrics']['appointments'] = $appointments;
            }
            
            // Call metrics
            if (in_array('calls', $metrics)) {
                $calls = $this->calculateCallMetrics($args['company_id'], $args['branch_id'] ?? null, $dateFrom, $dateTo);
                $result['metrics']['calls'] = $calls;
            }
            
            // Customer metrics
            if (in_array('customers', $metrics)) {
                $customers = $this->calculateCustomerMetrics($args['company_id'], $args['branch_id'] ?? null, $dateFrom, $dateTo);
                $result['metrics']['customers'] = $customers;
            }
            
            // Conversion metrics
            if (in_array('conversion', $metrics)) {
                $conversion = $this->calculateConversionMetrics($args['company_id'], $args['branch_id'] ?? null, $dateFrom, $dateTo);
                $result['metrics']['conversion'] = $conversion;
            }
            
            // Add comparison if requested
            if ($args['comparison_period'] ?? false) {
                $result['comparison'] = $this->calculateComparison($result['metrics'], $args);
            }
            
            return $result;
        });
    }
    
    protected function predictRevenue(array $args): array
    {
        $predictionDays = min(90, max(7, $args['prediction_days']));
        $confidenceLevel = $args['confidence_level'] ?? 0.9;
        
        // Get historical data (last 180 days)
        $historicalData = $this->getHistoricalRevenue(
            $args['company_id'],
            $args['branch_id'] ?? null,
            180
        );
        
        if (count($historicalData) < 30) {
            return [
                'error' => 'Insufficient historical data for prediction',
                'required_days' => 30,
                'available_days' => count($historicalData)
            ];
        }
        
        // Calculate trends
        $trendAnalysis = $this->analyzeTrend($historicalData);
        
        // Apply prediction models
        $predictions = [];
        $startDate = Carbon::now();
        
        for ($i = 1; $i <= $predictionDays; $i++) {
            $date = $startDate->copy()->addDays($i);
            
            // Base prediction using moving average
            $baseValue = $this->calculateMovingAverage($historicalData, 7);
            
            // Apply trend
            if ($args['include_growth_trend'] ?? true) {
                $baseValue *= (1 + $trendAnalysis['growth_rate'] * $i / 365);
            }
            
            // Apply seasonality
            if ($args['include_seasonality'] ?? true) {
                $seasonalFactor = $this->getSeasonalFactor($date, $historicalData);
                $baseValue *= $seasonalFactor;
            }
            
            // Calculate confidence intervals
            $stdDev = $trendAnalysis['volatility'] * $baseValue;
            $zScore = $this->getZScore($confidenceLevel);
            
            $predictions[] = [
                'date' => $date->toDateString(),
                'predicted_value' => round($baseValue, 2),
                'lower_bound' => round($baseValue - ($zScore * $stdDev), 2),
                'upper_bound' => round($baseValue + ($zScore * $stdDev), 2),
                'confidence_level' => $confidenceLevel
            ];
        }
        
        return [
            'predictions' => $predictions,
            'summary' => [
                'total_predicted' => array_sum(array_column($predictions, 'predicted_value')),
                'average_daily' => round(array_sum(array_column($predictions, 'predicted_value')) / count($predictions), 2),
                'growth_trend' => $trendAnalysis['growth_rate'],
                'confidence_level' => $confidenceLevel,
                'model_accuracy' => $this->calculateModelAccuracy($historicalData)
            ],
            'factors' => [
                'trend' => $args['include_growth_trend'] ?? true,
                'seasonality' => $args['include_seasonality'] ?? true,
                'historical_days' => count($historicalData)
            ]
        ];
    }
    
    protected function predictAppointmentDemand(array $args): array
    {
        $dateFrom = Carbon::parse($args['date_from']);
        $dateTo = Carbon::parse($args['date_to']);
        $granularity = $args['granularity'] ?? 'daily';
        
        // Get historical patterns
        $historicalPatterns = $this->getHistoricalAppointmentPatterns(
            $args['company_id'],
            $args['branch_id'] ?? null,
            90 // Last 90 days
        );
        
        $predictions = [];
        $currentDate = $dateFrom->copy();
        
        while ($currentDate <= $dateTo) {
            if ($granularity === 'hourly') {
                $dayPredictions = [];
                for ($hour = 8; $hour <= 18; $hour++) {
                    $demand = $this->predictHourlyDemand($currentDate, $hour, $historicalPatterns);
                    $dayPredictions[] = [
                        'hour' => sprintf('%02d:00', $hour),
                        'predicted_appointments' => $demand['count'],
                        'confidence' => $demand['confidence'],
                        'recommended_staff' => $demand['recommended_staff']
                    ];
                }
                $predictions[$currentDate->toDateString()] = $dayPredictions;
            } else {
                $demand = $this->predictDailyDemand($currentDate, $historicalPatterns);
                $predictions[] = [
                    'date' => $currentDate->toDateString(),
                    'day_of_week' => $currentDate->format('l'),
                    'predicted_appointments' => $demand['count'],
                    'confidence' => $demand['confidence'],
                    'peak_hours' => $demand['peak_hours'],
                    'recommended_staff' => $demand['recommended_staff']
                ];
            }
            
            $currentDate->addDay();
        }
        
        // Calculate insights
        $insights = $this->generateDemandInsights($predictions, $historicalPatterns);
        
        return [
            'predictions' => $predictions,
            'insights' => $insights,
            'recommendations' => $this->generateStaffingRecommendations($predictions),
            'accuracy_metrics' => [
                'model_confidence' => 0.85,
                'based_on_days' => 90,
                'pattern_strength' => $historicalPatterns['pattern_strength'] ?? 0.8
            ]
        ];
    }
    
    protected function analyzeCustomerBehavior(array $args): array
    {
        $analysisType = $args['analysis_type'];
        $limit = $args['limit'] ?? 100;
        
        switch ($analysisType) {
            case 'churn_risk':
                return $this->analyzeChurnRisk($args['company_id'], $limit, $args['include_predictions'] ?? true);
                
            case 'lifetime_value':
                return $this->analyzeLifetimeValue($args['company_id'], $limit);
                
            case 'segmentation':
                return $this->performCustomerSegmentation($args['company_id']);
                
            case 'preferences':
                return $this->analyzeCustomerPreferences($args['company_id'], $limit);
                
            default:
                return ['error' => 'Invalid analysis type'];
        }
    }
    
    protected function analyzeChurnRisk(int $companyId, int $limit, bool $includePredictions): array
    {
        // Get customers with their appointment history
        $customers = Customer::where('company_id', $companyId)
            ->with(['appointments' => function ($query) {
                $query->orderBy('scheduled_at', 'desc')->limit(10);
            }])
            ->withCount(['appointments', 'appointments as cancelled_appointments_count' => function ($query) {
                $query->where('status', 'cancelled');
            }])
            ->limit($limit)
            ->get();
        
        $analysis = [];
        
        foreach ($customers as $customer) {
            $riskScore = $this->calculateChurnRiskScore($customer);
            
            $customerAnalysis = [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'churn_risk_score' => $riskScore,
                'risk_level' => $this->getRiskLevel($riskScore),
                'factors' => $this->getChurnFactors($customer),
                'last_appointment' => $customer->appointments->first()?->scheduled_at,
                'total_appointments' => $customer->appointments_count,
                'cancellation_rate' => $customer->appointments_count > 0 
                    ? round($customer->cancelled_appointments_count / $customer->appointments_count * 100, 2) 
                    : 0
            ];
            
            if ($includePredictions) {
                $customerAnalysis['predicted_churn_date'] = $this->predictChurnDate($customer, $riskScore);
                $customerAnalysis['retention_strategies'] = $this->suggestRetentionStrategies($customer, $riskScore);
            }
            
            $analysis[] = $customerAnalysis;
        }
        
        // Sort by risk score
        usort($analysis, fn($a, $b) => $b['churn_risk_score'] <=> $a['churn_risk_score']);
        
        return [
            'customers_at_risk' => $analysis,
            'summary' => [
                'total_analyzed' => count($analysis),
                'high_risk_count' => count(array_filter($analysis, fn($c) => $c['risk_level'] === 'high')),
                'medium_risk_count' => count(array_filter($analysis, fn($c) => $c['risk_level'] === 'medium')),
                'low_risk_count' => count(array_filter($analysis, fn($c) => $c['risk_level'] === 'low')),
                'average_risk_score' => round(array_sum(array_column($analysis, 'churn_risk_score')) / count($analysis), 2)
            ],
            'recommendations' => $this->generateChurnPreventionRecommendations($analysis)
        ];
    }
    
    protected function getPerformanceInsights(array $args): array
    {
        $focusAreas = $args['focus_areas'] ?? ['staff_productivity', 'service_efficiency', 'revenue_optimization'];
        $insights = [];
        
        foreach ($focusAreas as $area) {
            switch ($area) {
                case 'staff_productivity':
                    $insights['staff_productivity'] = $this->analyzeStaffProductivity(
                        $args['company_id'],
                        $args['branch_id'] ?? null
                    );
                    break;
                    
                case 'service_efficiency':
                    $insights['service_efficiency'] = $this->analyzeServiceEfficiency(
                        $args['company_id'],
                        $args['branch_id'] ?? null
                    );
                    break;
                    
                case 'revenue_optimization':
                    $insights['revenue_optimization'] = $this->analyzeRevenueOptimization(
                        $args['company_id'],
                        $args['branch_id'] ?? null
                    );
                    break;
                    
                case 'customer_satisfaction':
                    $insights['customer_satisfaction'] = $this->analyzeCustomerSatisfaction(
                        $args['company_id'],
                        $args['branch_id'] ?? null
                    );
                    break;
            }
        }
        
        // Generate overall insights
        $overallInsights = $this->generateOverallInsights($insights);
        
        $result = [
            'insights' => $insights,
            'overall' => $overallInsights,
            'key_metrics' => $this->extractKeyMetrics($insights),
        ];
        
        if ($args['include_recommendations'] ?? true) {
            $result['recommendations'] = $this->generateActionableRecommendations($insights);
            $result['quick_wins'] = $this->identifyQuickWins($insights);
        }
        
        return $result;
    }
    
    protected function detectAnomalies(array $args): array
    {
        $metricType = $args['metric_type'];
        $sensitivity = $args['sensitivity'] ?? 'medium';
        $lookbackDays = $args['lookback_days'] ?? 30;
        
        // Get historical data
        $historicalData = $this->getMetricHistory(
            $args['company_id'],
            $metricType,
            $lookbackDays
        );
        
        if (empty($historicalData)) {
            return ['error' => 'No historical data available'];
        }
        
        // Calculate statistical parameters
        $stats = $this->calculateStatistics($historicalData);
        
        // Detect anomalies using multiple methods
        $anomalies = [];
        
        // 1. Z-score method
        $zThreshold = match($sensitivity) {
            'low' => 3,
            'medium' => 2.5,
            'high' => 2,
            default => 2.5
        };
        
        foreach ($historicalData as $dataPoint) {
            $zScore = abs(($dataPoint['value'] - $stats['mean']) / $stats['std_dev']);
            
            if ($zScore > $zThreshold) {
                $anomalies[] = [
                    'date' => $dataPoint['date'],
                    'value' => $dataPoint['value'],
                    'expected_range' => [
                        'min' => round($stats['mean'] - ($zThreshold * $stats['std_dev']), 2),
                        'max' => round($stats['mean'] + ($zThreshold * $stats['std_dev']), 2)
                    ],
                    'deviation' => round($zScore, 2),
                    'type' => $dataPoint['value'] > $stats['mean'] ? 'spike' : 'drop',
                    'severity' => $this->calculateSeverity($zScore),
                    'possible_causes' => $this->suggestCauses($metricType, $dataPoint, $stats)
                ];
            }
        }
        
        // 2. Trend-based anomalies
        $trendAnomalies = $this->detectTrendAnomalies($historicalData, $sensitivity);
        $anomalies = array_merge($anomalies, $trendAnomalies);
        
        // Sort by date
        usort($anomalies, fn($a, $b) => $b['date'] <=> $a['date']);
        
        return [
            'anomalies' => $anomalies,
            'statistics' => $stats,
            'summary' => [
                'total_anomalies' => count($anomalies),
                'critical_anomalies' => count(array_filter($anomalies, fn($a) => $a['severity'] === 'critical')),
                'recent_trend' => $this->calculateRecentTrend($historicalData),
                'health_score' => $this->calculateMetricHealth($anomalies, count($historicalData))
            ],
            'alerts' => $this->generateAlerts($anomalies, $metricType)
        ];
    }
    
    protected function optimizeScheduling(array $args): array
    {
        $goal = $args['optimization_goal'];
        $constraints = $args['constraints'] ?? [];
        
        // Get current scheduling data
        $currentSchedule = $this->getCurrentSchedule(
            $args['company_id'],
            $args['branch_id'] ?? null
        );
        
        // Get demand predictions
        $demandPredictions = $this->predictAppointmentDemand([
            'company_id' => $args['company_id'],
            'branch_id' => $args['branch_id'] ?? null,
            'date_from' => now()->toDateString(),
            'date_to' => now()->addDays(14)->toDateString(),
            'granularity' => 'hourly'
        ]);
        
        // Apply optimization algorithm based on goal
        $optimizedSchedule = match($goal) {
            'maximize_revenue' => $this->optimizeForRevenue($currentSchedule, $demandPredictions, $constraints),
            'minimize_wait_time' => $this->optimizeForWaitTime($currentSchedule, $demandPredictions, $constraints),
            'balance_workload' => $this->optimizeForWorkloadBalance($currentSchedule, $demandPredictions, $constraints),
            'reduce_overtime' => $this->optimizeForOvertimeReduction($currentSchedule, $demandPredictions, $constraints),
            default => $currentSchedule
        };
        
        // Calculate improvements
        $improvements = $this->calculateSchedulingImprovements(
            $currentSchedule,
            $optimizedSchedule,
            $goal
        );
        
        return [
            'current_metrics' => $currentSchedule['metrics'],
            'optimized_metrics' => $optimizedSchedule['metrics'],
            'improvements' => $improvements,
            'recommendations' => $optimizedSchedule['changes'],
            'implementation_plan' => $this->generateImplementationPlan($optimizedSchedule['changes']),
            'expected_impact' => [
                'revenue_change' => $improvements['revenue_impact'] ?? 0,
                'efficiency_gain' => $improvements['efficiency_gain'] ?? 0,
                'staff_satisfaction' => $improvements['staff_satisfaction_impact'] ?? 0,
                'customer_satisfaction' => $improvements['customer_satisfaction_impact'] ?? 0
            ]
        ];
    }
    
    protected function generateReport(array $args): array
    {
        $reportType = $args['report_type'];
        $format = $args['format'] ?? 'json';
        $sections = $args['sections'] ?? $this->getDefaultSections($reportType);
        
        // Determine date range
        $dateRange = $this->parseDateRange($args['date_range'] ?? 'this_month');
        
        // Collect data for all sections
        $reportData = [
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'report_type' => $reportType,
                'company_id' => $args['company_id'],
                'date_range' => $dateRange,
                'sections' => $sections
            ],
            'content' => []
        ];
        
        foreach ($sections as $section) {
            $reportData['content'][$section] = $this->generateReportSection(
                $section,
                $args['company_id'],
                $dateRange,
                $reportType
            );
        }
        
        // Add executive summary if needed
        if ($reportType === 'executive_summary' || in_array('summary', $sections)) {
            $reportData['content']['executive_summary'] = $this->generateExecutiveSummary($reportData['content']);
        }
        
        // Format output based on requested format
        if ($format === 'json') {
            return $reportData;
        } elseif ($format === 'pdf') {
            // Generate PDF URL
            $pdfUrl = $this->generatePdfReport($reportData);
            return [
                'url' => $pdfUrl,
                'expires_at' => now()->addHours(24)->toIso8601String()
            ];
        } elseif ($format === 'excel') {
            // Generate Excel URL
            $excelUrl = $this->generateExcelReport($reportData);
            return [
                'url' => $excelUrl,
                'expires_at' => now()->addHours(24)->toIso8601String()
            ];
        }
        
        return $reportData;
    }
    
    // Helper methods
    
    protected function calculateRevenue(int $companyId, ?int $branchId, Carbon $from, Carbon $to): array
    {
        $query = Invoice::where('company_id', $companyId)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'paid');
            
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $total = $query->sum('total_amount');
        $count = $query->count();
        $avgTransaction = $count > 0 ? $total / $count : 0;
        
        // Daily breakdown
        $dailyRevenue = $query->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('revenue', 'date')
            ->toArray();
        
        return [
            'total' => round($total, 2),
            'count' => $count,
            'average_transaction' => round($avgTransaction, 2),
            'daily_average' => round($total / max(1, $from->diffInDays($to) + 1), 2),
            'daily_breakdown' => $dailyRevenue,
            'growth_rate' => $this->calculateGrowthRate($dailyRevenue)
        ];
    }
    
    protected function calculateChurnRiskScore(Customer $customer): float
    {
        $score = 0;
        $weights = [
            'recency' => 0.3,
            'frequency' => 0.25,
            'monetary' => 0.2,
            'cancellation_rate' => 0.15,
            'engagement' => 0.1
        ];
        
        // Recency score (days since last appointment)
        $lastAppointment = $customer->appointments->first();
        if ($lastAppointment) {
            $daysSince = Carbon::parse($lastAppointment->scheduled_at)->diffInDays(now());
            $recencyScore = min(100, $daysSince / 90 * 100); // 90 days = 100% risk
        } else {
            $recencyScore = 100;
        }
        $score += $recencyScore * $weights['recency'];
        
        // Frequency score (appointments per month)
        $avgFrequency = $this->calculateAverageFrequency($customer);
        $frequencyScore = max(0, 100 - ($avgFrequency * 20)); // 5+ per month = 0% risk
        $score += $frequencyScore * $weights['frequency'];
        
        // Monetary score (average spend)
        $avgSpend = $this->calculateAverageSpend($customer);
        $monetaryScore = max(0, 100 - min(100, $avgSpend / 200 * 100)); // €200+ = 0% risk
        $score += $monetaryScore * $weights['monetary'];
        
        // Cancellation rate
        $cancellationRate = $customer->appointments_count > 0
            ? $customer->cancelled_appointments_count / $customer->appointments_count * 100
            : 0;
        $score += $cancellationRate * $weights['cancellation_rate'];
        
        // Engagement score (responses, feedback, etc.)
        $engagementScore = $this->calculateEngagementScore($customer);
        $score += (100 - $engagementScore) * $weights['engagement'];
        
        return round($score, 2);
    }
    
    protected function analyzeTrend(array $data): array
    {
        if (count($data) < 2) {
            return ['growth_rate' => 0, 'volatility' => 0, 'trend' => 'stable'];
        }
        
        // Calculate linear regression
        $x = range(0, count($data) - 1);
        $y = array_values($data);
        
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        // Calculate volatility (standard deviation)
        $mean = array_sum($y) / count($y);
        $variance = 0;
        foreach ($y as $value) {
            $variance += pow($value - $mean, 2);
        }
        $stdDev = sqrt($variance / count($y));
        $volatility = $mean > 0 ? $stdDev / $mean : 0;
        
        // Determine trend
        $growthRate = $mean > 0 ? $slope / $mean : 0;
        $trend = $growthRate > 0.01 ? 'growing' : ($growthRate < -0.01 ? 'declining' : 'stable');
        
        return [
            'growth_rate' => round($growthRate, 4),
            'volatility' => round($volatility, 4),
            'trend' => $trend,
            'slope' => $slope,
            'intercept' => $intercept
        ];
    }
    
    protected function getZScore(float $confidenceLevel): float
    {
        // Common z-scores for confidence levels
        $zScores = [
            0.80 => 1.282,
            0.85 => 1.440,
            0.90 => 1.645,
            0.95 => 1.960,
            0.99 => 2.576
        ];
        
        return $zScores[$confidenceLevel] ?? 1.645;
    }
}