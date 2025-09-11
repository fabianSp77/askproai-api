<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Models\Transaction;
use Carbon\Carbon;

class MLServiceClient
{
    private CircuitBreaker $circuitBreaker;
    private RedisEventPublisher $eventPublisher;
    private string $baseUrl;
    private int $timeout = 5; // seconds
    private int $cacheMinutes = 60;
    
    public function __construct()
    {
        $this->baseUrl = config('services.ml.base_url', 'http://localhost:8001');
        $this->circuitBreaker = new CircuitBreaker('ml_service', 3, 60, 2);
        $this->eventPublisher = new RedisEventPublisher();
    }
    
    /**
     * Predict future usage for a tenant
     */
    public function predictUsage(Tenant $tenant, array $additionalFeatures = []): array
    {
        $cacheKey = "ml.usage.{$tenant->id}." . md5(json_encode($additionalFeatures));
        
        // Check cache first
        if ($cached = Cache::get($cacheKey)) {
            Log::debug("Returning cached usage prediction for tenant {$tenant->id}");
            return $cached;
        }
        
        // Prepare features
        $features = $this->prepareUsageFeatures($tenant, $additionalFeatures);
        
        // Make prediction with circuit breaker
        try {
            $result = $this->circuitBreaker->call(
                function () use ($tenant, $features) {
                    return $this->makePrediction('usage', $tenant->id, $features);
                },
                function () use ($tenant) {
                    // Fallback: Use simple heuristics
                    return $this->fallbackUsagePrediction($tenant);
                }
            );
            
            // Cache successful result
            if ($result['status'] === 'success') {
                Cache::put($cacheKey, $result, now()->addMinutes($this->cacheMinutes));
            }
            
            // Publish event for monitoring
            $this->eventPublisher->publishPredictionRequest(
                $tenant->id,
                'usage',
                $features
            );
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error("ML usage prediction failed for tenant {$tenant->id}", [
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackUsagePrediction($tenant);
        }
    }
    
    /**
     * Detect fraud for a transaction
     */
    public function detectFraud(Transaction $transaction): array
    {
        // Prepare features
        $features = $this->prepareFraudFeatures($transaction);
        
        // Make prediction with circuit breaker
        try {
            $result = $this->circuitBreaker->call(
                function () use ($transaction, $features) {
                    return $this->makePrediction('fraud', $transaction->tenant_id, $features);
                },
                function () use ($transaction) {
                    // Fallback: Use rule-based detection
                    return $this->fallbackFraudDetection($transaction);
                }
            );
            
            // If high risk, trigger alert
            if ($result['risk_score'] ?? 0 > 0.7) {
                $this->eventPublisher->publishAlert('high', 'fraud_detected', [
                    'tenant_id' => $transaction->tenant_id,
                    'transaction_id' => $transaction->id,
                    'risk_score' => $result['risk_score'],
                    'amount' => $transaction->amount_cents,
                    'requires_action' => true
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error("ML fraud detection failed for transaction {$transaction->id}", [
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackFraudDetection($transaction);
        }
    }
    
    /**
     * Predict customer churn probability
     */
    public function predictChurn(Tenant $tenant): array
    {
        $cacheKey = "ml.churn.{$tenant->id}";
        
        // Check cache
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }
        
        // Prepare features
        $features = $this->prepareChurnFeatures($tenant);
        
        // Make prediction
        try {
            $result = $this->circuitBreaker->call(
                function () use ($tenant, $features) {
                    return $this->makePrediction('churn', $tenant->id, $features);
                },
                function () use ($tenant) {
                    return $this->fallbackChurnPrediction($tenant);
                }
            );
            
            // Cache result
            Cache::put($cacheKey, $result, now()->addMinutes($this->cacheMinutes * 4)); // Cache longer
            
            // If high churn risk, trigger retention workflow
            if ($result['churn_probability'] ?? 0 > 0.7) {
                $this->triggerRetentionWorkflow($tenant, $result);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error("ML churn prediction failed for tenant {$tenant->id}", [
                'error' => $e->getMessage()
            ]);
            
            return $this->fallbackChurnPrediction($tenant);
        }
    }
    
    /**
     * Make actual API call to ML service
     */
    private function makePrediction(string $type, string $tenantId, array $features): array
    {
        $response = Http::timeout($this->timeout)
            ->post("{$this->baseUrl}/predict", [
                'tenant_id' => $tenantId,
                'prediction_type' => $type,
                'features' => $features,
                'async_mode' => false // Synchronous for immediate results
            ]);
        
        if (!$response->successful()) {
            throw new \Exception("ML service returned error: " . $response->status());
        }
        
        $data = $response->json();
        
        // Wait for async result if needed
        if ($data['status'] === 'queued' || $data['status'] === 'processing') {
            return $this->waitForResult($data['job_id']);
        }
        
        return [
            'status' => 'success',
            'prediction' => $data['prediction'] ?? [],
            'confidence' => $data['confidence'] ?? 0,
            'model_version' => $data['model_version'] ?? 'unknown'
        ];
    }
    
    /**
     * Wait for async prediction result
     */
    private function waitForResult(string $jobId, int $maxWait = 10): array
    {
        $startTime = time();
        
        while (time() - $startTime < $maxWait) {
            sleep(1);
            
            $response = Http::timeout($this->timeout)
                ->get("{$this->baseUrl}/predict/{$jobId}");
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['status'] === 'completed') {
                    return [
                        'status' => 'success',
                        'prediction' => $data['prediction'] ?? [],
                        'confidence' => $data['confidence'] ?? 0,
                        'model_version' => $data['model_version'] ?? 'unknown'
                    ];
                }
            }
        }
        
        throw new \Exception("Prediction timeout for job {$jobId}");
    }
    
    /**
     * Prepare features for usage prediction
     */
    private function prepareUsageFeatures(Tenant $tenant, array $additional = []): array
    {
        $now = Carbon::now();
        
        // Get historical usage
        $usageHistory = Cache::remember(
            "usage.history.{$tenant->id}",
            60,
            function () use ($tenant) {
                return $tenant->transactions()
                    ->where('type', 'usage')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->selectRaw('DATE(created_at) as date, SUM(ABS(amount_cents)) as usage')
                    ->groupBy('date')
                    ->pluck('usage', 'date')
                    ->toArray();
            }
        );
        
        // Calculate features
        $features = [
            'hour' => $now->hour,
            'day_of_week' => $now->dayOfWeek,
            'day_of_month' => $now->day,
            'is_weekend' => $now->isWeekend() ? 1 : 0,
            'is_business_hours' => $now->hour >= 9 && $now->hour <= 17 ? 1 : 0,
            'usage_lag_1d' => $usageHistory[$now->subDay()->toDateString()] ?? 0,
            'usage_lag_7d' => $usageHistory[$now->subDays(7)->toDateString()] ?? 0,
            'usage_mean_7d' => count($usageHistory) > 0 ? array_sum(array_slice($usageHistory, -7)) / min(7, count($usageHistory)) : 0,
            'usage_mean_30d' => count($usageHistory) > 0 ? array_sum($usageHistory) / count($usageHistory) : 0,
        ];
        
        return array_merge($features, $additional);
    }
    
    /**
     * Prepare features for fraud detection
     */
    private function prepareFraudFeatures(Transaction $transaction): array
    {
        $tenant = $transaction->tenant;
        
        // Get transaction patterns
        $recentTransactions = $tenant->transactions()
            ->where('created_at', '>=', now()->subDays(7))
            ->get();
        
        $avgAmount = $recentTransactions->avg('amount_cents') ?? 0;
        $maxAmount = $recentTransactions->max('amount_cents') ?? 0;
        $transactionCount = $recentTransactions->count();
        
        return [
            'amount' => $transaction->amount_cents,
            'amount_ratio' => $avgAmount > 0 ? $transaction->amount_cents / $avgAmount : 1,
            'is_large_amount' => $transaction->amount_cents > $maxAmount ? 1 : 0,
            'time' => $transaction->created_at->hour,
            'day_of_week' => $transaction->created_at->dayOfWeek,
            'transactions_last_hour' => $recentTransactions->where('created_at', '>=', now()->subHour())->count(),
            'transactions_last_day' => $recentTransactions->where('created_at', '>=', now()->subDay())->count(),
            'payment_method_changes' => 0, // Would check payment method history
            'location_change' => 0, // Would check IP/location changes
        ];
    }
    
    /**
     * Prepare features for churn prediction
     */
    private function prepareChurnFeatures(Tenant $tenant): array
    {
        // Calculate activity metrics
        $lastTransaction = $tenant->transactions()->latest()->first();
        $daysInactive = $lastTransaction 
            ? now()->diffInDays($lastTransaction->created_at) 
            : 999;
        
        $monthlyUsage = $tenant->transactions()
            ->where('type', 'usage')
            ->where('created_at', '>=', now()->subMonth())
            ->sum('amount_cents');
        
        $paymentFailures = $tenant->transactions()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subMonths(3))
            ->count();
        
        $supportTickets = 0; // Would integrate with support system
        
        return [
            'days_since_last_use' => $daysInactive,
            'usage_trend' => $this->calculateUsageTrend($tenant),
            'payment_failures' => $paymentFailures,
            'account_age_days' => now()->diffInDays($tenant->created_at),
            'balance_cents' => $tenant->balance_cents,
            'auto_topup_enabled' => $tenant->settings['auto_topup_enabled'] ?? false ? 1 : 0,
            'monthly_usage' => $monthlyUsage,
            'support_tickets' => $supportTickets,
        ];
    }
    
    /**
     * Calculate usage trend (-1 to 1)
     */
    private function calculateUsageTrend(Tenant $tenant): float
    {
        $thisMonth = $tenant->transactions()
            ->where('type', 'usage')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount_cents');
        
        $lastMonth = $tenant->transactions()
            ->where('type', 'usage')
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])
            ->sum('amount_cents');
        
        if ($lastMonth == 0) {
            return 0;
        }
        
        return ($thisMonth - $lastMonth) / $lastMonth;
    }
    
    /**
     * Fallback usage prediction using simple heuristics
     */
    private function fallbackUsagePrediction(Tenant $tenant): array
    {
        // Simple average-based prediction
        $avgDaily = $tenant->transactions()
            ->where('type', 'usage')
            ->where('created_at', '>=', now()->subDays(7))
            ->avg('amount_cents') ?? 100;
        
        $hour = now()->hour;
        $multiplier = ($hour >= 9 && $hour <= 17) ? 1.5 : 0.7;
        
        return [
            'status' => 'fallback',
            'prediction' => [
                'usage_next_hour' => round($avgDaily * $multiplier / 24, 2),
                'usage_next_day' => round($avgDaily * $multiplier, 2),
                'usage_next_week' => round($avgDaily * 7, 2)
            ],
            'confidence' => 0.6,
            'model_version' => 'heuristic_v1'
        ];
    }
    
    /**
     * Fallback fraud detection using rules
     */
    private function fallbackFraudDetection(Transaction $transaction): array
    {
        $riskScore = 0.1; // Base risk
        
        // Rule-based checks
        if ($transaction->amount_cents > 50000) { // Over 500€
            $riskScore += 0.3;
        }
        
        if ($transaction->amount_cents > 100000) { // Over 1000€
            $riskScore += 0.3;
        }
        
        $hour = $transaction->created_at->hour;
        if ($hour < 6 || $hour > 22) { // Unusual hours
            $riskScore += 0.2;
        }
        
        // Check velocity
        $recentCount = $transaction->tenant->transactions()
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        if ($recentCount > 10) {
            $riskScore += 0.3;
        }
        
        $riskScore = min($riskScore, 1);
        
        return [
            'status' => 'fallback',
            'risk_score' => $riskScore,
            'is_fraud' => $riskScore > 0.7,
            'risk_level' => $riskScore > 0.7 ? 'high' : ($riskScore > 0.4 ? 'medium' : 'low'),
            'confidence' => 0.7,
            'factors' => [
                'large_amount' => $transaction->amount_cents > 50000,
                'unusual_time' => $hour < 6 || $hour > 22,
                'high_velocity' => $recentCount > 10
            ]
        ];
    }
    
    /**
     * Fallback churn prediction
     */
    private function fallbackChurnPrediction(Tenant $tenant): array
    {
        $lastTransaction = $tenant->transactions()->latest()->first();
        $daysInactive = $lastTransaction 
            ? now()->diffInDays($lastTransaction->created_at) 
            : 999;
        
        $churnProbability = 0.1;
        
        if ($daysInactive > 60) {
            $churnProbability = 0.8;
        } elseif ($daysInactive > 30) {
            $churnProbability = 0.5;
        } elseif ($daysInactive > 14) {
            $churnProbability = 0.3;
        }
        
        return [
            'status' => 'fallback',
            'churn_probability' => $churnProbability,
            'will_churn' => $churnProbability > 0.5,
            'retention_score' => 1 - $churnProbability,
            'confidence' => 0.65,
            'recommendations' => $this->getRetentionRecommendations($churnProbability, $daysInactive)
        ];
    }
    
    /**
     * Get retention recommendations based on churn risk
     */
    private function getRetentionRecommendations(float $churnProbability, int $daysInactive): array
    {
        $recommendations = [];
        
        if ($churnProbability > 0.7) {
            $recommendations[] = 'Send win-back offer with 20% discount';
            $recommendations[] = 'Personal outreach from account manager';
        } elseif ($churnProbability > 0.5) {
            $recommendations[] = 'Send retention email with usage tips';
            $recommendations[] = 'Offer free consultation call';
        } elseif ($churnProbability > 0.3) {
            $recommendations[] = 'Send product update newsletter';
            $recommendations[] = 'Share success stories';
        }
        
        if ($daysInactive > 30) {
            $recommendations[] = 'Check for technical issues';
            $recommendations[] = 'Review recent support tickets';
        }
        
        return array_filter($recommendations);
    }
    
    /**
     * Trigger retention workflow for high-risk customers
     */
    private function triggerRetentionWorkflow(Tenant $tenant, array $prediction): void
    {
        Log::info("Triggering retention workflow for tenant {$tenant->id}", [
            'churn_probability' => $prediction['churn_probability'],
            'recommendations' => $prediction['recommendations'] ?? []
        ]);
        
        // Publish retention event
        $this->eventPublisher->publishAlert('medium', 'high_churn_risk', [
            'tenant_id' => $tenant->id,
            'title' => 'High Churn Risk Detected',
            'message' => "Tenant {$tenant->name} has {$prediction['churn_probability']}% churn probability",
            'metadata' => $prediction,
            'requires_action' => true,
            'auto_resolve' => false
        ]);
        
        // Could trigger automated campaigns, notifications, etc.
    }
    
    /**
     * Get ML service health status
     */
    public function getHealthStatus(): array
    {
        try {
            $response = Http::timeout(2)->get("{$this->baseUrl}/health");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return ['status' => 'unhealthy', 'error' => 'Failed to connect'];
            
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Trigger model retraining
     */
    public function triggerRetraining(string $modelType, array $parameters = []): array
    {
        try {
            $response = Http::timeout($this->timeout * 2)
                ->post("{$this->baseUrl}/train", array_merge([
                    'model_type' => $modelType,
                    'start_date' => now()->subMonths(3)->toDateString(),
                    'end_date' => now()->toDateString()
                ], $parameters));
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info("Model retraining triggered", [
                    'model_type' => $modelType,
                    'job_id' => $data['job_id'] ?? null
                ]);
                
                return $data;
            }
            
            throw new \Exception("Failed to trigger retraining: " . $response->status());
            
        } catch (\Exception $e) {
            Log::error("Model retraining failed", [
                'model_type' => $modelType,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}