<?php

namespace App\Services\Stripe;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\BillingPeriod;
use App\Models\CompanyPricing;
use App\Models\Call;
use App\Services\StripeInvoiceService;
use App\Services\TaxService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Carbon\Carbon;

class EnhancedStripeInvoiceService extends StripeInvoiceService
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Create draft invoice for manual editing
     */
    public function createDraftInvoice(Company $company, array $options = []): Invoice
    {
        return DB::transaction(function () use ($company, $options) {
            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber($company);
            
            // Create local invoice as draft
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'branch_id' => $options['branch_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                'status' => Invoice::STATUS_DRAFT,
                'creation_mode' => $options['creation_mode'] ?? 'manual',
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'currency' => $company->currency ?? 'EUR',
                'invoice_date' => $options['invoice_date'] ?? now(),
                'due_date' => $options['due_date'] ?? $this->calculateDueDate($company),
                'billing_reason' => $options['billing_reason'] ?? Invoice::REASON_MANUAL,
                'manual_editable' => true,
                'period_start' => $options['period_start'] ?? null,
                'period_end' => $options['period_end'] ?? null,
                'tax_note' => $company->is_small_business ? $this->taxService->getTaxNote($company, null) : null,
                'auto_advance' => false, // Don't auto-finalize drafts
                'metadata' => [
                    'created_by' => auth()->id(),
                    'created_at' => now()->toIso8601String(),
                ],
            ]);
            
            Log::info('Draft invoice created', [
                'invoice_id' => $invoice->id,
                'company_id' => $company->id,
                'invoice_number' => $invoiceNumber,
            ]);
            
            return $invoice;
        });
    }
    
    /**
     * Preview invoice without saving
     */
    public function previewInvoice(Company $company, array $items, array $options = []): array
    {
        $subtotal = 0;
        $taxBreakdown = [];
        $processedItems = [];
        
        foreach ($items as $item) {
            // Calculate tax for each item
            $amount = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
            $taxCalc = $this->taxService->calculateTax(
                $amount, 
                $company, 
                $item['tax_rate_id'] ?? null
            );
            
            $processedItems[] = [
                'description' => $item['description'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'unit' => $item['unit'] ?? 'Stück',
                'unit_price' => $item['unit_price'] ?? 0,
                'amount' => $amount,
                'tax_amount' => $taxCalc['tax_amount'],
                'gross_amount' => $taxCalc['gross_amount'],
                'tax_rate' => $taxCalc['tax_rate'],
                'period_start' => $item['period_start'] ?? null,
                'period_end' => $item['period_end'] ?? null,
            ];
            
            $subtotal += $amount;
            
            // Aggregate tax by rate
            $rateKey = $taxCalc['tax_rate'] . '%';
            if (!isset($taxBreakdown[$rateKey])) {
                $taxBreakdown[$rateKey] = [
                    'rate' => $taxCalc['tax_rate'],
                    'net_amount' => 0,
                    'tax_amount' => 0,
                ];
            }
            $taxBreakdown[$rateKey]['net_amount'] += $amount;
            $taxBreakdown[$rateKey]['tax_amount'] += $taxCalc['tax_amount'];
        }
        
        $totalTax = array_sum(array_column($taxBreakdown, 'tax_amount'));
        $total = $subtotal + $totalTax;
        
        return [
            'items' => $processedItems,
            'subtotal' => round($subtotal, 2),
            'tax_breakdown' => array_values($taxBreakdown),
            'tax_amount' => round($totalTax, 2),
            'total' => round($total, 2),
            'currency' => $company->currency ?? 'EUR',
            'is_small_business' => $company->is_small_business,
            'tax_note' => $company->is_small_business ? $this->taxService->getTaxNote($company, null) : null,
            'payment_terms' => $this->getPaymentTermsText($company),
            'due_date' => $this->calculateDueDate($company, $options['invoice_date'] ?? now()),
        ];
    }
    
    /**
     * Override parent method to handle small business taxation
     */
    public function createInvoiceForBillingPeriod(BillingPeriod $billingPeriod): ?Invoice
    {
        try {
            $company = $billingPeriod->company;
            
            // Ensure Stripe customer exists
            $stripeCustomerId = $this->ensureStripeCustomer($company);
            if (!$stripeCustomerId) {
                throw new \Exception('Could not create/update Stripe customer');
            }
            
            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber($company);
            
            // Create local invoice first
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'branch_id' => $billingPeriod->branch_id,
                'invoice_number' => $invoiceNumber,
                'status' => Invoice::STATUS_DRAFT,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'currency' => 'EUR',
                'invoice_date' => now(),
                'due_date' => $this->calculateDueDate($company),
                'billing_reason' => Invoice::REASON_SUBSCRIPTION_CYCLE,
                'period_start' => $billingPeriod->period_start,
                'period_end' => $billingPeriod->period_end,
                'tax_note' => $company->is_small_business ? $this->taxService->getTaxNote($company, null) : null,
                'auto_advance' => true,
            ]);
            
            // Get applicable tax rate
            $taxRate = $this->taxService->getApplicableTaxRate($company);
            
            // Sync tax rate to Stripe if needed
            $stripeTaxRateId = null;
            if (!$company->is_small_business && $taxRate->rate > 0) {
                $stripeTaxRateId = $this->taxService->syncStripeTaxRate($taxRate);
            }
            
            // Create Stripe invoice with proper tax handling
            $stripeInvoiceData = [
                'customer' => $stripeCustomerId,
                'collection_method' => 'send_invoice',
                'days_until_due' => $this->getDaysUntilDue($company),
                'metadata' => [
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'billing_period_id' => $billingPeriod->id,
                    'is_small_business' => $company->is_small_business ? 'true' : 'false',
                ],
                'custom_fields' => [
                    [
                        'name' => 'Rechnungsnummer',
                        'value' => $invoiceNumber,
                    ],
                ],
                'footer' => $this->getInvoiceFooter($company),
                'rendering_options' => [
                    'amount_tax_display' => $company->is_small_business ? 'exclude_tax' : 'include_inclusive_tax',
                ],
            ];
            
            // Add tax note for small businesses
            if ($company->is_small_business) {
                $stripeInvoiceData['custom_fields'][] = [
                    'name' => 'Steuerhinweis',
                    'value' => 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.',
                ];
            }
            
            // Add default tax rate if not small business
            if ($stripeTaxRateId) {
                $stripeInvoiceData['default_tax_rates'] = [$stripeTaxRateId];
            }
            
            $stripeInvoice = $this->stripe->invoices->create($stripeInvoiceData);
            
            // Update local invoice with Stripe ID
            $invoice->update(['stripe_invoice_id' => $stripeInvoice->id]);
            
            // Add invoice items with proper tax handling
            $this->addInvoiceItemsWithTax($invoice, $billingPeriod, $stripeInvoice->id);
            
            // Finalize if auto_advance
            if ($invoice->auto_advance) {
                $this->finalizeInvoice($invoice);
            }
            
            // Link billing period to invoice
            $billingPeriod->update([
                'invoice_id' => $invoice->id,
                'is_invoiced' => true,
            ]);
            
            // Update company revenue tracking
            $this->updateCompanyRevenue($company, $invoice->total);
            
            Log::info('Invoice created with tax compliance', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $stripeInvoice->id,
                'company_id' => $company->id,
                'is_small_business' => $company->is_small_business,
                'tax_rate' => $taxRate->rate,
            ]);
            
            return $invoice;
            
        } catch (\Exception $e) {
            Log::error('Error creating invoice', [
                'billing_period_id' => $billingPeriod->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Clean up local invoice if created
            if (isset($invoice)) {
                $invoice->delete();
            }
            
            return null;
        }
    }
    
    /**
     * Add invoice items with proper tax calculation
     */
    protected function addInvoiceItemsWithTax(Invoice $invoice, BillingPeriod $billingPeriod, string $stripeInvoiceId): void
    {
        $company = $invoice->company;
        $taxRate = $this->taxService->getApplicableTaxRate($company);
        $subtotal = 0;
        
        // Get pricing model
        $pricing = $billingPeriod->pricing_model_id 
            ? CompanyPricing::find($billingPeriod->pricing_model_id)
            : CompanyPricing::getCurrentForCompany($company->id);
            
        if (!$pricing) {
            Log::warning('No pricing model found for invoice', [
                'invoice_id' => $invoice->id,
                'company_id' => $company->id,
            ]);
            return;
        }
        
        // 1. Monthly base fee
        if ($pricing->monthly_base_fee > 0) {
            $this->addInvoiceItemWithTax(
                $invoice,
                $stripeInvoiceId,
                InvoiceItem::TYPE_MONTHLY_FEE,
                'Monatliche Grundgebühr',
                1,
                'Monat',
                $pricing->monthly_base_fee,
                $taxRate,
                $billingPeriod->period_start,
                $billingPeriod->period_end
            );
            $subtotal += $pricing->monthly_base_fee;
        }
        
        // 2. Usage charges
        if ($billingPeriod->total_minutes > 0) {
            $billableMinutes = max(0, $billingPeriod->total_minutes - $pricing->included_minutes);
            
            if ($billableMinutes > 0) {
                $minutePrice = $pricing->overage_price_per_minute ?? $pricing->price_per_minute;
                $usageAmount = $billableMinutes * $minutePrice;
                
                $this->addInvoiceItemWithTax(
                    $invoice,
                    $stripeInvoiceId,
                    InvoiceItem::TYPE_USAGE,
                    sprintf(
                        'Telefonie-Nutzung: %.0f Minuten (%.0f inkl., %.0f zusätzlich)',
                        $billingPeriod->total_minutes,
                        $pricing->included_minutes,
                        $billableMinutes
                    ),
                    $billableMinutes,
                    'Minuten',
                    $minutePrice,
                    $taxRate,
                    $billingPeriod->period_start,
                    $billingPeriod->period_end
                );
                $subtotal += $usageAmount;
            }
        }
        
        // Calculate final amounts
        $taxCalc = $this->taxService->calculateTax($subtotal, $company);
        
        // Update invoice totals
        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxCalc['tax_amount'],
            'total' => $taxCalc['gross_amount'],
        ]);
    }
    
    /**
     * Add single invoice item with tax handling
     */
    protected function addInvoiceItemWithTax(
        Invoice $invoice,
        string $stripeInvoiceId,
        string $type,
        string $description,
        float $quantity,
        string $unit,
        float $unitPrice,
        $taxRate,
        ?\Carbon\Carbon $periodStart = null,
        ?\Carbon\Carbon $periodEnd = null
    ): InvoiceItem {
        $amount = $quantity * $unitPrice;
        $company = $invoice->company;
        
        // Create Stripe invoice item
        try {
            $stripeItemData = [
                'customer' => $company->stripe_customer_id,
                'invoice' => $stripeInvoiceId,
                'description' => $description,
                'quantity' => $quantity,
                'unit_amount_decimal' => $unitPrice * 100, // Convert to cents
                'currency' => 'eur',
                'metadata' => [
                    'type' => $type,
                    'unit' => $unit,
                ],
            ];
            
            // Add tax rate if not small business
            if (!$company->is_small_business && $taxRate->stripe_tax_rate_id) {
                $stripeItemData['tax_rates'] = [$taxRate->stripe_tax_rate_id];
            }
            
            $stripeItem = $this->stripe->invoiceItems->create($stripeItemData);
            $stripeItemId = $stripeItem->id;
            
        } catch (\Exception $e) {
            Log::error('Error creating Stripe invoice item', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoice->id,
            ]);
            $stripeItemId = null;
        }
        
        // Create local invoice item (using flexible items table)
        return DB::table('invoice_items_flexible')->insertGetId([
            'invoice_id' => $invoice->id,
            'stripe_invoice_item_id' => $stripeItemId,
            'type' => $type,
            'description' => $description,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'amount' => $amount,
            'tax_rate' => $taxRate->rate,
            'tax_rate_id' => $taxRate->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    
    /**
     * Finalize invoice with compliance checks
     */
    public function finalizeInvoice(Invoice $invoice): bool
    {
        try {
            // Check if already finalized
            if ($invoice->finalized_at) {
                Log::warning('Invoice already finalized', ['invoice_id' => $invoice->id]);
                return false;
            }
            
            // Validate invoice has required fields
            $validation = $this->validateInvoiceCompliance($invoice);
            if (!$validation['valid']) {
                Log::error('Invoice compliance validation failed', [
                    'invoice_id' => $invoice->id,
                    'errors' => $validation['errors'],
                ]);
                return false;
            }
            
            // Finalize in Stripe if exists
            if ($invoice->stripe_invoice_id) {
                $stripeInvoice = $this->stripe->invoices->finalizeInvoice($invoice->stripe_invoice_id);
                
                // Send invoice
                $this->stripe->invoices->sendInvoice($invoice->stripe_invoice_id);
                
                // Update with PDF URL
                $invoice->pdf_url = $stripeInvoice->invoice_pdf;
            }
            
            // Mark as finalized
            $invoice->update([
                'status' => Invoice::STATUS_OPEN,
                'finalized_at' => now(),
                'manual_editable' => false,
                'audit_log' => array_merge($invoice->audit_log ?? [], [
                    [
                        'action' => 'finalized',
                        'user_id' => auth()->id(),
                        'timestamp' => now()->toIso8601String(),
                    ],
                ]),
            ]);
            
            Log::info('Invoice finalized', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error finalizing invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Validate invoice compliance with German law
     */
    protected function validateInvoiceCompliance(Invoice $invoice): array
    {
        $errors = [];
        $company = $invoice->company;
        
        // Required fields check
        if (empty($invoice->invoice_number)) {
            $errors[] = 'Rechnungsnummer fehlt';
        }
        
        if (empty($company->name)) {
            $errors[] = 'Firmenname fehlt';
        }
        
        if (empty($company->address)) {
            $errors[] = 'Firmenadresse fehlt';
        }
        
        if (!$company->is_small_business && empty($company->tax_id) && empty($company->vat_id)) {
            $errors[] = 'Steuernummer oder USt-IdNr. fehlt';
        }
        
        if ($invoice->items->isEmpty()) {
            $errors[] = 'Keine Rechnungspositionen vorhanden';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
    /**
     * Generate compliant invoice number
     */
    protected function generateInvoiceNumber(Company $company): string
    {
        $prefix = $company->invoice_prefix ?: 'RE';
        $year = now()->year;
        $number = $company->next_invoice_number;
        
        // Increment for next use
        $company->increment('next_invoice_number');
        
        // Format: PREFIX-YYYY-00001
        return sprintf('%s-%d-%05d', $prefix, $year, $number);
    }
    
    /**
     * Update company revenue tracking
     */
    protected function updateCompanyRevenue(Company $company, float $amount): void
    {
        $company->increment('revenue_ytd', $amount);
        
        // Check thresholds if small business
        if ($company->is_small_business) {
            $thresholdCheck = $this->taxService->checkSmallBusinessThresholds($company);
            
            if ($thresholdCheck['status'] === 'exceeded') {
                // Notify about threshold exceeded
                Log::warning('Small business threshold exceeded', [
                    'company_id' => $company->id,
                    'revenue_ytd' => $company->revenue_ytd,
                ]);
                
                // TODO: Send notification email
            }
        }
    }
    
    /**
     * Get invoice footer text
     */
    protected function getInvoiceFooter(Company $company): string
    {
        $footer = "Vielen Dank für Ihr Vertrauen in AskProAI.\n";
        
        if (!empty($company->tax_id)) {
            $footer .= "Steuernummer: {$company->tax_id}\n";
        }
        
        if (!empty($company->vat_id)) {
            $footer .= "USt-IdNr.: {$company->vat_id}\n";
        }
        
        return $footer;
    }
    
    /**
     * Get payment terms text
     */
    protected function getPaymentTermsText(Company $company): string
    {
        $terms = $company->payment_terms ?? 'net30';
        
        return match($terms) {
            'due_on_receipt' => 'Zahlbar sofort nach Erhalt',
            'net15' => 'Zahlbar innerhalb von 15 Tagen',
            'net30' => 'Zahlbar innerhalb von 30 Tagen',
            'net60' => 'Zahlbar innerhalb von 60 Tagen',
            default => 'Zahlbar innerhalb von 30 Tagen',
        };
    }
    
    /**
     * Create usage-based invoice from calls data
     */
    public function createUsageBasedInvoice(Company $company, Carbon $periodStart, Carbon $periodEnd, array $options = []): Invoice
    {
        return DB::transaction(function () use ($company, $periodStart, $periodEnd, $options) {
            // Get active pricing model for the period
            $pricing = CompanyPricing::where('company_id', $company->id)
                ->where('is_active', true)
                ->where('valid_from', '<=', $periodStart)
                ->where(function ($q) use ($periodEnd) {
                    $q->whereNull('valid_until')
                      ->orWhere('valid_until', '>=', $periodEnd);
                })
                ->orderBy('valid_from', 'desc')
                ->first();
                
            if (!$pricing) {
                throw new \Exception('Kein aktives Preismodell für den gewählten Zeitraum gefunden');
            }
            
            // Get calls for the period
            $calls = Call::where('company_id', $company->id)
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->where(function($query) {
                    // Include calls that are either successful or have duration > 0
                    $query->where('call_successful', true)
                          ->orWhere('duration_sec', '>', 0)
                          ->orWhere('duration_minutes', '>', 0);
                })
                ->get();
                
            // Calculate total minutes
            $totalMinutes = $calls->sum(function ($call) {
                if ($call->duration_minutes) {
                    return $call->duration_minutes;
                } elseif ($call->duration_sec) {
                    return $call->duration_sec / 60;
                }
                return 0;
            });
            
            // Create draft invoice with usage mode
            $invoice = $this->createDraftInvoice($company, array_merge($options, [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'billing_reason' => Invoice::REASON_SUBSCRIPTION_CYCLE,
                'creation_mode' => 'usage',
            ]));
            
            $items = [];
            $subtotal = 0;
            
            // 1. Monthly base fee (pro-rated if partial month)
            if ($pricing->monthly_base_fee > 0) {
                $daysInMonth = $periodStart->daysInMonth;
                $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
                $proRateFactor = $daysInPeriod / $daysInMonth;
                $baseFee = round($pricing->monthly_base_fee * $proRateFactor, 2);
                
                if ($baseFee > 0) {
                    $items[] = [
                        'description' => $proRateFactor < 1 
                            ? sprintf('Grundgebühr (anteilig %d von %d Tagen)', $daysInPeriod, $daysInMonth)
                            : 'Monatliche Grundgebühr',
                        'quantity' => $proRateFactor,
                        'unit' => 'Monat',
                        'unit_price' => $pricing->monthly_base_fee,
                        'amount' => $baseFee,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                    ];
                    $subtotal += $baseFee;
                }
            }
            
            // 2. Setup fee (if not already invoiced)
            if ($pricing->setup_fee > 0 && !($options['skip_setup_fee'] ?? false)) {
                $setupFeeInvoiced = DB::table('invoices')
                    ->join('invoice_items_flexible', 'invoices.id', '=', 'invoice_items_flexible.invoice_id')
                    ->where('invoices.company_id', $company->id)
                    ->where('invoice_items_flexible.type', 'setup_fee')
                    ->where('invoices.status', '!=', 'cancelled')
                    ->exists();
                    
                if (!$setupFeeInvoiced) {
                    $items[] = [
                        'description' => 'Einrichtungsgebühr (einmalig)',
                        'quantity' => 1,
                        'unit' => 'Pauschal',
                        'unit_price' => $pricing->setup_fee,
                        'amount' => $pricing->setup_fee,
                    ];
                    $subtotal += $pricing->setup_fee;
                }
            }
            
            // 3. Usage charges
            if ($totalMinutes > 0) {
                // Included minutes (show but don't charge)
                if ($pricing->included_minutes > 0) {
                    $includedMinutes = min($totalMinutes, $pricing->included_minutes);
                    $items[] = [
                        'description' => sprintf('Inklusiv-Minuten (in Grundgebühr enthalten)'),
                        'quantity' => $includedMinutes,
                        'unit' => 'Minuten',
                        'unit_price' => 0,
                        'amount' => 0,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                    ];
                }
                
                // Overage minutes
                $billableMinutes = max(0, $totalMinutes - $pricing->included_minutes);
                if ($billableMinutes > 0) {
                    $minutePrice = $pricing->overage_price_per_minute ?? $pricing->price_per_minute;
                    $usageAmount = round($billableMinutes * $minutePrice, 2);
                    
                    $items[] = [
                        'description' => sprintf('Zusätzliche Gesprächsminuten'),
                        'quantity' => $billableMinutes,
                        'unit' => 'Minuten',
                        'unit_price' => $minutePrice,
                        'amount' => $usageAmount,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                    ];
                    $subtotal += $usageAmount;
                }
                
                // Call summary
                $items[] = [
                    'description' => sprintf('Zusammenfassung: %d Anrufe, %.1f Minuten gesamt', $calls->count(), $totalMinutes),
                    'quantity' => 0,
                    'unit' => 'Info',
                    'unit_price' => 0,
                    'amount' => 0,
                ];
            }
            
            // Calculate tax
            $taxInfo = $this->taxService->getDeterminedTaxRate($company);
            $taxAmount = $company->is_small_business ? 0 : round($subtotal * ($taxInfo['rate'] / 100), 2);
            $total = $subtotal + $taxAmount;
            
            // Update invoice totals
            $invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'pricing_model_id' => $pricing->id,
                    'total_minutes' => $totalMinutes,
                    'total_calls' => $calls->count(),
                    'included_minutes' => $pricing->included_minutes,
                    'billable_minutes' => $billableMinutes ?? 0,
                ]),
            ]);
            
            // Add items to invoice
            foreach ($items as $item) {
                DB::table('invoice_items_flexible')->insert([
                    'invoice_id' => $invoice->id,
                    'type' => $item['type'] ?? 'usage',
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount'],
                    'tax_rate' => $taxInfo['rate'],
                    'tax_rate_id' => $taxInfo['rate_id'],
                    'period_start' => $item['period_start'] ?? null,
                    'period_end' => $item['period_end'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            Log::info('Usage-based invoice created', [
                'invoice_id' => $invoice->id,
                'company_id' => $company->id,
                'period' => $periodStart->format('Y-m-d') . ' - ' . $periodEnd->format('Y-m-d'),
                'total_minutes' => $totalMinutes,
                'subtotal' => $subtotal,
                'total' => $invoice->total,
            ]);
            
            return $invoice;
        });
    }
    
    /**
     * Get usage statistics for preview
     */
    public function getUsageStatistics(Company $company, Carbon $periodStart, Carbon $periodEnd): array
    {
        // Get active pricing
        $pricing = CompanyPricing::where('company_id', $company->id)
            ->where('is_active', true)
            ->where('valid_from', '<=', $periodStart)
            ->where(function ($q) use ($periodEnd) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', $periodEnd);
            })
            ->orderBy('valid_from', 'desc')
            ->first();
            
        if (!$pricing) {
            return [
                'error' => 'Kein aktives Preismodell gefunden',
                'has_pricing' => false,
            ];
        }
        
        // Get calls
        $calls = Call::where('company_id', $company->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->where(function($query) {
                // Include calls that are either successful or have duration > 0
                $query->where('call_successful', true)
                      ->orWhere('duration_sec', '>', 0)
                      ->orWhere('duration_minutes', '>', 0);
            })
            ->get();
            
        $totalMinutes = $calls->sum(function ($call) {
            if ($call->duration_minutes) {
                return $call->duration_minutes;
            } elseif ($call->duration_sec) {
                return $call->duration_sec / 60;
            }
            return 0;
        });
        
        $billableMinutes = max(0, $totalMinutes - $pricing->included_minutes);
        
        return [
            'has_pricing' => true,
            'pricing' => [
                'monthly_base_fee' => $pricing->monthly_base_fee,
                'included_minutes' => $pricing->included_minutes,
                'price_per_minute' => $pricing->overage_price_per_minute ?? $pricing->price_per_minute,
            ],
            'usage' => [
                'total_calls' => $calls->count(),
                'total_minutes' => round($totalMinutes, 2),
                'included_minutes_used' => min($totalMinutes, $pricing->included_minutes),
                'billable_minutes' => round($billableMinutes, 2),
            ],
            'calls_by_day' => $calls->groupBy(function ($call) {
                return $call->created_at->format('Y-m-d');
            })->map(function ($dayCalls) {
                return [
                    'count' => $dayCalls->count(),
                    'minutes' => round($dayCalls->sum(function ($call) {
                        if ($call->duration_minutes) {
                            return $call->duration_minutes;
                        } elseif ($call->duration_sec) {
                            return $call->duration_sec / 60;
                        }
                        return 0;
                    }), 2),
                ];
            }),
        ];
    }
}