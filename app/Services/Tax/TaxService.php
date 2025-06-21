<?php

namespace App\Services\Tax;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\TaxRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaxService
{
    // German small business thresholds
    const SMALL_BUSINESS_THRESHOLD_PREVIOUS_YEAR = 22000; // €22,000
    const SMALL_BUSINESS_THRESHOLD_CURRENT_YEAR = 50000;  // €50,000
    
    /**
     * Calculate tax for a given amount
     */
    public function calculateTax(float $amount, Company $company, ?int $taxRateId = null): array
    {
        // Get applicable tax rate
        $taxRate = $this->getApplicableTaxRate($company, $taxRateId);
        
        // Calculate tax amount
        $taxAmount = 0;
        $netAmount = $amount;
        $grossAmount = $amount;
        
        if ($taxRate->rate > 0 && !$company->is_small_business) {
            // Regular taxation
            $taxAmount = round($amount * ($taxRate->rate / 100), 2);
            $grossAmount = $amount + $taxAmount;
        } elseif ($company->is_small_business) {
            // Small business - no tax but special note required
            $taxAmount = 0;
            $grossAmount = $amount;
        }
        
        return [
            'net_amount' => $netAmount,
            'tax_amount' => $taxAmount,
            'gross_amount' => $grossAmount,
            'tax_rate' => $taxRate->rate,
            'tax_rate_id' => $taxRate->id,
            'tax_rate_name' => $taxRate->name,
            'tax_note' => $this->getTaxNote($company, $taxRate),
        ];
    }
    
    /**
     * Get applicable tax rate for a company
     */
    public function getApplicableTaxRate(Company $company, ?int $taxRateId = null): TaxRate
    {
        if ($taxRateId) {
            // Use specific tax rate if provided
            $taxRate = TaxRate::find($taxRateId);
            if ($taxRate && ($taxRate->company_id === $company->id || $taxRate->is_system)) {
                return $taxRate;
            }
        }
        
        // If company is small business, always use 0% rate
        if ($company->is_small_business) {
            return $this->getSmallBusinessTaxRate();
        }
        
        // Get company's default tax rate
        $defaultRate = TaxRate::where(function($query) use ($company) {
            $query->where('company_id', $company->id)
                  ->orWhere('is_system', true);
        })
        ->where('is_default', true)
        ->first();
        
        if (!$defaultRate) {
            // Fallback to system standard rate
            $defaultRate = TaxRate::where('is_system', true)
                                 ->where('rate', 19)
                                 ->first();
        }
        
        return $defaultRate;
    }
    
    /**
     * Get small business tax rate (0%)
     */
    private function getSmallBusinessTaxRate(): TaxRate
    {
        return Cache::remember('tax_rate_small_business', 3600, function () {
            return TaxRate::where('is_system', true)
                         ->where('rate', 0)
                         ->where('name', 'like', '%Kleinunternehmer%')
                         ->first()
                    ?? TaxRate::where('is_system', true)
                              ->where('rate', 0)
                              ->first();
        });
    }
    
    /**
     * Get tax note for invoices
     */
    public function getTaxNote(Company $company, TaxRate $taxRate): ?string
    {
        if ($company->is_small_business) {
            return "Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.";
        }
        
        if ($taxRate->rate == 0 && strpos($taxRate->name, 'Reverse Charge') !== false) {
            return "Steuerschuldnerschaft des Leistungsempfängers (Reverse Charge)";
        }
        
        return null;
    }
    
    /**
     * Check if company exceeds small business thresholds
     */
    public function checkSmallBusinessThresholds(Company $company): array
    {
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;
        
        // Get revenue data
        $revenueCurrentYear = $company->revenue_ytd;
        $revenuePreviousYear = $company->revenue_previous_year;
        
        // Calculate projections for current year
        $monthsElapsed = now()->month;
        $projectedRevenue = $monthsElapsed > 0 
            ? ($revenueCurrentYear / $monthsElapsed) * 12 
            : 0;
        
        // Check thresholds
        $previousYearExceeded = $revenuePreviousYear > self::SMALL_BUSINESS_THRESHOLD_PREVIOUS_YEAR;
        $currentYearExceeded = $revenueCurrentYear > self::SMALL_BUSINESS_THRESHOLD_CURRENT_YEAR;
        $currentYearProjectedExceeded = $projectedRevenue > self::SMALL_BUSINESS_THRESHOLD_CURRENT_YEAR;
        
        // Determine status
        $status = 'safe';
        $message = '';
        
        if ($previousYearExceeded) {
            $status = 'exceeded';
            $message = "Kleinunternehmergrenze im Vorjahr überschritten. Status nicht mehr möglich.";
        } elseif ($currentYearExceeded) {
            $status = 'exceeded';
            $message = "Kleinunternehmergrenze im laufenden Jahr überschritten.";
        } elseif ($currentYearProjectedExceeded) {
            $status = 'critical';
            $message = "Kleinunternehmergrenze wird voraussichtlich überschritten.";
        } elseif ($revenueCurrentYear > (self::SMALL_BUSINESS_THRESHOLD_CURRENT_YEAR * 0.8)) {
            $status = 'warning';
            $message = "80% der Kleinunternehmergrenze erreicht.";
        }
        
        // Calculate percentages
        $percentagePreviousYear = $revenuePreviousYear > 0 
            ? round(($revenuePreviousYear / self::SMALL_BUSINESS_THRESHOLD_PREVIOUS_YEAR) * 100, 2)
            : 0;
            
        $percentageCurrentYear = round(($revenueCurrentYear / self::SMALL_BUSINESS_THRESHOLD_CURRENT_YEAR) * 100, 2);
        
        return [
            'status' => $status,
            'message' => $message,
            'revenue_previous_year' => $revenuePreviousYear,
            'revenue_current_year' => $revenueCurrentYear,
            'revenue_projected' => $projectedRevenue,
            'threshold_previous_year' => self::SMALL_BUSINESS_THRESHOLD_PREVIOUS_YEAR,
            'threshold_current_year' => self::SMALL_BUSINESS_THRESHOLD_CURRENT_YEAR,
            'percentage_previous_year' => $percentagePreviousYear,
            'percentage_current_year' => $percentageCurrentYear,
            'can_be_small_business' => !$previousYearExceeded && !$currentYearExceeded,
        ];
    }
    
    /**
     * Validate German VAT ID (USt-IdNr.) via VIES
     */
    public function validateVatId(string $vatId): array
    {
        // Remove common prefixes and spaces
        $vatId = str_replace([' ', '-', '.'], '', $vatId);
        $vatId = strtoupper($vatId);
        
        // Extract country code and number
        if (!preg_match('/^([A-Z]{2})(.+)$/', $vatId, $matches)) {
            return [
                'valid' => false,
                'error' => 'Invalid VAT ID format',
            ];
        }
        
        $countryCode = $matches[1];
        $vatNumber = $matches[2];
        
        try {
            // Call VIES API
            $response = Http::asForm()->post('https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number', [
                'countryCode' => $countryCode,
                'vatNumber' => $vatNumber,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'valid' => $data['valid'] ?? false,
                    'company_name' => $data['name'] ?? null,
                    'company_address' => $data['address'] ?? null,
                    'request_date' => now()->toIso8601String(),
                ];
            }
            
            return [
                'valid' => false,
                'error' => 'VIES service unavailable',
            ];
            
        } catch (\Exception $e) {
            Log::error('VAT ID validation failed', [
                'vat_id' => $vatId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'valid' => false,
                'error' => 'Validation service error',
            ];
        }
    }
    
    /**
     * Create or update Stripe tax rate
     */
    public function syncStripeTaxRate(TaxRate $taxRate): ?string
    {
        if (!config('services.stripe.secret')) {
            return null;
        }
        
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            
            // Check if tax rate already exists in Stripe
            if ($taxRate->stripe_tax_rate_id) {
                // Stripe tax rates cannot be updated, only created
                return $taxRate->stripe_tax_rate_id;
            }
            
            // Create new tax rate in Stripe
            $stripeTaxRate = $stripe->taxRates->create([
                'display_name' => $taxRate->name,
                'percentage' => $taxRate->rate,
                'inclusive' => false,
                'country' => 'DE',
                'description' => $taxRate->description,
                'metadata' => [
                    'askproai_tax_rate_id' => $taxRate->id,
                    'is_system' => $taxRate->is_system ? 'true' : 'false',
                ],
            ]);
            
            // Update local record with Stripe ID
            $taxRate->update(['stripe_tax_rate_id' => $stripeTaxRate->id]);
            
            return $stripeTaxRate->id;
            
        } catch (\Exception $e) {
            Log::error('Failed to sync tax rate to Stripe', [
                'tax_rate_id' => $taxRate->id,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
    
    /**
     * Get tax configuration for invoice
     */
    public function getInvoiceTaxConfiguration(Invoice $invoice): array
    {
        $company = $invoice->company;
        $items = [];
        $totalNet = 0;
        $totalTax = 0;
        $taxBreakdown = [];
        
        // Process each invoice item
        foreach ($invoice->items as $item) {
            $taxCalc = $this->calculateTax($item->amount, $company, $item->tax_rate_id);
            
            $items[] = array_merge($item->toArray(), [
                'tax_calculation' => $taxCalc,
            ]);
            
            $totalNet += $taxCalc['net_amount'];
            $totalTax += $taxCalc['tax_amount'];
            
            // Group by tax rate for breakdown
            $rateKey = $taxCalc['tax_rate'] . '%';
            if (!isset($taxBreakdown[$rateKey])) {
                $taxBreakdown[$rateKey] = [
                    'rate' => $taxCalc['tax_rate'],
                    'net_amount' => 0,
                    'tax_amount' => 0,
                ];
            }
            $taxBreakdown[$rateKey]['net_amount'] += $taxCalc['net_amount'];
            $taxBreakdown[$rateKey]['tax_amount'] += $taxCalc['tax_amount'];
        }
        
        return [
            'items' => $items,
            'total_net' => round($totalNet, 2),
            'total_tax' => round($totalTax, 2),
            'total_gross' => round($totalNet + $totalTax, 2),
            'tax_breakdown' => array_values($taxBreakdown),
            'tax_note' => $this->getTaxNote($company, $this->getApplicableTaxRate($company)),
            'is_small_business' => $company->is_small_business,
        ];
    }
}