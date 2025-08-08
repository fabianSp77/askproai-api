<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Company;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use App\Events\BillingThresholdExceeded;
use App\Events\HighVolumeUsageDetected;

/**
 * Enhanced Hair Salon Billing Service
 * 
 * Production-grade billing with:
 * - Real-time usage tracking
 * - Tiered pricing models
 * - Automated billing alerts
 * - Reseller margin calculations
 * - Cost optimization insights
 * - Fraud detection
 */
class EnhancedHairSalonBillingService
{
    // Base pricing constants
    const COST_PER_MINUTE = 0.30;
    const SETUP_FEE = 199.00;
    const MONTHLY_FEE = 49.00;
    
    // Volume discount tiers (monthly minutes)
    const VOLUME_TIERS = [
        'starter' => ['max_minutes' => 100, 'discount' => 0],
        'professional' => ['max_minutes' => 500, 'discount' => 0.10],
        'enterprise' => ['max_minutes' => 1000, 'discount' => 0.15],
        'unlimited' => ['max_minutes' => PHP_INT_MAX, 'discount' => 0.20]
    ];
    
    // Reseller margins by tier
    const RESELLER_MARGINS = [
        'standard' => ['calls' => 0.05, 'setup' => 50.00, 'monthly' => 10.00],
        'premium' => ['calls' => 0.08, 'setup' => 75.00, 'monthly' => 15.00],
        'enterprise' => ['calls' => 0.10, 'setup' => 100.00, 'monthly' => 20.00]
    ];
    
    // Alert thresholds
    const DAILY_USAGE_ALERT = 50.00;  // €50 per day
    const MONTHLY_USAGE_ALERT = 500.00; // €500 per month
    const SUSPICIOUS_USAGE_THRESHOLD = 10; // 10 calls in 5 minutes
    
    protected Company $company;
    protected string $resellerTier;
    
    public function __construct(Company $company = null)
    {
        if ($company) {
            $this->company = $company;
            $this->resellerTier = $this->determineResellerTier($company);
        }
    }
    
    /**
     * Calculate advanced call cost with volume discounts
     */
    public function calculateAdvancedCallCost(int $durationSeconds, array $options = []): array
    {
        $durationMinutes = ceil($durationSeconds / 60);
        $monthlyUsage = $this->getMonthlyUsageMinutes();
        
        // Determine pricing tier
        $tier = $this->determineVolumeTier($monthlyUsage + $durationMinutes);
        $discount = self::VOLUME_TIERS[$tier]['discount'];
        
        // Calculate base cost with discount
        $baseCostPerMinute = self::COST_PER_MINUTE * (1 - $discount);
        $baseCost = $durationMinutes * $baseCostPerMinute;
        
        // Reseller margin calculation
        $resellerMarginRate = self::RESELLER_MARGINS[$this->resellerTier]['calls'];
        $resellerMargin = $durationMinutes * $resellerMarginRate;
        $netCost = $baseCost - $resellerMargin;
        
        // Additional fees
        $additionalFees = $this->calculateAdditionalFees($options);
        
        return [
            'duration_seconds' => $durationSeconds,
            'duration_minutes' => $durationMinutes,
            'pricing_tier' => $tier,
            'base_rate' => self::COST_PER_MINUTE,
            'discounted_rate' => $baseCostPerMinute,
            'discount_percentage' => $discount * 100,
            'base_cost' => round($baseCost, 2),
            'additional_fees' => round($additionalFees, 2),
            'subtotal' => round($baseCost + $additionalFees, 2),
            'reseller_margin' => round($resellerMargin, 2),
            'net_cost' => round($netCost + $additionalFees, 2),
            'calculated_at' => now()->toIso8601String()
        ];
    }
    
    /**
     * Track call usage with real-time monitoring
     */
    public function trackAdvancedCallUsage(Call $call, array $metadata = []): array
    {
        try {
            DB::beginTransaction();
            
            $billing = $this->calculateAdvancedCallCost($call->duration_seconds ?? 0, $metadata);
            
            // Store detailed billing information
            $call->update([
                'metadata' => array_merge($call->metadata ?? [], [
                    'billing' => $billing,
                    'billed_at' => now()->toIso8601String(),
                    'billing_status' => 'calculated',
                    'reseller_tier' => $this->resellerTier
                ])
            ]);
            
            // Update real-time usage statistics
            $this->updateRealTimeUsageStats($billing);
            
            // Check for usage alerts
            $this->checkUsageAlerts($billing);
            
            // Detect suspicious patterns
            $this->detectSuspiciousUsage($call);
            
            DB::commit();
            
            Log::info('Advanced call usage tracked', [
                'call_id' => $call->id,
                'company_id' => $this->company->id,
                'billing' => $billing,
                'tier' => $billing['pricing_tier']
            ]);
            
            return [
                'success' => true,
                'call_id' => $call->id,
                'billing' => $billing,
                'alerts_triggered' => $this->getTriggeredAlerts($billing)
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Enhanced billing tracking failed', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate comprehensive usage analytics
     */
    public function getUsageAnalytics(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();
        
        $cacheKey = "usage_analytics_{$this->company->id}_" . md5($startDate->toDateString() . $endDate->toDateString());
        
        return Cache::remember($cacheKey, 15, function () use ($startDate, $endDate) {
            $calls = Call::where('company_id', $this->company->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            $analytics = [
                'period' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                    'days' => $startDate->diffInDays($endDate)
                ],
                'totals' => $this->calculateTotals($calls),
                'daily_breakdown' => $this->getDailyBreakdown($calls, $startDate, $endDate),
                'tier_distribution' => $this->getTierDistribution($calls),
                'cost_optimization' => $this->getCostOptimizationInsights($calls),
                'performance_metrics' => $this->getPerformanceMetrics($calls),
                'projections' => $this->getUsageProjections($calls)
            ];
            
            return $analytics;
        });
    }
    
    /**
     * Calculate totals for analytics
     */
    protected function calculateTotals($calls): array
    {
        $totalCalls = $calls->count();
        $totalMinutes = 0;
        $totalCost = 0;
        $totalResellerMargin = 0;
        $successfulBookings = 0;
        $tierCounts = array_fill_keys(array_keys(self::VOLUME_TIERS), 0);
        
        foreach ($calls as $call) {
            $billing = $call->metadata['billing'] ?? null;
            if ($billing) {
                $totalMinutes += $billing['duration_minutes'];
                $totalCost += $billing['net_cost'];
                $totalResellerMargin += $billing['reseller_margin'];
                
                if (isset($billing['pricing_tier'])) {
                    $tierCounts[$billing['pricing_tier']]++;
                }
            }
            
            if (isset($call->metadata['booking_success'])) {
                $successfulBookings++;
            }
        }
        
        return [
            'calls' => $totalCalls,
            'minutes' => $totalMinutes,
            'cost' => round($totalCost, 2),
            'reseller_margin' => round($totalResellerMargin, 2),
            'successful_bookings' => $successfulBookings,
            'conversion_rate' => $totalCalls > 0 ? round(($successfulBookings / $totalCalls) * 100, 2) : 0,
            'tier_distribution' => $tierCounts,
            'average_cost_per_call' => $totalCalls > 0 ? round($totalCost / $totalCalls, 2) : 0,
            'average_duration' => $totalCalls > 0 ? round($totalMinutes / $totalCalls, 2) : 0
        ];
    }
    
    /**
     * Get cost optimization insights
     */
    protected function getCostOptimizationInsights($calls): array
    {
        $insights = [];
        $monthlyMinutes = $this->getMonthlyUsageMinutes();
        $currentTier = $this->determineVolumeTier($monthlyMinutes);
        
        // Volume tier optimization
        $nextTier = $this->getNextVolumeTier($currentTier);
        if ($nextTier) {
            $minutesToNextTier = self::VOLUME_TIERS[$nextTier]['max_minutes'] - $monthlyMinutes;
            $potentialSavings = $this->calculateTierUpgradeSavings($monthlyMinutes, $nextTier);
            
            if ($potentialSavings > 0) {
                $insights[] = [
                    'type' => 'volume_upgrade',
                    'message' => "Upgrade to {$nextTier} tier for {$potentialSavings}% savings",
                    'minutes_needed' => $minutesToNextTier,
                    'potential_savings' => $potentialSavings
                ];
            }
        }
        
        // Peak usage optimization
        $peakHours = $this->getPeakUsageHours($calls);
        if (count($peakHours) > 0) {
            $insights[] = [
                'type' => 'peak_distribution',
                'message' => 'Consider distributing calls outside peak hours for better staff utilization',
                'peak_hours' => $peakHours
            ];
        }
        
        // Conversion rate optimization
        $avgConversion = $this->getAverageConversionRate($calls);
        $industryBenchmark = 65; // 65% industry benchmark
        
        if ($avgConversion < $industryBenchmark) {
            $insights[] = [
                'type' => 'conversion_optimization',
                'message' => 'Conversion rate below industry benchmark',
                'current_rate' => $avgConversion,
                'benchmark' => $industryBenchmark,
                'improvement_potential' => $industryBenchmark - $avgConversion
            ];
        }
        
        return $insights;
    }
    
    /**
     * Real-time fraud detection
     */
    protected function detectSuspiciousUsage(Call $call): void
    {
        $recentCalls = Call::where('company_id', $this->company->id)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();
        
        if ($recentCalls >= self::SUSPICIOUS_USAGE_THRESHOLD) {
            Log::warning('Suspicious usage pattern detected', [
                'company_id' => $this->company->id,
                'recent_calls' => $recentCalls,
                'threshold' => self::SUSPICIOUS_USAGE_THRESHOLD
            ]);
            
            // Trigger security alert
            Event::dispatch(new HighVolumeUsageDetected($this->company, $recentCalls));
        }
        
        // Check for unusual call patterns
        $this->detectUnusualPatterns($call);
    }
    
    /**
     * Update real-time usage statistics
     */
    protected function updateRealTimeUsageStats(array $billing): void
    {
        $cacheKey = "realtime_usage_{$this->company->id}";
        $stats = Cache::get($cacheKey, [
            'daily_cost' => 0,
            'monthly_cost' => 0,
            'daily_calls' => 0,
            'monthly_calls' => 0,
            'last_updated' => now()
        ]);
        
        // Update daily stats
        if (now()->format('Y-m-d') !== Carbon::parse($stats['last_updated'])->format('Y-m-d')) {
            $stats['daily_cost'] = 0;
            $stats['daily_calls'] = 0;
        }
        
        // Update monthly stats
        if (now()->format('Y-m') !== Carbon::parse($stats['last_updated'])->format('Y-m')) {
            $stats['monthly_cost'] = 0;
            $stats['monthly_calls'] = 0;
        }
        
        $stats['daily_cost'] += $billing['net_cost'];
        $stats['monthly_cost'] += $billing['net_cost'];
        $stats['daily_calls'] += 1;
        $stats['monthly_calls'] += 1;
        $stats['last_updated'] = now();
        
        Cache::put($cacheKey, $stats, 3600);
    }
    
    /**
     * Check and trigger usage alerts
     */
    protected function checkUsageAlerts(array $billing): void
    {
        $stats = Cache::get("realtime_usage_{$this->company->id}", []);
        
        // Daily usage alert
        if (($stats['daily_cost'] ?? 0) >= self::DAILY_USAGE_ALERT) {
            Event::dispatch(new BillingThresholdExceeded(
                $this->company,
                'daily',
                $stats['daily_cost'],
                self::DAILY_USAGE_ALERT
            ));
        }
        
        // Monthly usage alert
        if (($stats['monthly_cost'] ?? 0) >= self::MONTHLY_USAGE_ALERT) {
            Event::dispatch(new BillingThresholdExceeded(
                $this->company,
                'monthly',
                $stats['monthly_cost'],
                self::MONTHLY_USAGE_ALERT
            ));
        }
    }
    
    /**
     * Helper methods for tier and margin calculations
     */
    protected function determineVolumeTier(int $minutes): string
    {
        foreach (self::VOLUME_TIERS as $tier => $config) {
            if ($minutes <= $config['max_minutes']) {
                return $tier;
            }
        }
        return 'unlimited';
    }
    
    protected function determineResellerTier(Company $company): string
    {
        $settings = $company->settings ?? [];
        return $settings['reseller_tier'] ?? 'standard';
    }
    
    protected function getMonthlyUsageMinutes(): int
    {
        return Cache::remember("monthly_minutes_{$this->company->id}", 15, function () {
            return DB::table('calls')
                ->where('company_id', $this->company->id)
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum(DB::raw('CEIL(duration_seconds / 60)')) ?: 0;
        });
    }
    
    protected function calculateAdditionalFees(array $options): float
    {
        $fees = 0;
        
        if ($options['priority_booking'] ?? false) {
            $fees += 1.00; // €1 priority booking fee
        }
        
        if ($options['multi_language'] ?? false) {
            $fees += 0.50; // €0.50 multi-language fee
        }
        
        return $fees;
    }
    
    protected function getTriggeredAlerts(array $billing): array
    {
        // This would return any alerts that were triggered during billing
        return [];
    }
    
    /**
     * Generate detailed monthly invoice with enhanced features
     */
    public function generateEnhancedMonthlyInvoice(Carbon $month = null): array
    {
        $month = $month ?? now();
        $analytics = $this->getUsageAnalytics($month->copy()->startOfMonth(), $month->copy()->endOfMonth());
        
        return [
            'invoice_type' => 'enhanced_monthly_usage',
            'period' => $month->format('F Y'),
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'reseller_tier' => $this->resellerTier,
            'usage_summary' => $analytics['totals'],
            'tier_breakdown' => $analytics['tier_distribution'],
            'cost_optimization' => $analytics['cost_optimization'],
            'billing_details' => [
                'base_charges' => $analytics['totals']['cost'],
                'monthly_fee' => self::MONTHLY_FEE,
                'total_charges' => $analytics['totals']['cost'] + self::MONTHLY_FEE,
                'reseller_margin' => $analytics['totals']['reseller_margin'] + self::RESELLER_MARGINS[$this->resellerTier]['monthly'],
                'net_amount' => ($analytics['totals']['cost'] + self::MONTHLY_FEE) - ($analytics['totals']['reseller_margin'] + self::RESELLER_MARGINS[$this->resellerTier]['monthly'])
            ],
            'performance_metrics' => $analytics['performance_metrics'] ?? [],
            'projections' => $analytics['projections'] ?? [],
            'generated_at' => now()->toIso8601String()
        ];
    }
}