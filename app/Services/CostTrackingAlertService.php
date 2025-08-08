<?php

namespace App\Services;

use App\Models\BillingAlert;
use App\Models\BillingAlertConfig;
use App\Models\PrepaidBalance;
use App\Models\Company;
use App\Models\Call;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class CostTrackingAlertService
{
    protected NotificationService $notificationService;
    
    // Cache keys for performance optimization
    const CACHE_PREFIX = 'cost_tracking_alerts:';
    const CACHE_TTL = 300; // 5 minutes
    
    // Alert types specific to cost tracking
    const TYPE_LOW_BALANCE = 'low_balance';
    const TYPE_USAGE_SPIKE = 'usage_spike';
    const TYPE_BUDGET_EXCEEDED = 'budget_exceeded';
    const TYPE_ZERO_BALANCE = 'zero_balance';
    const TYPE_COST_ANOMALY = 'cost_anomaly';
    
    // Default thresholds (percentages)
    const DEFAULT_LOW_BALANCE_THRESHOLDS = [25, 10, 5];
    const DEFAULT_USAGE_SPIKE_THRESHOLD = 200; // 200% of average usage
    const DEFAULT_ANOMALY_MULTIPLIER = 3.0; // 3x average cost
    
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Check all cost-related alerts for all active companies
     */
    public function checkAllCostAlerts(): array
    {
        $results = [
            'processed' => 0,
            'alerts_created' => 0,
            'notifications_sent' => 0,
            'errors' => []
        ];

        try {
            $companies = Company::with(['prepaidBalance', 'billingAlertConfigs'])
                ->whereHas('prepaidBalance')
                ->where('is_active', true)
                ->get();

            foreach ($companies as $company) {
                try {
                    $companyResults = $this->checkCompanyCostAlerts($company);
                    
                    $results['processed']++;
                    $results['alerts_created'] += $companyResults['alerts_created'];
                    $results['notifications_sent'] += $companyResults['notifications_sent'];
                    
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'company_id' => $company->id,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::error('Cost alert check failed for company', [
                        'company_id' => $company->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            Log::info('Cost tracking alerts check completed', $results);
            
        } catch (\Exception $e) {
            Log::error('Critical error in cost tracking alerts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $results['errors'][] = [
                'type' => 'critical',
                'error' => $e->getMessage()
            ];
        }

        return $results;
    }

    /**
     * Check cost alerts for a specific company
     */
    public function checkCompanyCostAlerts(Company $company): array
    {
        $results = [
            'alerts_created' => 0,
            'notifications_sent' => 0
        ];

        if (!$company->prepaidBalance) {
            return $results;
        }

        $prepaidBalance = $company->prepaidBalance;
        
        // Get cached recent usage for performance
        $usageData = $this->getCachedUsageData($company);
        
        // Check different types of cost alerts
        $alertChecks = [
            'checkLowBalanceAlerts',
            'checkZeroBalanceAlerts',
            'checkUsageSpikeAlerts',
            'checkBudgetExceededAlerts',
            'checkCostAnomalyAlerts'
        ];

        foreach ($alertChecks as $checkMethod) {
            try {
                $checkResults = $this->$checkMethod($company, $prepaidBalance, $usageData);
                
                $results['alerts_created'] += $checkResults['alerts_created'];
                $results['notifications_sent'] += $checkResults['notifications_sent'];
                
            } catch (\Exception $e) {
                Log::error("Cost alert check method failed: {$checkMethod}", [
                    'company_id' => $company->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $results;
    }

    /**
     * Check low balance alerts
     */
    protected function checkLowBalanceAlerts(Company $company, PrepaidBalance $balance, array $usageData): array
    {
        $results = ['alerts_created' => 0, 'notifications_sent' => 0];
        
        $config = $this->getAlertConfig($company, self::TYPE_LOW_BALANCE);
        if (!$config || !$config->is_enabled) {
            return $results;
        }

        $effectiveBalance = $balance->getEffectiveBalance();
        $threshold = $balance->low_balance_threshold;
        
        if ($threshold <= 0) {
            return $results;
        }

        $percentage = ($effectiveBalance / $threshold) * 100;
        $thresholds = $config->thresholds ?? self::DEFAULT_LOW_BALANCE_THRESHOLDS;
        
        // Find the most severe crossed threshold
        $crossedThreshold = null;
        foreach ($thresholds as $t) {
            if ($percentage <= $t) {
                $crossedThreshold = $t;
                break; // Take the first (most severe) crossed threshold
            }
        }

        if ($crossedThreshold !== null) {
            // Check if we've already alerted for this threshold recently
            $recentAlert = BillingAlert::where('company_id', $company->id)
                ->where('alert_type', self::TYPE_LOW_BALANCE)
                ->where('threshold_value', $crossedThreshold)
                ->where('created_at', '>', now()->subHours(24))
                ->first();

            if (!$recentAlert) {
                $alert = $this->createLowBalanceAlert($company, $balance, $crossedThreshold, $percentage, $config);
                
                if ($alert) {
                    $results['alerts_created']++;
                    
                    if ($this->sendAlertNotification($alert)) {
                        $results['notifications_sent']++;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Check zero balance alerts
     */
    protected function checkZeroBalanceAlerts(Company $company, PrepaidBalance $balance, array $usageData): array
    {
        $results = ['alerts_created' => 0, 'notifications_sent' => 0];
        
        $effectiveBalance = $balance->getEffectiveBalance();
        
        if ($effectiveBalance <= 0) {
            // Check if we've already alerted for zero balance in the last 4 hours
            $recentAlert = BillingAlert::where('company_id', $company->id)
                ->where('alert_type', self::TYPE_ZERO_BALANCE)
                ->where('created_at', '>', now()->subHours(4))
                ->first();

            if (!$recentAlert) {
                $alert = $this->createZeroBalanceAlert($company, $balance);
                
                if ($alert) {
                    $results['alerts_created']++;
                    
                    if ($this->sendAlertNotification($alert)) {
                        $results['notifications_sent']++;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Check usage spike alerts
     */
    protected function checkUsageSpikeAlerts(Company $company, PrepaidBalance $balance, array $usageData): array
    {
        $results = ['alerts_created' => 0, 'notifications_sent' => 0];
        
        $config = $this->getAlertConfig($company, self::TYPE_USAGE_SPIKE);
        if (!$config || !$config->is_enabled) {
            return $results;
        }

        $currentHourUsage = $usageData['current_hour_cost'] ?? 0;
        $averageHourlyUsage = $usageData['average_hourly_cost'] ?? 0;
        
        if ($averageHourlyUsage > 0 && $currentHourUsage > 0) {
            $spikeMultiplier = $currentHourUsage / $averageHourlyUsage;
            $threshold = ($config->amount_threshold ?? self::DEFAULT_USAGE_SPIKE_THRESHOLD) / 100;
            
            if ($spikeMultiplier >= $threshold) {
                // Check if we've already alerted in the last 2 hours
                $recentAlert = BillingAlert::where('company_id', $company->id)
                    ->where('alert_type', self::TYPE_USAGE_SPIKE)
                    ->where('created_at', '>', now()->subHours(2))
                    ->first();

                if (!$recentAlert) {
                    $alert = $this->createUsageSpikeAlert($company, $currentHourUsage, $averageHourlyUsage, $spikeMultiplier, $config);
                    
                    if ($alert) {
                        $results['alerts_created']++;
                        
                        if ($this->sendAlertNotification($alert)) {
                            $results['notifications_sent']++;
                        }
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Check budget exceeded alerts
     */
    protected function checkBudgetExceededAlerts(Company $company, PrepaidBalance $balance, array $usageData): array
    {
        $results = ['alerts_created' => 0, 'notifications_sent' => 0];
        
        $config = $this->getAlertConfig($company, self::TYPE_BUDGET_EXCEEDED);
        if (!$config || !$config->is_enabled) {
            return $results;
        }

        $monthlySpend = $usageData['monthly_spend'] ?? 0;
        $monthlyBudget = $company->monthly_budget ?? null;
        
        if (!$monthlyBudget || $monthlyBudget <= 0) {
            return $results;
        }

        $budgetPercentage = ($monthlySpend / $monthlyBudget) * 100;
        $thresholds = $config->thresholds ?? [80, 90, 100, 110];
        
        foreach ($thresholds as $threshold) {
            if ($budgetPercentage >= $threshold) {
                // Check if we've already alerted for this threshold this month
                $recentAlert = BillingAlert::where('company_id', $company->id)
                    ->where('alert_type', self::TYPE_BUDGET_EXCEEDED)
                    ->where('threshold_value', $threshold)
                    ->where('created_at', '>', now()->startOfMonth())
                    ->first();

                if (!$recentAlert) {
                    $alert = $this->createBudgetExceededAlert($company, $monthlySpend, $monthlyBudget, $threshold, $budgetPercentage, $config);
                    
                    if ($alert) {
                        $results['alerts_created']++;
                        
                        if ($this->sendAlertNotification($alert)) {
                            $results['notifications_sent']++;
                        }
                    }
                }
                break; // Only alert for the highest threshold
            }
        }

        return $results;
    }

    /**
     * Check cost anomaly alerts
     */
    protected function checkCostAnomalyAlerts(Company $company, PrepaidBalance $balance, array $usageData): array
    {
        $results = ['alerts_created' => 0, 'notifications_sent' => 0];
        
        $config = $this->getAlertConfig($company, self::TYPE_COST_ANOMALY);
        if (!$config || !$config->is_enabled) {
            return $results;
        }

        $currentDayCost = $usageData['current_day_cost'] ?? 0;
        $averageDailyCost = $usageData['average_daily_cost'] ?? 0;
        
        if ($averageDailyCost > 0 && $currentDayCost > 0) {
            $anomalyMultiplier = $currentDayCost / $averageDailyCost;
            $threshold = $config->amount_threshold ?? self::DEFAULT_ANOMALY_MULTIPLIER;
            
            if ($anomalyMultiplier >= $threshold) {
                // Check if we've already alerted today
                $recentAlert = BillingAlert::where('company_id', $company->id)
                    ->where('alert_type', self::TYPE_COST_ANOMALY)
                    ->whereDate('created_at', today())
                    ->first();

                if (!$recentAlert) {
                    $alert = $this->createCostAnomalyAlert($company, $currentDayCost, $averageDailyCost, $anomalyMultiplier, $config);
                    
                    if ($alert) {
                        $results['alerts_created']++;
                        
                        if ($this->sendAlertNotification($alert)) {
                            $results['notifications_sent']++;
                        }
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Create low balance alert
     */
    protected function createLowBalanceAlert(Company $company, PrepaidBalance $balance, float $threshold, float $percentage, BillingAlertConfig $config): ?BillingAlert
    {
        $effectiveBalance = $balance->getEffectiveBalance();
        
        $severity = match (true) {
            $threshold <= 5 => BillingAlert::SEVERITY_CRITICAL,
            $threshold <= 10 => BillingAlert::SEVERITY_WARNING,
            default => BillingAlert::SEVERITY_INFO
        };

        $title = match ($severity) {
            BillingAlert::SEVERITY_CRITICAL => 'Critical: Account balance extremely low',
            BillingAlert::SEVERITY_WARNING => 'Warning: Account balance running low',
            default => 'Info: Low balance notification'
        };

        $message = "Your account balance is at {$threshold}% of the minimum threshold. " .
                  "Current balance: €" . number_format($effectiveBalance, 2) . ". " .
                  "Please top up your account to ensure uninterrupted service.";

        return BillingAlert::create([
            'company_id' => $company->id,
            'config_id' => $config->id,
            'alert_type' => self::TYPE_LOW_BALANCE,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'data' => [
                'balance' => $effectiveBalance,
                'threshold' => $balance->low_balance_threshold,
                'percentage' => round($percentage, 2),
                'recommended_topup' => max(50, $balance->low_balance_threshold - $effectiveBalance)
            ],
            'threshold_value' => $threshold,
            'current_value' => $percentage,
            'status' => BillingAlert::STATUS_PENDING,
        ]);
    }

    /**
     * Create zero balance alert
     */
    protected function createZeroBalanceAlert(Company $company, PrepaidBalance $balance): ?BillingAlert
    {
        return BillingAlert::create([
            'company_id' => $company->id,
            'alert_type' => self::TYPE_ZERO_BALANCE,
            'severity' => BillingAlert::SEVERITY_CRITICAL,
            'title' => 'URGENT: Account balance is zero',
            'message' => 'Your account balance has reached zero. All services may be interrupted. Please top up immediately.',
            'data' => [
                'balance' => $balance->getEffectiveBalance(),
                'last_transaction' => $balance->transactions()->latest()->first()?->created_at,
                'urgent_topup_required' => true
            ],
            'threshold_value' => 0,
            'current_value' => $balance->getEffectiveBalance(),
            'status' => BillingAlert::STATUS_PENDING,
        ]);
    }

    /**
     * Create usage spike alert
     */
    protected function createUsageSpikeAlert(Company $company, float $currentUsage, float $averageUsage, float $multiplier, BillingAlertConfig $config): ?BillingAlert
    {
        $percentageIncrease = round(($multiplier - 1) * 100, 1);
        
        return BillingAlert::create([
            'company_id' => $company->id,
            'config_id' => $config->id,
            'alert_type' => self::TYPE_USAGE_SPIKE,
            'severity' => $multiplier >= 5 ? BillingAlert::SEVERITY_CRITICAL : BillingAlert::SEVERITY_WARNING,
            'title' => 'Usage Spike Detected',
            'message' => "Unusual usage spike detected. Current hourly cost (€" . number_format($currentUsage, 2) . 
                        ") is {$percentageIncrease}% higher than average (€" . number_format($averageUsage, 2) . ").",
            'data' => [
                'current_hourly_cost' => $currentUsage,
                'average_hourly_cost' => $averageUsage,
                'spike_multiplier' => round($multiplier, 2),
                'percentage_increase' => $percentageIncrease,
                'detection_time' => now()->toISOString()
            ],
            'threshold_value' => $averageUsage,
            'current_value' => $currentUsage,
            'status' => BillingAlert::STATUS_PENDING,
        ]);
    }

    /**
     * Create budget exceeded alert
     */
    protected function createBudgetExceededAlert(Company $company, float $monthlySpend, float $monthlyBudget, float $threshold, float $percentage, BillingAlertConfig $config): ?BillingAlert
    {
        $severity = match (true) {
            $threshold >= 110 => BillingAlert::SEVERITY_CRITICAL,
            $threshold >= 100 => BillingAlert::SEVERITY_WARNING,
            default => BillingAlert::SEVERITY_INFO
        };

        $title = match (true) {
            $threshold >= 100 => 'Budget Exceeded',
            $threshold >= 90 => 'Near Budget Limit',
            default => 'Budget Alert'
        };

        $message = "Monthly spending has reached {$threshold}% of budget. " .
                  "Current spend: €" . number_format($monthlySpend, 2) . " of €" . number_format($monthlyBudget, 2) . " budget.";

        return BillingAlert::create([
            'company_id' => $company->id,
            'config_id' => $config->id,
            'alert_type' => self::TYPE_BUDGET_EXCEEDED,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'data' => [
                'monthly_spend' => $monthlySpend,
                'monthly_budget' => $monthlyBudget,
                'percentage' => round($percentage, 2),
                'overage' => max(0, $monthlySpend - $monthlyBudget),
                'days_remaining' => now()->endOfMonth()->diffInDays(now())
            ],
            'threshold_value' => $threshold,
            'current_value' => $percentage,
            'status' => BillingAlert::STATUS_PENDING,
        ]);
    }

    /**
     * Create cost anomaly alert
     */
    protected function createCostAnomalyAlert(Company $company, float $currentCost, float $averageCost, float $multiplier, BillingAlertConfig $config): ?BillingAlert
    {
        return BillingAlert::create([
            'company_id' => $company->id,
            'config_id' => $config->id,
            'alert_type' => self::TYPE_COST_ANOMALY,
            'severity' => $multiplier >= 5 ? BillingAlert::SEVERITY_CRITICAL : BillingAlert::SEVERITY_WARNING,
            'title' => 'Cost Anomaly Detected',
            'message' => "Unusual cost pattern detected. Today's cost (€" . number_format($currentCost, 2) . 
                        ") is " . round($multiplier, 1) . "x higher than the daily average (€" . number_format($averageCost, 2) . ").",
            'data' => [
                'current_daily_cost' => $currentCost,
                'average_daily_cost' => $averageCost,
                'anomaly_multiplier' => round($multiplier, 2),
                'detection_date' => now()->format('Y-m-d'),
                'potential_causes' => [
                    'high_call_volume',
                    'longer_call_durations',
                    'premium_features_usage'
                ]
            ],
            'threshold_value' => $averageCost,
            'current_value' => $currentCost,
            'status' => BillingAlert::STATUS_PENDING,
        ]);
    }

    /**
     * Send alert notification
     */
    protected function sendAlertNotification(BillingAlert $alert): bool
    {
        try {
            $config = $alert->config;
            if (!$config) {
                Log::warning('Alert config not found', ['alert_id' => $alert->id]);
                return false;
            }

            $channels = $config->notification_channels ?? ['email'];
            $recipients = $config->getRecipientEmails();
            
            if (empty($recipients)) {
                Log::warning('No recipients found for alert', ['alert_id' => $alert->id]);
                $alert->markAsFailed(['error' => 'No recipients configured']);
                return false;
            }

            $channelResults = [];
            
            // Send email notifications
            if (in_array('email', $channels)) {
                foreach ($recipients as $email) {
                    try {
                        Mail::to($email)->send(new \App\Mail\CostTrackingAlert($alert));
                        $channelResults['email'][] = ['recipient' => $email, 'status' => 'sent'];
                    } catch (\Exception $e) {
                        $channelResults['email'][] = ['recipient' => $email, 'status' => 'failed', 'error' => $e->getMessage()];
                        Log::error('Failed to send cost alert email', [
                            'alert_id' => $alert->id,
                            'recipient' => $email,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            $alert->markAsSent($channels, $channelResults);
            
            Log::info('Cost alert notification sent', [
                'alert_id' => $alert->id,
                'type' => $alert->alert_type,
                'severity' => $alert->severity,
                'recipients_count' => count($recipients)
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send cost alert notification', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $alert->markAsFailed(['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get alert configuration for company and type
     */
    protected function getAlertConfig(Company $company, string $alertType): ?BillingAlertConfig
    {
        return $company->billingAlertConfigs()
            ->where('alert_type', $alertType)
            ->where('is_enabled', true)
            ->first();
    }

    /**
     * Get cached usage data for performance
     */
    public function getCachedUsageData(Company $company): array
    {
        $cacheKey = self::CACHE_PREFIX . "usage_data:{$company->id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($company) {
            return $this->calculateUsageData($company);
        });
    }

    /**
     * Calculate usage data for a company
     */
    protected function calculateUsageData(Company $company): array
    {
        try {
            $now = now();
            
            // Current hour cost
            $currentHourCost = Call::where('company_id', $company->id)
                ->where('created_at', '>=', $now->startOfHour())
                ->where('created_at', '<', $now->copy()->addHour())
                ->sum('cost') ?? 0;

            // Average hourly cost (last 7 days, same hour)
            $averageHourlyCost = Call::where('company_id', $company->id)
                ->where('created_at', '>=', $now->copy()->subDays(7)->startOfHour())
                ->where('created_at', '<', $now->startOfHour())
                ->groupBy(DB::raw('HOUR(created_at)'))
                ->havingRaw('HOUR(created_at) = ?', [$now->hour])
                ->avg('cost') ?? 0;

            // Current day cost
            $currentDayCost = Call::where('company_id', $company->id)
                ->whereDate('created_at', $now->toDateString())
                ->sum('cost') ?? 0;

            // Average daily cost (last 30 days)
            $averageDailyCost = Call::where('company_id', $company->id)
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->where('created_at', '<', $now->startOfDay())
                ->groupBy(DB::raw('DATE(created_at)'))
                ->select(DB::raw('SUM(cost) as daily_cost'))
                ->get()
                ->avg('daily_cost') ?? 0;

            // Monthly spend (current month)
            $monthlySpend = Call::where('company_id', $company->id)
                ->where('created_at', '>=', $now->startOfMonth())
                ->sum('cost') ?? 0;

            return [
                'current_hour_cost' => (float) $currentHourCost,
                'average_hourly_cost' => (float) $averageHourlyCost,
                'current_day_cost' => (float) $currentDayCost,
                'average_daily_cost' => (float) $averageDailyCost,
                'monthly_spend' => (float) $monthlySpend,
                'calculated_at' => $now->toISOString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to calculate usage data', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'current_hour_cost' => 0,
                'average_hourly_cost' => 0,
                'current_day_cost' => 0,
                'average_daily_cost' => 0,
                'monthly_spend' => 0,
                'calculated_at' => now()->toISOString(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Clear cache for a company's usage data
     */
    public function clearUsageDataCache(Company $company): void
    {
        $cacheKey = self::CACHE_PREFIX . "usage_data:{$company->id}";
        Cache::forget($cacheKey);
    }

    /**
     * Get cost tracking dashboard data
     */
    public function getDashboardData(?Company $company = null): array
    {
        $query = BillingAlert::with(['company', 'config'])
            ->whereIn('alert_type', [
                self::TYPE_LOW_BALANCE,
                self::TYPE_USAGE_SPIKE,
                self::TYPE_BUDGET_EXCEEDED,
                self::TYPE_ZERO_BALANCE,
                self::TYPE_COST_ANOMALY
            ]);

        if ($company) {
            $query->where('company_id', $company->id);
        }

        $alerts = $query->latest()->limit(100)->get();

        return [
            'recent_alerts' => $alerts,
            'alert_summary' => [
                'total' => $alerts->count(),
                'critical' => $alerts->where('severity', BillingAlert::SEVERITY_CRITICAL)->count(),
                'warning' => $alerts->where('severity', BillingAlert::SEVERITY_WARNING)->count(),
                'info' => $alerts->where('severity', BillingAlert::SEVERITY_INFO)->count(),
                'pending' => $alerts->where('status', BillingAlert::STATUS_PENDING)->count(),
                'acknowledged' => $alerts->where('status', BillingAlert::STATUS_ACKNOWLEDGED)->count(),
            ],
            'alert_types' => [
                'low_balance' => $alerts->where('alert_type', self::TYPE_LOW_BALANCE)->count(),
                'zero_balance' => $alerts->where('alert_type', self::TYPE_ZERO_BALANCE)->count(),
                'usage_spike' => $alerts->where('alert_type', self::TYPE_USAGE_SPIKE)->count(),
                'budget_exceeded' => $alerts->where('alert_type', self::TYPE_BUDGET_EXCEEDED)->count(),
                'cost_anomaly' => $alerts->where('alert_type', self::TYPE_COST_ANOMALY)->count(),
            ]
        ];
    }
}