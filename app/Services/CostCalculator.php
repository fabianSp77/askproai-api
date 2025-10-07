<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Company;
use App\Models\PricingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CostCalculator
{
    /**
     * Calculate all cost levels for a call
     */
    public function calculateCallCosts(Call $call): array
    {
        $costs = [
            'base_cost' => 0,
            'reseller_cost' => 0,
            'customer_cost' => 0,
            'platform_profit' => 0,
            'reseller_profit' => 0,
            'total_profit' => 0,
            'profit_margin_platform' => 0,
            'profit_margin_reseller' => 0,
            'profit_margin_total' => 0,
            'cost_breakdown' => [],
            'cost_calculation_method' => 'standard'
        ];

        try {
            // 1. Calculate base cost (our cost from Retell/Infrastructure)
            $costs['base_cost'] = $this->calculateBaseCost($call);

            // Enhanced cost breakdown with all components
            $costs['cost_breakdown']['base'] = [
                'retell_cost_eur_cents' => $call->retell_cost_eur_cents ?? 0,
                'twilio_cost_eur_cents' => $call->twilio_cost_eur_cents ?? 0,
                'llm_tokens' => $this->getTokenCost($call),
                'total_external' => $call->total_external_cost_eur_cents ?? 0,
                'exchange_rate' => $call->exchange_rate_used ?? 0.92,
                'calculation_method' => $call->total_external_cost_eur_cents > 0 ? 'actual' : 'estimated'
            ];

            // 2. Get the company and check if it's through a reseller
            $company = $call->company;
            if (!$company) {
                Log::warning('No company found for call', ['call_id' => $call->id]);
                return $costs;
            }

            // Ensure downstream calculations use the freshly computed base cost
            $call->base_cost = $costs['base_cost'];

            // ✅ FIX: Cost consistency validation (QUALITY AUDIT recommendation)
            if ($call->total_external_cost_eur_cents &&
                abs($call->total_external_cost_eur_cents - $costs['base_cost']) > 1) {
                Log::warning('Cost calculation mismatch detected', [
                    'call_id' => $call->id,
                    'total_external_cost_eur_cents' => $call->total_external_cost_eur_cents,
                    'calculated_base_cost' => $costs['base_cost'],
                    'difference_cents' => abs($call->total_external_cost_eur_cents - $costs['base_cost']),
                    'retell_cost' => $call->retell_cost_eur_cents,
                    'twilio_cost' => $call->twilio_cost_eur_cents,
                    'note' => 'Base cost should equal total_external_cost (within 1 cent rounding)'
                ]);
            }

            // 3. Check if company has a reseller/mandant
            if ($company->parent_company_id) {
                // This is a reseller customer
                $costs['cost_calculation_method'] = 'reseller';
                $resellerCompany = Company::find($company->parent_company_id);

                // Calculate reseller cost (what the reseller pays us)
                $costs['reseller_cost'] = $this->calculateResellerCost($call, $resellerCompany);
                $costs['cost_breakdown']['reseller'] = [
                    'markup_percentage' => $resellerCompany->reseller_markup ?? 20,
                    'base_cost' => $costs['base_cost'],
                    'reseller_cost' => $costs['reseller_cost']
                ];

                // Calculate customer cost (what end customer pays reseller)
                $costs['customer_cost'] = $this->calculateCustomerCost($call, $company, $resellerCompany);
                $costs['cost_breakdown']['customer'] = [
                    'pricing_plan' => $company->pricing_plan_id,
                    'call_duration' => $call->duration_sec,
                    'customer_cost' => $costs['customer_cost']
                ];
            } else {
                // Direct customer
                $costs['cost_calculation_method'] = 'direct';
                $costs['reseller_cost'] = 0;

                // Calculate customer cost based on their pricing plan
                $costs['customer_cost'] = $this->calculateDirectCustomerCost($call, $company);
                $costs['cost_breakdown']['customer'] = [
                    'pricing_plan' => $company->pricing_plan_id,
                    'call_duration' => $call->duration_sec,
                    'customer_cost' => $costs['customer_cost']
                ];
            }

            // 4. Apply any discounts or special rates
            $costs = $this->applyDiscounts($costs, $call, $company);

            // 5. Calculate profits
            $costs = $this->calculateProfits($costs);

        } catch (\Exception $e) {
            Log::error('Error calculating call costs', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
        }

        return $costs;
    }

    /**
     * Calculate base cost from Retell API and infrastructure
     *
     * PRIORITY: Use actual external costs tracked by PlatformCostService
     * FALLBACK: Calculate estimate if external costs not available
     *
     * NOTE: total_external_cost already includes LLM tokens if tracked by Retell
     */
    private function calculateBaseCost(Call $call): int
    {
        // 🔴 CRITICAL FIX: Use actual external costs first (includes ALL Retell components)
        if ($call->total_external_cost_eur_cents && $call->total_external_cost_eur_cents > 0) {
            Log::debug('Using actual external costs for call', [
                'call_id' => $call->id,
                'total_external_cost_eur_cents' => $call->total_external_cost_eur_cents,
                'note' => 'Includes Retell API + Twilio + LLM (if tracked)'
            ]);
            return $call->total_external_cost_eur_cents;
        }

        // 🟡 FALLBACK: Build from individual cost components if available
        $baseCost = 0;

        // Retell AI costs (if tracked separately)
        if ($call->retell_cost_eur_cents && $call->retell_cost_eur_cents > 0) {
            $baseCost += $call->retell_cost_eur_cents;
        } else {
            // Estimate: Retell base is ~$0.07/min = 7 cents/min = 0.1167 cents/sec
            $baseCost += (int)round($call->duration_sec * (7 / 60));
        }

        // Twilio telephony costs
        if ($call->twilio_cost_eur_cents && $call->twilio_cost_eur_cents > 0) {
            $baseCost += $call->twilio_cost_eur_cents;
        }

        // LLM token usage cost (only if NOT already in retell_cost)
        // NOTE: Retell typically includes LLM costs in their billing
        // Only add separately if we're using fallback estimation
        if (!$call->retell_cost_eur_cents && $call->llm_token_usage) {
            $tokenCost = $this->getTokenCost($call);
            $baseCost += $tokenCost;
        }

        // Infrastructure overhead (only if no external costs tracked)
        if ($baseCost === 0) {
            // Last resort estimate: 10 cents/min + 5 cents fixed
            $minuteRate = 10; // 10 cents per minute
            $costPerSecond = $minuteRate / 60;
            $baseCost = (int)round($call->duration_sec * $costPerSecond) + 5;
        }

        Log::debug('Using calculated/estimated costs for call', [
            'call_id' => $call->id,
            'base_cost' => $baseCost,
            'method' => 'fallback',
            'llm_added_separately' => !$call->retell_cost_eur_cents && $call->llm_token_usage
        ]);

        return $baseCost;
    }

    /**
     * Get Retell API cost
     */
    private function getRetellApiCost(Call $call): int
    {
        // Check if we have the actual Retell cost stored
        if ($call->retell_cost) {
            return (int)($call->retell_cost * 100);
        }

        // Otherwise calculate based on duration with second-based billing
        $minuteRate = 10; // 10 cents per minute
        $costPerSecond = $minuteRate / 60; // 0.1667 cents per second
        return (int)round($call->duration_sec * $costPerSecond);
    }

    /**
     * Get infrastructure cost
     */
    private function getInfrastructureCost(Call $call): int
    {
        // Fixed cost per call for our infrastructure
        return 5; // 5 cents
    }

    /**
     * Get token usage cost
     */
    private function getTokenCost(Call $call): int
    {
        if (!$call->llm_token_usage) {
            return 0;
        }

        $tokenData = is_string($call->llm_token_usage)
            ? json_decode($call->llm_token_usage, true)
            : $call->llm_token_usage;

        if (!$tokenData) {
            return 0;
        }

        // GPT-4 pricing: $0.03 per 1K input tokens, $0.06 per 1K output tokens
        $inputCost = ($tokenData['input_tokens'] ?? 0) * 0.03 / 1000 * 100; // Convert to cents
        $outputCost = ($tokenData['output_tokens'] ?? 0) * 0.06 / 1000 * 100; // Convert to cents

        return (int)($inputCost + $outputCost);
    }

    /**
     * Calculate reseller cost (what reseller pays us)
     */
    private function calculateResellerCost(Call $call, ?Company $resellerCompany): int
    {
        if (!$resellerCompany) {
            return $call->base_cost;
        }

        // Apply reseller markup (default 20%)
        $markupPercentage = $resellerCompany->reseller_markup ?? 20;
        $resellerCost = $call->base_cost * (1 + $markupPercentage / 100);

        return (int)$resellerCost;
    }

    /**
     * Calculate customer cost for reseller customers
     */
    private function calculateCustomerCost(Call $call, Company $company, ?Company $resellerCompany): int
    {
        // Get the customer's pricing plan
        $pricingPlan = $company->pricingPlan;

        if (!$pricingPlan) {
            // Use default pricing if no plan
            return $this->calculateDefaultCost($call);
        }

        // Calculate based on pricing plan
        $cost = 0;

        // Per-minute rate
        if ($pricingPlan->price_per_minute) {
            $minutes = ceil($call->duration_sec / 60);
            $cost += $pricingPlan->price_per_minute * $minutes * 100; // Convert to cents
        }

        // Per-call rate
        if ($pricingPlan->price_per_call) {
            $cost += $pricingPlan->price_per_call * 100; // Convert to cents
        }

        // Check if within included minutes
        if ($pricingPlan->included_minutes) {
            $usedMinutes = $this->getUsedMinutesThisMonth($company);
            if ($usedMinutes < $pricingPlan->included_minutes) {
                // Within included minutes, no additional cost
                $cost = 0;
            }
        }

        return (int)$cost;
    }

    /**
     * Calculate cost for direct customers
     */
    private function calculateDirectCustomerCost(Call $call, Company $company): int
    {
        // Get the customer's pricing plan
        $pricingPlan = $company->pricingPlan;

        if (!$pricingPlan) {
            // Use default pricing if no plan
            return $this->calculateDefaultCost($call);
        }

        // Same calculation as customer cost but without reseller markup
        return $this->calculateCustomerCost($call, $company, null);
    }

    /**
     * Calculate default cost when no pricing plan is set
     */
    private function calculateDefaultCost(Call $call): int
    {
        // Default: 15 cents per minute
        $minutes = ceil($call->duration_sec / 60);
        return $minutes * 15;
    }

    /**
     * Get used minutes for this month
     */
    private function getUsedMinutesThisMonth(Company $company): int
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalSeconds = Call::where('company_id', $company->id)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('duration_sec');

        return (int)ceil($totalSeconds / 60);
    }

    /**
     * Apply any discounts or special rates
     */
    private function applyDiscounts(array $costs, Call $call, Company $company): array
    {
        // Check for volume discounts
        if ($company->volume_discount_percentage) {
            $discount = $costs['customer_cost'] * ($company->volume_discount_percentage / 100);
            $costs['customer_cost'] -= (int)$discount;
            $costs['cost_breakdown']['discounts'] = [
                'volume_discount' => $company->volume_discount_percentage . '%',
                'discount_amount' => (int)$discount
            ];
        }

        // Check for promotional rates
        if ($company->promotional_rate_until && Carbon::now()->lte($company->promotional_rate_until)) {
            $costs['cost_breakdown']['promotional_rate'] = true;
            // Apply promotional rate logic here
        }

        return $costs;
    }

    /**
     * Update call with calculated costs
     */
    public function updateCallCosts(Call $call): void
    {
        $costs = $this->calculateCallCosts($call);

        $call->update([
            'base_cost' => $costs['base_cost'],
            'reseller_cost' => $costs['reseller_cost'],
            'customer_cost' => $costs['customer_cost'],
            'platform_profit' => $costs['platform_profit'],
            'reseller_profit' => $costs['reseller_profit'],
            'total_profit' => $costs['total_profit'],
            'profit_margin_platform' => $costs['profit_margin_platform'],
            'profit_margin_reseller' => $costs['profit_margin_reseller'],
            'profit_margin_total' => $costs['profit_margin_total'],
            'cost_calculation_method' => $costs['cost_calculation_method'],
            'cost_breakdown' => json_encode($costs['cost_breakdown'])
        ]);
    }

    /**
     * Get display cost based on user role
     */
    public function getDisplayCost(Call $call, ?User $user): int
    {
        // First try to use cost_cents field (if it exists and is set)
        if ($call->cost_cents !== null && $call->cost_cents > 0) {
            return $call->cost_cents;
        }

        // Convert decimal cost to cents if that's what we have
        $costInCents = $call->cost !== null ? (int)($call->cost * 100) : 0;

        // If no user, return base cost
        if (!$user) {
            return $call->base_cost ?? $costInCents;
        }

        // Super admin sees all costs
        if ($user->hasRole(['super-admin', 'super_admin', 'Super Admin'])) {
            return $call->customer_cost ?? $call->base_cost ?? $costInCents;
        }

        // Reseller sees their cost and customer cost
        if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
            $company = $user->company;
            if ($company && $call->company && $call->company->parent_company_id === $company->id) {
                return $call->reseller_cost ?? $costInCents;
            }
        }

        // Customer sees only their cost
        if ($user->company_id === $call->company_id) {
            return $call->customer_cost ?? $costInCents;
        }

        // Default: show standard cost
        return $call->base_cost ?? $costInCents;
    }

    /**
     * Calculate all profit levels
     */
    private function calculateProfits(array $costs): array
    {
        // Platform profit (was uns gehört)
        if ($costs['cost_calculation_method'] === 'reseller') {
            // Bei Mandanten-Kunden: Platform-Profit = Mandanten-Kosten - Basiskosten
            $costs['platform_profit'] = $costs['reseller_cost'] - $costs['base_cost'];

            // Mandanten-Profit = Kunden-Kosten - Mandanten-Kosten
            $costs['reseller_profit'] = $costs['customer_cost'] - $costs['reseller_cost'];
        } else {
            // Bei Direkt-Kunden: Platform-Profit = Kunden-Kosten - Basiskosten
            $costs['platform_profit'] = $costs['customer_cost'] - $costs['base_cost'];
            $costs['reseller_profit'] = 0;
        }

        // Gesamt-Profit = Kunden-Kosten - Basiskosten
        $costs['total_profit'] = $costs['customer_cost'] - $costs['base_cost'];

        // Profit-Margen berechnen (in Prozent)
        if ($costs['base_cost'] > 0) {
            $costs['profit_margin_platform'] = round(($costs['platform_profit'] / $costs['base_cost']) * 100, 2);
            $costs['profit_margin_total'] = round(($costs['total_profit'] / $costs['base_cost']) * 100, 2);
        }

        if ($costs['reseller_cost'] > 0 && $costs['reseller_profit'] > 0) {
            $costs['profit_margin_reseller'] = round(($costs['reseller_profit'] / $costs['reseller_cost']) * 100, 2);
        }

        return $costs;
    }

    /**
     * Get platform profit
     */
    public function getPlatformProfit(Call $call): int
    {
        return $call->platform_profit ?? 0;
    }

    /**
     * Get reseller profit
     */
    public function getResellerProfit(Call $call): int
    {
        return $call->reseller_profit ?? 0;
    }

    /**
     * Get total profit
     */
    public function getTotalProfit(Call $call): int
    {
        return $call->total_profit ?? 0;
    }

    /**
     * Get profit margin
     */
    public function getProfitMargin(Call $call, string $type = 'total'): float
    {
        switch ($type) {
            case 'platform':
                return $call->profit_margin_platform ?? 0;
            case 'reseller':
                return $call->profit_margin_reseller ?? 0;
            case 'total':
            default:
                return $call->profit_margin_total ?? 0;
        }
    }

    /**
     * Get display profit based on user role
     */
    public function getDisplayProfit(Call $call, ?User $user): array
    {
        if (!$user) {
            return ['profit' => 0, 'margin' => 0, 'type' => 'none'];
        }

        // Super admin sees total profit
        if ($user->hasRole(['super-admin', 'super_admin', 'Super Admin'])) {
            return [
                'profit' => $call->total_profit ?? 0,
                'margin' => $call->profit_margin_total ?? 0,
                'type' => 'total',
                'breakdown' => [
                    'platform' => $call->platform_profit ?? 0,
                    'reseller' => $call->reseller_profit ?? 0,
                    'total' => $call->total_profit ?? 0,
                ]
            ];
        }

        // Reseller sees their profit
        if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
            $company = $user->company;
            if ($company && $call->company && $call->company->parent_company_id === $company->id) {
                return [
                    'profit' => $call->reseller_profit ?? 0,
                    'margin' => $call->profit_margin_reseller ?? 0,
                    'type' => 'reseller'
                ];
            }
        }

        // Customers don't see profit
        return ['profit' => 0, 'margin' => 0, 'type' => 'none'];
    }
}