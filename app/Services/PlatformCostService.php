<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Company;
use App\Models\PlatformCost;
use App\Models\MonthlyCostReport;
use App\Models\CurrencyExchangeRate;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PlatformCostService
{
    private ExchangeRateService $exchangeService;

    public function __construct()
    {
        $this->exchangeService = new ExchangeRateService();
    }

    /**
     * Track Retell.ai costs for a call
     */
    public function trackRetellCost(Call $call, float $costUsd): void
    {
        try {
            // Convert to EUR
            $costEurCents = $this->exchangeService->convertUsdCentsToEurCents((int)($costUsd * 100));

            // Create platform cost record
            PlatformCost::create([
                'company_id' => $call->company_id,
                'platform' => 'retell',
                'service_type' => 'api_call',
                'cost_type' => 'usage',
                'amount_cents' => $costEurCents,
                'currency' => 'EUR',
                'period_start' => $call->created_at,
                'period_end' => $call->updated_at,
                'usage_quantity' => $call->duration_sec / 60, // minutes
                'usage_unit' => 'minutes',
                'external_reference_id' => $call->retell_call_id,
                'metadata' => [
                    'call_id' => $call->id,
                    'duration_seconds' => $call->duration_sec,
                    'original_cost_usd' => $costUsd,
                    'exchange_rate' => CurrencyExchangeRate::getCurrentRate('USD', 'EUR')
                ]
            ]);

            // Update call with cost information
            $call->update([
                'retell_cost_usd' => $costUsd,
                'retell_cost_eur_cents' => $costEurCents
            ]);

            Log::info('Tracked Retell cost', [
                'call_id' => $call->id,
                'cost_usd' => $costUsd,
                'cost_eur_cents' => $costEurCents
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track Retell cost', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Track Twilio costs for a call
     */
    public function trackTwilioCost(Call $call, float $costUsd): void
    {
        try {
            // Convert to EUR
            $costEurCents = $this->exchangeService->convertUsdCentsToEurCents((int)($costUsd * 100));

            // Create platform cost record
            PlatformCost::create([
                'company_id' => $call->company_id,
                'platform' => 'twilio',
                'service_type' => 'telephony',
                'cost_type' => 'usage',
                'amount_cents' => $costEurCents,
                'currency' => 'EUR',
                'period_start' => $call->created_at,
                'period_end' => $call->updated_at,
                'usage_quantity' => $call->duration_sec / 60,
                'usage_unit' => 'minutes',
                'external_reference_id' => $call->twilio_sid ?? $call->retell_call_id,
                'metadata' => [
                    'call_id' => $call->id,
                    'duration_seconds' => $call->duration_sec,
                    'original_cost_usd' => $costUsd,
                    'exchange_rate' => CurrencyExchangeRate::getCurrentRate('USD', 'EUR'),
                    'phone_number' => $call->to_number
                ]
            ]);

            // Update call with cost information
            $call->update([
                'twilio_cost_usd' => $costUsd,
                'twilio_cost_eur_cents' => $costEurCents
            ]);

            Log::info('Tracked Twilio cost', [
                'call_id' => $call->id,
                'cost_usd' => $costUsd,
                'cost_eur_cents' => $costEurCents
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track Twilio cost', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Track Cal.com subscription cost
     */
    public function trackCalcomCost(Company $company, int $userCount, Carbon $periodStart, Carbon $periodEnd): void
    {
        try {
            // Cal.com is $15 USD per user per month
            $monthlyPerUserUsd = 15;
            $totalUsd = $userCount * $monthlyPerUserUsd;

            // Convert to EUR
            $totalEurCents = $this->exchangeService->convertUsdCentsToEurCents($totalUsd * 100);

            PlatformCost::create([
                'company_id' => $company->id,
                'platform' => 'calcom',
                'service_type' => 'subscription',
                'cost_type' => 'fixed',
                'amount_cents' => $totalEurCents,
                'currency' => 'EUR',
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'usage_quantity' => $userCount,
                'usage_unit' => 'users',
                'metadata' => [
                    'user_count' => $userCount,
                    'per_user_usd' => $monthlyPerUserUsd,
                    'total_usd' => $totalUsd,
                    'exchange_rate' => CurrencyExchangeRate::getCurrentRate('USD', 'EUR')
                ],
                'notes' => "Cal.com subscription for {$userCount} users"
            ]);

            Log::info('Tracked Cal.com cost', [
                'company_id' => $company->id,
                'user_count' => $userCount,
                'total_usd' => $totalUsd,
                'total_eur_cents' => $totalEurCents
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track Cal.com cost', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate and update total external costs for a call
     */
    public function calculateCallTotalCosts(Call $call): void
    {
        try {
            $exchangeRate = CurrencyExchangeRate::getCurrentRate('USD', 'EUR') ?? config('currency.fallback_rates.USD.EUR', 0.856);

            // Calculate total external costs (Retell + Twilio)
            $totalExternalCostEurCents =
                ($call->retell_cost_eur_cents ?? 0) +
                ($call->twilio_cost_eur_cents ?? 0);

            // Update call with totals
            // IMPORTANT: base_cost should equal total_external_cost_eur_cents (our cost basis)
            $call->update([
                'exchange_rate_used' => $exchangeRate,
                'total_external_cost_eur_cents' => $totalExternalCostEurCents,
                'base_cost' => $totalExternalCostEurCents  // Base cost = Total external costs
            ]);

            Log::info('Calculated total call costs', [
                'call_id' => $call->id,
                'retell_eur_cents' => $call->retell_cost_eur_cents ?? 0,
                'twilio_eur_cents' => $call->twilio_cost_eur_cents ?? 0,
                'total_eur_cents' => $totalExternalCostEurCents
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to calculate total call costs', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate monthly cost report for a company
     */
    public function generateMonthlyReport(Company $company, int $month, int $year): MonthlyCostReport
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get platform costs for the month
        $retellCosts = PlatformCost::where('company_id', $company->id)
            ->where('platform', 'retell')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->sum('amount_cents');

        $twilioCosts = PlatformCost::where('company_id', $company->id)
            ->where('platform', 'twilio')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->sum('amount_cents');

        $calcomCosts = PlatformCost::where('company_id', $company->id)
            ->where('platform', 'calcom')
            ->whereBetween('period_start', [$startDate, $endDate])
            ->sum('amount_cents');

        $otherCosts = PlatformCost::where('company_id', $company->id)
            ->whereNotIn('platform', ['retell', 'twilio', 'calcom'])
            ->whereBetween('period_start', [$startDate, $endDate])
            ->sum('amount_cents');

        // Get call statistics
        $calls = Call::where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $callCount = $calls->count();
        $totalSeconds = $calls->sum('duration_sec');
        $totalMinutes = $totalSeconds / 60;
        $avgDuration = $callCount > 0 ? $totalSeconds / $callCount : 0;

        // Calculate revenue (customer costs)
        $totalRevenue = $calls->sum('customer_cost');

        // Calculate total external costs
        $totalExternalCosts = $retellCosts + $twilioCosts + $calcomCosts + $otherCosts;

        // Calculate profit
        $grossProfit = $totalRevenue - $totalExternalCosts;
        $profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

        // Create cost breakdown
        $costBreakdown = [
            'retell' => [
                'amount_cents' => $retellCosts,
                'percentage' => $totalExternalCosts > 0 ? ($retellCosts / $totalExternalCosts) * 100 : 0
            ],
            'twilio' => [
                'amount_cents' => $twilioCosts,
                'percentage' => $totalExternalCosts > 0 ? ($twilioCosts / $totalExternalCosts) * 100 : 0
            ],
            'calcom' => [
                'amount_cents' => $calcomCosts,
                'percentage' => $totalExternalCosts > 0 ? ($calcomCosts / $totalExternalCosts) * 100 : 0
            ],
            'other' => [
                'amount_cents' => $otherCosts,
                'percentage' => $totalExternalCosts > 0 ? ($otherCosts / $totalExternalCosts) * 100 : 0
            ]
        ];

        // Create or update monthly report
        return MonthlyCostReport::updateOrCreate(
            [
                'company_id' => $company->id,
                'month' => $month,
                'year' => $year
            ],
            [
                'retell_cost_cents' => $retellCosts,
                'twilio_cost_cents' => $twilioCosts,
                'calcom_cost_cents' => $calcomCosts,
                'other_costs_cents' => $otherCosts,
                'total_external_costs_cents' => $totalExternalCosts,
                'total_revenue_cents' => $totalRevenue,
                'gross_profit_cents' => $grossProfit,
                'profit_margin' => $profitMargin,
                'call_count' => $callCount,
                'total_minutes' => $totalMinutes,
                'average_call_duration' => $avgDuration,
                'cost_breakdown' => $costBreakdown
            ]
        );
    }

    /**
     * Get cost summary for a company
     */
    public function getCostSummary(Company $company, Carbon $startDate = null, Carbon $endDate = null): array
    {
        if (!$startDate) {
            $startDate = now()->startOfMonth();
        }
        if (!$endDate) {
            $endDate = now()->endOfMonth();
        }

        // Get costs by platform
        $costs = PlatformCost::where('company_id', $company->id)
            ->whereBetween('period_start', [$startDate, $endDate])
            ->selectRaw('platform, SUM(amount_cents) as total_cents, COUNT(*) as count')
            ->groupBy('platform')
            ->get();

        // Get call statistics
        $calls = Call::where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalRevenue = $calls->sum('customer_cost');
        $totalExternalCosts = $costs->sum('total_cents');
        $grossProfit = $totalRevenue - $totalExternalCosts;

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'costs_by_platform' => $costs->mapWithKeys(function ($item) {
                return [$item->platform => [
                    'total_cents' => $item->total_cents,
                    'total_euros' => $item->total_cents / 100,
                    'count' => $item->count
                ]];
            })->toArray(),
            'totals' => [
                'external_costs_cents' => $totalExternalCosts,
                'external_costs_euros' => $totalExternalCosts / 100,
                'revenue_cents' => $totalRevenue,
                'revenue_euros' => $totalRevenue / 100,
                'gross_profit_cents' => $grossProfit,
                'gross_profit_euros' => $grossProfit / 100,
                'profit_margin' => $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0
            ],
            'call_stats' => [
                'total_calls' => $calls->count(),
                'total_minutes' => $calls->sum('duration_sec') / 60,
                'avg_duration_seconds' => $calls->avg('duration_sec')
            ]
        ];
    }

    /**
     * Track custom platform cost
     */
    public function trackCustomCost(array $data): PlatformCost
    {
        // Convert amount to EUR if needed
        if (($data['currency'] ?? 'EUR') !== 'EUR') {
            $amountEur = $this->exchangeService->convertToEur(
                $data['amount'] ?? 0,
                $data['currency'] ?? 'EUR'
            );
            $data['amount_cents'] = (int)($amountEur * 100);
            $data['currency'] = 'EUR';
        } else {
            $data['amount_cents'] = (int)(($data['amount'] ?? 0) * 100);
        }

        unset($data['amount']);

        return PlatformCost::create($data);
    }
}