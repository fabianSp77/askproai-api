<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\BillingPeriod;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeInvoiceService
{
    protected StripeClient $stripe;
    protected PricingService $pricingService;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
        $this->pricingService = new PricingService();
    }

    /**
     * Create or update Stripe customer for a company.
     */
    public function ensureStripeCustomer(Company $company): ?string
    {
        try {
            if ($company->stripe_customer_id) {
                // Update existing customer
                $this->stripe->customers->update($company->stripe_customer_id, [
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'address' => [
                        'line1' => $company->address,
                        'city' => $company->city,
                        'postal_code' => $company->postal_code,
                        'country' => $company->country ?? 'DE',
                    ],
                    'metadata' => [
                        'company_id' => $company->id,
                        'tax_number' => $company->tax_number,
                    ],
                ]);
                
                return $company->stripe_customer_id;
            } else {
                // Create new customer
                $customer = $this->stripe->customers->create([
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'address' => [
                        'line1' => $company->address,
                        'city' => $company->city,
                        'postal_code' => $company->postal_code,
                        'country' => $company->country ?? 'DE',
                    ],
                    'metadata' => [
                        'company_id' => $company->id,
                        'tax_number' => $company->tax_number,
                    ],
                    'preferred_locales' => ['de-DE'],
                ]);
                
                $company->update(['stripe_customer_id' => $customer->id]);
                
                Log::info('Stripe customer created', [
                    'company_id' => $company->id,
                    'stripe_customer_id' => $customer->id,
                ]);
                
                return $customer->id;
            }
        } catch (ApiErrorException $e) {
            Log::error('Stripe customer error', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Create invoice for a billing period.
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

            // Create local invoice first
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'branch_id' => $billingPeriod->branch_id,
                'invoice_number' => Invoice::generateInvoiceNumber($company),
                'status' => Invoice::STATUS_DRAFT,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'currency' => 'EUR',
                'invoice_date' => now(),
                'due_date' => $this->calculateDueDate($company),
                'billing_reason' => Invoice::REASON_SUBSCRIPTION_CYCLE,
                'auto_advance' => true,
            ]);

            // Create Stripe invoice
            $stripeInvoice = $this->stripe->invoices->create([
                'customer' => $stripeCustomerId,
                'collection_method' => 'send_invoice',
                'days_until_due' => $this->getDaysUntilDue($company),
                'metadata' => [
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'billing_period_id' => $billingPeriod->id,
                ],
                'custom_fields' => [
                    [
                        'name' => 'Steuernummer',
                        'value' => $company->tax_number ?? 'N/A',
                    ],
                ],
                'footer' => 'Vielen Dank für Ihr Vertrauen in AskProAI.',
                'rendering_options' => [
                    'amount_tax_display' => 'include_inclusive_tax',
                ],
            ]);

            // Update local invoice with Stripe ID
            $invoice->update(['stripe_invoice_id' => $stripeInvoice->id]);

            // Add invoice items
            $this->addInvoiceItems($invoice, $billingPeriod, $stripeInvoice->id);

            // Finalize Stripe invoice if auto_advance is true
            if ($invoice->auto_advance) {
                $finalizedInvoice = $this->stripe->invoices->finalizeInvoice($stripeInvoice->id);
                
                // Update local invoice
                $invoice->update([
                    'status' => Invoice::STATUS_OPEN,
                    'pdf_url' => $finalizedInvoice->invoice_pdf,
                ]);
                
                // Send invoice
                $this->stripe->invoices->sendInvoice($stripeInvoice->id);
            }

            // Link billing period to invoice
            $billingPeriod->update([
                'invoice_id' => $invoice->id,
                'is_invoiced' => true,
            ]);

            Log::info('Invoice created', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $stripeInvoice->id,
                'company_id' => $company->id,
            ]);

            return $invoice;

        } catch (\Exception $e) {
            Log::error('Error creating invoice', [
                'billing_period_id' => $billingPeriod->id,
                'error' => $e->getMessage(),
            ]);
            
            // Clean up local invoice if created
            if (isset($invoice)) {
                $invoice->delete();
            }
            
            return null;
        }
    }

    /**
     * Add items to invoice.
     */
    protected function addInvoiceItems(Invoice $invoice, BillingPeriod $billingPeriod, string $stripeInvoiceId): void
    {
        $company = $invoice->company;
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

        $subtotal = 0;

        // 1. Monthly base fee
        if ($pricing->monthly_base_fee > 0) {
            $this->addInvoiceItem(
                $invoice,
                $stripeInvoiceId,
                InvoiceItem::TYPE_MONTHLY_FEE,
                'Monatliche Grundgebühr',
                1,
                'Monat',
                $pricing->monthly_base_fee,
                $billingPeriod->period_start,
                $billingPeriod->period_end
            );
            $subtotal += $pricing->monthly_base_fee;
        }

        // 2. Setup fee (if not already invoiced)
        if ($pricing->setup_fee > 0) {
            $setupFeeInvoiced = \DB::table('setup_fee_tracking')
                ->where('company_id', $company->id)
                ->where('pricing_model_id', $pricing->id)
                ->exists();

            if (!$setupFeeInvoiced) {
                $this->addInvoiceItem(
                    $invoice,
                    $stripeInvoiceId,
                    InvoiceItem::TYPE_SETUP_FEE,
                    'Einrichtungsgebühr',
                    1,
                    'Einmalig',
                    $pricing->setup_fee
                );
                $subtotal += $pricing->setup_fee;

                // Track that setup fee has been invoiced
                \DB::table('setup_fee_tracking')->insert([
                    'company_id' => $company->id,
                    'pricing_model_id' => $pricing->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $pricing->setup_fee,
                    'invoiced_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Usage charges
        if ($billingPeriod->total_minutes > 0) {
            $billableMinutes = max(0, $billingPeriod->total_minutes - $pricing->included_minutes);
            
            if ($billableMinutes > 0) {
                $minutePrice = $pricing->overage_price_per_minute ?? $pricing->price_per_minute;
                $usageAmount = $billableMinutes * $minutePrice;
                
                $this->addInvoiceItem(
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
                    $billingPeriod->period_start,
                    $billingPeriod->period_end
                );
                $subtotal += $usageAmount;
            }
        }

        // 4. Additional services
        $additionalServices = \DB::table('customer_services')
            ->where('company_id', $company->id)
            ->when($invoice->branch_id, fn($q) => $q->where('branch_id', $invoice->branch_id))
            ->where('status', 'pending')
            ->whereBetween('service_date', [$billingPeriod->period_start, $billingPeriod->period_end])
            ->get();

        foreach ($additionalServices as $service) {
            $serviceDetails = \DB::table('additional_services')->find($service->service_id);
            
            $this->addInvoiceItem(
                $invoice,
                $stripeInvoiceId,
                InvoiceItem::TYPE_SERVICE,
                $serviceDetails->name . ($service->notes ? ': ' . $service->notes : ''),
                $service->quantity,
                $serviceDetails->unit,
                $service->unit_price,
                $service->service_date,
                $service->service_date
            );
            $subtotal += $service->total_price;

            // Mark service as invoiced
            \DB::table('customer_services')
                ->where('id', $service->id)
                ->update([
                    'invoice_id' => $invoice->id,
                    'status' => 'invoiced',
                    'updated_at' => now(),
                ]);
        }

        // Calculate tax (19% German VAT)
        $taxRate = 19; // TODO: Make this configurable
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;

        // Update invoice totals
        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }

    /**
     * Add single invoice item.
     */
    protected function addInvoiceItem(
        Invoice $invoice,
        string $stripeInvoiceId,
        string $type,
        string $description,
        float $quantity,
        string $unit,
        float $unitPrice,
        ?\Carbon\Carbon $periodStart = null,
        ?\Carbon\Carbon $periodEnd = null
    ): InvoiceItem {
        $amount = $quantity * $unitPrice;
        
        // Create Stripe invoice item
        try {
            $stripeItem = $this->stripe->invoiceItems->create([
                'customer' => $invoice->company->stripe_customer_id,
                'invoice' => $stripeInvoiceId,
                'description' => $description,
                'quantity' => $quantity,
                'unit_amount_decimal' => $unitPrice * 100, // Convert to cents
                'currency' => 'eur',
                'metadata' => [
                    'type' => $type,
                    'unit' => $unit,
                ],
            ]);
            
            $stripeItemId = $stripeItem->id;
        } catch (\Exception $e) {
            Log::error('Error creating Stripe invoice item', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoice->id,
            ]);
            $stripeItemId = null;
        }

        // Create local invoice item
        return InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'stripe_invoice_item_id' => $stripeItemId,
            'type' => $type,
            'description' => $description,
            'quantity' => $quantity,
            'unit' => $unit,
            'unit_price' => $unitPrice,
            'amount' => $amount,
            'tax_rate' => 19, // TODO: Make configurable
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);
    }

    /**
     * Process Stripe webhook for invoice events.
     */
    public function processWebhook(array $event): void
    {
        try {
            switch ($event['type']) {
                case 'invoice.payment_succeeded':
                    $this->handlePaymentSucceeded($event['data']['object']);
                    break;
                    
                case 'invoice.payment_failed':
                    $this->handlePaymentFailed($event['data']['object']);
                    break;
                    
                case 'invoice.finalized':
                    $this->handleInvoiceFinalized($event['data']['object']);
                    break;
                    
                case 'invoice.voided':
                    $this->handleInvoiceVoided($event['data']['object']);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Error processing Stripe webhook', [
                'event_type' => $event['type'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle successful payment.
     */
    protected function handlePaymentSucceeded(array $stripeInvoice): void
    {
        $invoice = Invoice::where('stripe_invoice_id', $stripeInvoice['id'])->first();
        
        if (!$invoice) {
            Log::warning('Invoice not found for Stripe payment', [
                'stripe_invoice_id' => $stripeInvoice['id'],
            ]);
            return;
        }

        // Update invoice status
        $invoice->update([
            'status' => Invoice::STATUS_PAID,
            'paid_date' => now(),
            'payment_method' => 'stripe',
        ]);

        // Create payment record
        Payment::create([
            'invoice_id' => $invoice->id,
            'stripe_payment_id' => $stripeInvoice['payment_intent'],
            'payment_method' => 'stripe',
            'amount' => $stripeInvoice['amount_paid'] / 100, // Convert from cents
            'currency' => strtoupper($stripeInvoice['currency']),
            'status' => 'succeeded',
            'payment_date' => now(),
            'metadata' => [
                'stripe_invoice' => $stripeInvoice,
            ],
        ]);

        Log::info('Invoice payment succeeded', [
            'invoice_id' => $invoice->id,
            'amount' => $stripeInvoice['amount_paid'] / 100,
        ]);
    }

    /**
     * Handle failed payment.
     */
    protected function handlePaymentFailed(array $stripeInvoice): void
    {
        $invoice = Invoice::where('stripe_invoice_id', $stripeInvoice['id'])->first();
        
        if (!$invoice) {
            return;
        }

        // Log the failed payment attempt
        Log::warning('Invoice payment failed', [
            'invoice_id' => $invoice->id,
            'stripe_invoice_id' => $stripeInvoice['id'],
        ]);

        // TODO: Send notification to customer
    }

    /**
     * Handle invoice finalized.
     */
    protected function handleInvoiceFinalized(array $stripeInvoice): void
    {
        $invoice = Invoice::where('stripe_invoice_id', $stripeInvoice['id'])->first();
        
        if (!$invoice) {
            return;
        }

        $invoice->update([
            'status' => Invoice::STATUS_OPEN,
            'pdf_url' => $stripeInvoice['invoice_pdf'],
        ]);
    }

    /**
     * Handle invoice voided.
     */
    protected function handleInvoiceVoided(array $stripeInvoice): void
    {
        $invoice = Invoice::where('stripe_invoice_id', $stripeInvoice['id'])->first();
        
        if (!$invoice) {
            return;
        }

        $invoice->update(['status' => Invoice::STATUS_VOID]);
    }

    /**
     * Calculate due date based on payment terms.
     */
    protected function calculateDueDate(Company $company): \Carbon\Carbon
    {
        $paymentTerms = $company->payment_terms ?? 'net30';
        
        return match($paymentTerms) {
            'due_on_receipt' => now(),
            'net15' => now()->addDays(15),
            'net30' => now()->addDays(30),
            'net60' => now()->addDays(60),
            default => now()->addDays(30),
        };
    }

    /**
     * Get days until due based on payment terms.
     */
    protected function getDaysUntilDue(Company $company): int
    {
        $paymentTerms = $company->payment_terms ?? 'net30';
        
        return match($paymentTerms) {
            'due_on_receipt' => 0,
            'net15' => 15,
            'net30' => 30,
            'net60' => 60,
            default => 30,
        };
    }

    /**
     * Create manual invoice for additional services.
     */
    public function createManualInvoice(Company $company, array $items, ?int $branchId = null): ?Invoice
    {
        try {
            // Ensure Stripe customer
            $stripeCustomerId = $this->ensureStripeCustomer($company);
            if (!$stripeCustomerId) {
                throw new \Exception('Could not create Stripe customer');
            }

            // Create invoice
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'branch_id' => $branchId,
                'invoice_number' => Invoice::generateInvoiceNumber($company),
                'status' => Invoice::STATUS_DRAFT,
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'currency' => 'EUR',
                'invoice_date' => now(),
                'due_date' => $this->calculateDueDate($company),
                'billing_reason' => Invoice::REASON_MANUAL,
                'auto_advance' => false, // Manual invoices need review
            ]);

            // Create Stripe invoice
            $stripeInvoice = $this->stripe->invoices->create([
                'customer' => $stripeCustomerId,
                'collection_method' => 'send_invoice',
                'days_until_due' => $this->getDaysUntilDue($company),
                'metadata' => [
                    'company_id' => $company->id,
                    'invoice_id' => $invoice->id,
                    'type' => 'manual',
                ],
            ]);

            $invoice->update(['stripe_invoice_id' => $stripeInvoice->id]);

            // Add items
            $subtotal = 0;
            foreach ($items as $item) {
                $this->addInvoiceItem(
                    $invoice,
                    $stripeInvoice->id,
                    $item['type'] ?? InvoiceItem::TYPE_SERVICE,
                    $item['description'],
                    $item['quantity'] ?? 1,
                    $item['unit'] ?? 'Stück',
                    $item['unit_price']
                );
                $subtotal += ($item['quantity'] ?? 1) * $item['unit_price'];
            }

            // Update totals
            $taxAmount = $subtotal * 0.19; // 19% VAT
            $invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $subtotal + $taxAmount,
            ]);

            return $invoice;

        } catch (\Exception $e) {
            Log::error('Error creating manual invoice', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            
            if (isset($invoice)) {
                $invoice->delete();
            }
            
            return null;
        }
    }
}