<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Company;
use App\Models\CompanyPricingTier;
use App\Models\PricingMargin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class TieredPricingService
{
    /**
     * Calculate call cost based on company hierarchy and pricing tiers
     */
    public function calculateCallCost(Call $call): array
    {
        $company = $call->company;
        $duration = $call->duration_minutes ?? 0;
        $callType = $call->direction === 'outbound' ? 'outbound' : 'inbound';
        
        // Get base cost (what we pay to providers)
        $baseCost = $this->getProviderCost($call);
        
        // Get applicable pricing tier
        $pricingTier = $this->getApplicablePricingTier($company, $callType);
        
        if (!$pricingTier) {
            // Fallback to company default pricing
            return $this->calculateDefaultPricing($company, $duration, $baseCost);
        }
        
        // Calculate costs using the pricing tier
        $costs = $pricingTier->calculateCost($duration);
        
        // Add base provider cost
        $costs['provider_cost'] = $baseCost;
        $costs['total_margin'] = $costs['sell_cost'] - $baseCost;
        
        // Store margin data for reporting
        $this->recordMargin($pricingTier, $costs);
        
        return $costs;
    }
    
    /**
     * Get applicable pricing tier for a company
     */
    public function getApplicablePricingTier(Company $company, string $pricingType): ?CompanyPricingTier
    {
        // Cache key for performance
        $cacheKey = "pricing_tier:{$company->id}:{$pricingType}";
        
        return Cache::remember($cacheKey, 300, function () use ($company, $pricingType) {
            // If company has a parent (is a client), get parent's pricing for this client
            if ($company->parent_company_id) {
                return CompanyPricingTier::active()
                    ->where('company_id', $company->parent_company_id)
                    ->where('child_company_id', $company->id)
                    ->where('pricing_type', $pricingType)
                    ->first();
            }
            
            // If company is a reseller, get their own pricing
            if ($company->company_type === 'reseller') {
                return CompanyPricingTier::active()
                    ->where('company_id', $company->id)
                    ->whereNull('child_company_id')
                    ->where('pricing_type', $pricingType)
                    ->first();
            }
            
            return null;
        });
    }
    
    /**
     * Calculate monthly invoice for a company
     */
    public function calculateMonthlyInvoice(Company $company, \Carbon\Carbon $month): array
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();
        
        // Get all calls for the period
        $calls = Call::where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
        
        $invoice = [
            'company_id' => $company->id,
            'period' => $month->format('Y-m'),
            'line_items' => [],
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0
        ];
        
        // Group calls by type
        $callsByType = $calls->groupBy('direction');
        
        foreach ($callsByType as $direction => $typeCalls) {
            $totalMinutes = $typeCalls->sum('duration_minutes');
            $pricingTier = $this->getApplicablePricingTier($company, $direction);
            
            if ($pricingTier) {
                $costs = $pricingTier->calculateCost($totalMinutes);
                
                $invoice['line_items'][] = [
                    'type' => $direction,
                    'description' => $pricingTier->pricing_type_display,
                    'quantity' => $totalMinutes,
                    'unit_price' => $pricingTier->sell_price,
                    'included_minutes' => $pricingTier->included_minutes,
                    'billable_minutes' => $costs['billable_minutes'] ?? $totalMinutes,
                    'amount' => $costs['sell_cost']
                ];
                
                $invoice['subtotal'] += $costs['sell_cost'];
            }
        }
        
        // Add monthly fees
        $monthlyPricing = $this->getApplicablePricingTier($company, 'monthly');
        if ($monthlyPricing && $monthlyPricing->monthly_fee > 0) {
            $invoice['line_items'][] = [
                'type' => 'monthly',
                'description' => 'Monatliche Grundgebühr',
                'quantity' => 1,
                'unit_price' => $monthlyPricing->monthly_fee,
                'amount' => $monthlyPricing->monthly_fee
            ];
            
            $invoice['subtotal'] += $monthlyPricing->monthly_fee;
        }
        
        // Calculate tax (19% German VAT)
        $invoice['tax'] = round($invoice['subtotal'] * 0.19, 2);
        $invoice['total'] = $invoice['subtotal'] + $invoice['tax'];
        
        return $invoice;
    }
    
    /**
     * Update pricing for a client company
     */
    public function updateClientPricing(
        Company $reseller,
        Company $client,
        array $pricingData
    ): CompanyPricingTier {
        // Validate reseller owns the client
        if ($client->parent_company_id !== $reseller->id) {
            throw new \InvalidArgumentException('Client does not belong to this reseller');
        }
        
        // Validate required fields
        if (empty($pricingData['pricing_type']) || !in_array($pricingData['pricing_type'], ['inbound', 'outbound', 'sms', 'monthly'])) {
            throw new \InvalidArgumentException('Invalid pricing type');
        }
        
        // Validate numeric values
        $numericFields = ['cost_price', 'sell_price', 'setup_fee', 'monthly_fee', 'included_minutes', 'overage_rate'];
        foreach ($numericFields as $field) {
            if (isset($pricingData[$field]) && !is_numeric($pricingData[$field])) {
                throw new \InvalidArgumentException("Field {$field} must be numeric");
            }
            if (isset($pricingData[$field]) && $pricingData[$field] < 0) {
                throw new \InvalidArgumentException("Field {$field} cannot be negative");
            }
        }
        
        // Validate business logic
        if (isset($pricingData['cost_price'], $pricingData['sell_price']) && 
            $pricingData['sell_price'] < $pricingData['cost_price']) {
            throw new \InvalidArgumentException('Sell price cannot be lower than cost price');
        }
        
        // Clear cache
        Cache::forget("pricing_tier:{$client->id}:{$pricingData['pricing_type']}");
        
        return CompanyPricingTier::updateOrCreate(
            [
                'company_id' => $reseller->id,
                'child_company_id' => $client->id,
                'pricing_type' => $pricingData['pricing_type']
            ],
            [
                'cost_price' => max(0, (float) ($pricingData['cost_price'] ?? 0)),
                'sell_price' => max(0, (float) ($pricingData['sell_price'] ?? 0)),
                'setup_fee' => max(0, (float) ($pricingData['setup_fee'] ?? 0)),
                'monthly_fee' => max(0, (float) ($pricingData['monthly_fee'] ?? 0)),
                'included_minutes' => max(0, (int) ($pricingData['included_minutes'] ?? 0)),
                'overage_rate' => max(0, (float) ($pricingData['overage_rate'] ?? $pricingData['sell_price'] ?? 0)),
                'is_active' => (bool) ($pricingData['is_active'] ?? true),
                'metadata' => is_array($pricingData['metadata'] ?? null) ? $pricingData['metadata'] : null
            ]
        );
    }
    
    /**
     * Validate user can access company data
     */
    private function validateCompanyAccess(User $user, Company $company): bool
    {
        // Super admin can access all
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // User can access their own company
        if ($user->company_id === $company->id) {
            return true;
        }
        
        // Reseller can access child companies
        if ($user->hasRole(['reseller_owner', 'reseller_admin', 'reseller_support']) && 
            $user->company && $user->company->isReseller()) {
            return $user->company->childCompanies()->where('id', $company->id)->exists();
        }
        
        return false;
    }
    
    /**
     * Sanitize and validate pricing data input
     */
    private function sanitizePricingData(array $data): array
    {
        $sanitized = [];
        
        // Allowed fields with their types
        $allowedFields = [
            'pricing_type' => 'string',
            'cost_price' => 'float',
            'sell_price' => 'float',
            'setup_fee' => 'float',
            'monthly_fee' => 'float',
            'included_minutes' => 'int',
            'overage_rate' => 'float',
            'is_active' => 'bool',
            'metadata' => 'array'
        ];
        
        foreach ($allowedFields as $field => $type) {
            if (!isset($data[$field])) {
                continue;
            }
            
            $value = $data[$field];
            
            switch ($type) {
                case 'float':
                    $sanitized[$field] = is_numeric($value) ? (float) $value : 0;
                    break;
                case 'int':
                    $sanitized[$field] = is_numeric($value) ? (int) $value : 0;
                    break;
                case 'bool':
                    $sanitized[$field] = (bool) $value;
                    break;
                case 'string':
                    $sanitized[$field] = is_string($value) ? trim($value) : '';
                    break;
                case 'array':
                    $sanitized[$field] = is_array($value) ? $value : null;
                    break;
            }
        }
        
        return $sanitized;
    }

    /**
     * Get margin report for reseller
     */
    public function getMarginReport(Company $reseller, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $clients = $reseller->childCompanies;
        $report = [
            'reseller' => $reseller->name,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'clients' => [],
            'totals' => [
                'revenue' => 0,
                'cost' => 0,
                'margin' => 0,
                'margin_percentage' => 0
            ]
        ];
        
        foreach ($clients as $client) {
            $clientData = [
                'name' => $client->name,
                'calls' => 0,
                'minutes' => 0,
                'revenue' => 0,
                'cost' => 0,
                'margin' => 0
            ];
            
            $calls = Call::where('company_id', $client->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            foreach ($calls as $call) {
                $costs = $this->calculateCallCost($call);
                $clientData['calls']++;
                $clientData['minutes'] += $call->duration_minutes;
                $clientData['revenue'] += $costs['sell_cost'];
                $clientData['cost'] += $costs['base_cost'];
                $clientData['margin'] += $costs['margin'];
            }
            
            $report['clients'][] = $clientData;
            $report['totals']['revenue'] += $clientData['revenue'];
            $report['totals']['cost'] += $clientData['cost'];
            $report['totals']['margin'] += $clientData['margin'];
        }
        
        if ($report['totals']['cost'] > 0) {
            $report['totals']['margin_percentage'] = 
                round(($report['totals']['margin'] / $report['totals']['cost']) * 100, 2);
        }
        
        return $report;
    }
    
    /**
     * Get provider cost for a call
     */
    private function getProviderCost(Call $call): float
    {
        // This would integrate with your actual provider pricing
        // For now, using a simple calculation
        $baseRates = [
            'inbound' => 0.015,  // 1.5 cents per minute
            'outbound' => 0.025  // 2.5 cents per minute
        ];
        
        $rate = $baseRates[$call->direction] ?? $baseRates['inbound'];
        return round($call->duration_minutes * $rate, 4);
    }
    
    /**
     * Calculate default pricing when no tier is defined
     */
    private function calculateDefaultPricing(Company $company, float $duration, float $baseCost): array
    {
        $defaultRate = $company->price_per_minute ?? 0.35;
        $sellCost = round($duration * $defaultRate, 2);
        
        return [
            'base_cost' => $baseCost,
            'sell_cost' => $sellCost,
            'margin' => $sellCost - $baseCost,
            'provider_cost' => $baseCost,
            'total_margin' => $sellCost - $baseCost,
            'included_minutes_used' => 0,
            'billable_minutes' => $duration
        ];
    }
    
    /**
     * Record margin data for reporting
     */
    private function recordMargin(CompanyPricingTier $pricingTier, array $costs): void
    {
        // Only record if there's actual usage
        if (($costs['sell_cost'] ?? 0) > 0) {
            PricingMargin::create([
                'company_pricing_tier_id' => $pricingTier->id,
                'margin_amount' => $costs['margin'] ?? 0,
                'margin_percentage' => $pricingTier->calculateMargin()['percentage'],
                'calculated_date' => now()->toDateString()
            ]);
        }
    }
}