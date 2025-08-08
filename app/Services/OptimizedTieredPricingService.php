<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Company;
use App\Models\SecureCompanyPricingTier as CompanyPricingTier;
use App\Models\PricingMargin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class OptimizedTieredPricingService extends TieredPricingService
{
    /**
     * Batch cache duration in seconds
     */
    private const CACHE_TTL = 3600; // 1 hour
    
    /**
     * Get margin report with optimized queries
     */
    public function getMarginReport(Company $reseller, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        // Use cache for expensive operations
        $cacheKey = "margin_report:{$reseller->id}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($reseller, $startDate, $endDate) {
            return $this->calculateMarginReport($reseller, $startDate, $endDate);
        });
    }
    
    /**
     * Calculate margin report with optimized queries
     */
    private function calculateMarginReport(Company $reseller, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        // Eager load all relationships
        $reseller->load(['childCompanies', 'pricingTiers']);
        
        $clientIds = $reseller->childCompanies->pluck('id');
        
        // Single query for all calls with aggregation
        $callStats = Call::query()
            ->select(
                'company_id',
                DB::raw('COUNT(*) as call_count'),
                DB::raw('SUM(duration_minutes) as total_minutes'),
                DB::raw('SUM(CASE WHEN direction = "inbound" THEN duration_minutes ELSE 0 END) as inbound_minutes'),
                DB::raw('SUM(CASE WHEN direction = "outbound" THEN duration_minutes ELSE 0 END) as outbound_minutes')
            )
            ->whereIn('company_id', $clientIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('company_id')
            ->get()
            ->keyBy('company_id');
        
        // Batch load all pricing tiers
        $pricingTiers = $this->getBatchPricingTiers($reseller->id, $clientIds);
        
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
                'margin_percentage' => 0,
                'calls' => 0,
                'minutes' => 0
            ]
        ];
        
        // Process each client with cached data
        foreach ($reseller->childCompanies as $client) {
            $clientStats = $callStats->get($client->id);
            
            if (!$clientStats) {
                continue;
            }
            
            $clientData = $this->calculateClientMargin(
                $client,
                $clientStats,
                $pricingTiers->get($client->id, collect())
            );
            
            $report['clients'][] = $clientData;
            
            // Update totals
            $report['totals']['revenue'] += $clientData['revenue'];
            $report['totals']['cost'] += $clientData['cost'];
            $report['totals']['margin'] += $clientData['margin'];
            $report['totals']['calls'] += $clientData['calls'];
            $report['totals']['minutes'] += $clientData['minutes'];
        }
        
        // Calculate margin percentage
        if ($report['totals']['cost'] > 0) {
            $report['totals']['margin_percentage'] = 
                round(($report['totals']['margin'] / $report['totals']['cost']) * 100, 2);
        }
        
        return $report;
    }
    
    /**
     * Get batch pricing tiers for multiple companies
     */
    private function getBatchPricingTiers(int $resellerId, Collection $clientIds): Collection
    {
        return CompanyPricingTier::query()
            ->where('company_id', $resellerId)
            ->whereIn('child_company_id', $clientIds)
            ->where('is_active', true)
            ->get()
            ->groupBy('child_company_id');
    }
    
    /**
     * Calculate margin for a single client with pre-loaded data
     */
    private function calculateClientMargin(Company $client, $stats, Collection $pricingTiers): array
    {
        $inboundPricing = $pricingTiers->firstWhere('pricing_type', 'inbound');
        $outboundPricing = $pricingTiers->firstWhere('pricing_type', 'outbound');
        
        $revenue = 0;
        $cost = 0;
        
        // Calculate inbound costs
        if ($inboundPricing && $stats->inbound_minutes > 0) {
            $inboundCosts = $inboundPricing->calculateCost($stats->inbound_minutes);
            $revenue += $inboundCosts['sell_cost'];
            $cost += $inboundCosts['base_cost'] ?? ($stats->inbound_minutes * $inboundPricing->cost_price);
        }
        
        // Calculate outbound costs
        if ($outboundPricing && $stats->outbound_minutes > 0) {
            $outboundCosts = $outboundPricing->calculateCost($stats->outbound_minutes);
            $revenue += $outboundCosts['sell_cost'];
            $cost += $outboundCosts['base_cost'] ?? ($stats->outbound_minutes * $outboundPricing->cost_price);
        }
        
        return [
            'id' => $client->id,
            'name' => $client->name,
            'calls' => $stats->call_count,
            'minutes' => round($stats->total_minutes, 2),
            'revenue' => round($revenue, 2),
            'cost' => round($cost, 2),
            'margin' => round($revenue - $cost, 2),
            'margin_percentage' => $cost > 0 ? round((($revenue - $cost) / $cost) * 100, 2) : 0
        ];
    }
    
    /**
     * Calculate monthly invoice with optimized queries
     */
    public function calculateMonthlyInvoice(Company $company, \Carbon\Carbon $month): array
    {
        $cacheKey = "monthly_invoice:{$company->id}:{$month->format('Y-m')}";
        
        return Cache::remember($cacheKey, 1800, function () use ($company, $month) {
            return $this->calculateMonthlyInvoiceOptimized($company, $month);
        });
    }
    
    /**
     * Optimized monthly invoice calculation
     */
    private function calculateMonthlyInvoiceOptimized(Company $company, \Carbon\Carbon $month): array
    {
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();
        
        // Single aggregated query for call statistics
        $callStats = Call::query()
            ->select(
                'direction',
                DB::raw('SUM(duration_minutes) as total_minutes'),
                DB::raw('COUNT(*) as call_count')
            )
            ->where('company_id', $company->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('direction')
            ->get()
            ->keyBy('direction');
        
        // Load all pricing tiers at once
        $pricingTiers = $this->getAllPricingTiersForCompany($company);
        
        $invoice = [
            'company_id' => $company->id,
            'period' => $month->format('Y-m'),
            'line_items' => [],
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0
        ];
        
        // Process each call type
        foreach ($callStats as $direction => $stats) {
            $pricingTier = $pricingTiers->get($direction);
            
            if (!$pricingTier) {
                continue;
            }
            
            $costs = $pricingTier->calculateCost($stats->total_minutes);
            
            $invoice['line_items'][] = [
                'type' => $direction,
                'description' => $pricingTier->pricing_type_display,
                'quantity' => round($stats->total_minutes, 2),
                'calls' => $stats->call_count,
                'unit_price' => $pricingTier->sell_price,
                'included_minutes' => $pricingTier->included_minutes,
                'billable_minutes' => $costs['billable_minutes'] ?? $stats->total_minutes,
                'amount' => $costs['sell_cost']
            ];
            
            $invoice['subtotal'] += $costs['sell_cost'];
        }
        
        // Add monthly fees
        $monthlyPricing = $pricingTiers->get('monthly');
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
     * Get all pricing tiers for a company with caching
     */
    private function getAllPricingTiersForCompany(Company $company): Collection
    {
        $cacheKey = "company_all_pricing:{$company->id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($company) {
            $query = CompanyPricingTier::active();
            
            if ($company->parent_company_id) {
                // Client company - get parent's pricing
                $query->where('company_id', $company->parent_company_id)
                      ->where('child_company_id', $company->id);
            } elseif ($company->company_type === 'reseller') {
                // Reseller - get own pricing
                $query->where('company_id', $company->id)
                      ->whereNull('child_company_id');
            }
            
            return $query->get()->keyBy('pricing_type');
        });
    }
    
    /**
     * Invalidate caches when pricing changes
     */
    public function invalidatePricingCaches(Company $company): void
    {
        // Clear company-specific caches
        Cache::forget("company_all_pricing:{$company->id}");
        
        // Clear margin reports for current month
        $currentMonth = now();
        Cache::forget("margin_report:{$company->id}:{$currentMonth->startOfMonth()->format('Y-m-d')}:{$currentMonth->endOfMonth()->format('Y-m-d')}");
        
        // Clear monthly invoices
        Cache::forget("monthly_invoice:{$company->id}:{$currentMonth->format('Y-m')}");
        
        // If it's a reseller, clear child company caches too
        if ($company->isReseller()) {
            foreach ($company->childCompanies as $child) {
                Cache::forget("pricing_tier:{$child->id}:inbound");
                Cache::forget("pricing_tier:{$child->id}:outbound");
                Cache::forget("company_all_pricing:{$child->id}");
            }
        }
    }
}