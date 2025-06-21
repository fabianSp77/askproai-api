<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheWarmer
{
    /**
     * Warm invoice-related caches
     */
    public function warmInvoiceCaches(): void
    {
        try {
            // Cache usage-based invoice IDs for quick lookup
            $usageInvoiceIds = Invoice::where('creation_mode', 'usage')
                ->orWhereHas('flexibleItems', function ($query) {
                    $query->where('type', 'usage');
                })
                ->pluck('id')
                ->toArray();
                
            Cache::put('usage_invoice_ids', $usageInvoiceIds, now()->addHours(1));
            
            // Cache company settings for active companies
            Company::where('is_active', true)->each(function ($company) {
                $cacheKey = "company_settings_{$company->id}";
                Cache::put($cacheKey, [
                    'is_small_business' => $company->is_small_business,
                    'tax_id' => $company->tax_id,
                    'vat_id' => $company->vat_id,
                    'invoice_prefix' => $company->invoice_prefix,
                    'next_invoice_number' => $company->next_invoice_number,
                ], now()->addHours(2));
            });
            
            Log::info('Invoice caches warmed successfully');
        } catch (\Exception $e) {
            Log::error('Failed to warm invoice caches', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check if an invoice is usage-based using cache
     */
    public static function isUsageBasedInvoice(Invoice $invoice): bool
    {
        // Check creation_mode first
        if ($invoice->creation_mode === 'usage') {
            return true;
        }
        
        // Check cache for usage invoice IDs
        $usageInvoiceIds = Cache::get('usage_invoice_ids', []);
        if (in_array($invoice->id, $usageInvoiceIds)) {
            return true;
        }
        
        // Fallback to database check
        return $invoice->flexibleItems()->where('type', 'usage')->exists();
    }
}