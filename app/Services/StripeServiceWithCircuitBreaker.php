<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\BillingPeriod;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use App\Services\CircuitBreaker\CircuitBreakerManager;
use App\Services\TaxService;
use App\Services\InvoiceComplianceService;

/**
 * Enhanced Stripe Service with Circuit Breaker protection
 * 
 * Wraps all Stripe API calls with circuit breaker pattern for fault tolerance
 */
class StripeServiceWithCircuitBreaker extends StripeInvoiceService
{
    protected CircuitBreakerManager $circuitBreaker;
    
    public function __construct()
    {
        parent::__construct();
        $this->circuitBreaker = CircuitBreakerManager::getInstance();
    }
    
    /**
     * Create or update Stripe customer with circuit breaker protection
     */
    public function ensureStripeCustomer(Company $company): ?string
    {
        return $this->circuitBreaker->call('stripe', 
            function() use ($company) {
                return parent::ensureStripeCustomer($company);
            },
            function($exception) use ($company) {
                Log::error('Stripe circuit breaker open, using fallback', [
                    'company_id' => $company->id,
                    'error' => $exception->getMessage()
                ]);
                
                // Fallback: Return existing stripe_customer_id if available
                return $company->stripe_customer_id;
            }
        );
    }
    
    /**
     * Create invoice with circuit breaker protection
     */
    public function createInvoiceForBillingPeriod(BillingPeriod $billingPeriod): ?Invoice
    {
        return $this->circuitBreaker->call('stripe',
            function() use ($billingPeriod) {
                return parent::createInvoiceForBillingPeriod($billingPeriod);
            },
            function($exception) use ($billingPeriod) {
                Log::error('Stripe circuit breaker open for invoice creation', [
                    'billing_period_id' => $billingPeriod->id,
                    'error' => $exception->getMessage()
                ]);
                
                // Fallback: Create local invoice only
                return $this->createLocalInvoiceOnly($billingPeriod);
            }
        );
    }
    
    /**
     * Create payment intent with circuit breaker protection
     */
    public function createPaymentIntent(Invoice $invoice): ?string
    {
        return $this->circuitBreaker->call('stripe',
            function() use ($invoice) {
                $paymentIntent = $this->stripe->paymentIntents->create([
                    'amount' => $invoice->total * 100, // Convert to cents
                    'currency' => strtolower($invoice->currency),
                    'customer' => $invoice->company->stripe_customer_id,
                    'metadata' => [
                        'invoice_id' => $invoice->id,
                        'company_id' => $invoice->company_id,
                    ],
                ]);
                
                return $paymentIntent->client_secret;
            },
            function($exception) use ($invoice) {
                Log::error('Stripe payment intent creation failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $exception->getMessage()
                ]);
                
                return null;
            }
        );
    }
    
    
    /**
     * Check if Stripe service is available
     */
    public function isAvailable(): bool
    {
        return $this->circuitBreaker->isAvailable('stripe');
    }
    
    /**
     * Get Stripe service health status
     */
    public function getHealthStatus(): array
    {
        $status = $this->circuitBreaker->getAllStatus()['stripe'] ?? [];
        
        // Add Stripe-specific health checks
        $status['api_reachable'] = $this->checkApiReachability();
        $status['webhook_endpoint_active'] = $this->checkWebhookEndpoint();
        
        return $status;
    }
    
    /**
     * Create local invoice only (fallback when Stripe is down)
     */
    protected function createLocalInvoiceOnly(BillingPeriod $billingPeriod): Invoice
    {
        $company = $billingPeriod->company;
        
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'branch_id' => $billingPeriod->branch_id,
            'invoice_number' => $this->complianceService->generateCompliantInvoiceNumber($company),
            'status' => Invoice::STATUS_DRAFT,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'currency' => 'EUR',
            'invoice_date' => now(),
            'due_date' => $this->calculateDueDate($company),
            'billing_reason' => Invoice::REASON_SUBSCRIPTION_CYCLE,
            'notes' => 'Created offline due to Stripe unavailability',
            'metadata' => [
                'created_offline' => true,
                'stripe_sync_pending' => true,
            ],
        ]);
        
        // Queue for later Stripe sync
        \App\Jobs\SyncInvoiceToStripeJob::dispatch($invoice)->delay(now()->addMinutes(5));
        
        return $invoice;
    }
    
    /**
     * Check if Stripe API is reachable
     */
    protected function checkApiReachability(): bool
    {
        try {
            $this->stripe->balance->retrieve();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if webhook endpoint is active
     */
    protected function checkWebhookEndpoint(): bool
    {
        try {
            $endpoints = $this->stripe->webhookEndpoints->all(['limit' => 1]);
            return $endpoints->data[0]->status === 'enabled' ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Calculate due date based on company payment terms
     */
    protected function calculateDueDate(Company $company): \Carbon\Carbon
    {
        $paymentTerms = $company->payment_terms ?? 14;
        return now()->addDays($paymentTerms);
    }
    
    /**
     * Get days until due for Stripe invoice
     */
    protected function getDaysUntilDue(Company $company): int
    {
        return $company->payment_terms ?? 14;
    }
    
    /**
     * Create customer portal session with circuit breaker protection
     *
     * @param Company $company
     * @return string|null The portal session URL
     */
    public function createCustomerPortalSession(Company $company): ?string
    {
        return $this->circuitBreaker->call('stripe',
            function() use ($company) {
                if (!$company->stripe_customer_id) {
                    return null;
                }
                
                try {
                    $session = $this->stripe->billingPortal->sessions->create([
                        'customer' => $company->stripe_customer_id,
                        'return_url' => url('/admin/customer-billing-dashboard'),
                    ]);
                    
                    return $session->url;
                } catch (\Exception $e) {
                    Log::error('Failed to create customer portal session', [
                        'company_id' => $company->id,
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }
            },
            function($exception) use ($company) {
                Log::error('Stripe circuit breaker open for portal session', [
                    'company_id' => $company->id,
                    'error' => $exception->getMessage()
                ]);
                return null;
            }
        );
    }
    
    /**
     * Retry invoice payment with circuit breaker protection
     *
     * @param string $invoiceId
     * @return array
     */
    public function retryInvoicePayment(string $invoiceId): array
    {
        return $this->circuitBreaker->call('stripe',
            function() use ($invoiceId) {
                try {
                    // First get the invoice to check its status
                    $invoice = $this->stripe->invoices->retrieve($invoiceId);
                    
                    if ($invoice->status === 'paid') {
                        return [
                            'paid' => true,
                            'invoice' => $invoice->toArray()
                        ];
                    }
                    
                    // Attempt to pay the invoice
                    $paidInvoice = $this->stripe->invoices->pay($invoiceId, [
                        'forgive' => false, // Don't forgive the invoice if payment fails
                        'off_session' => true // Attempt payment without customer present
                    ]);
                    
                    return [
                        'paid' => true,
                        'invoice' => $paidInvoice->toArray()
                    ];
                    
                } catch (\Stripe\Exception\CardException $e) {
                    // Card was declined
                    return [
                        'paid' => false,
                        'error' => $e->getMessage(),
                        'error_code' => $e->getStripeCode(),
                        'decline_code' => $e->getDeclineCode()
                    ];
                } catch (\Stripe\Exception\ApiErrorException $e) {
                    // Other Stripe API error
                    return [
                        'paid' => false,
                        'error' => $e->getMessage(),
                        'error_code' => $e->getStripeCode()
                    ];
                }
            },
            function($exception) use ($invoiceId) {
                Log::error('Stripe circuit breaker open for invoice retry', [
                    'invoice_id' => $invoiceId,
                    'error' => $exception->getMessage()
                ]);
                
                return [
                    'paid' => false,
                    'error' => 'Service temporarily unavailable',
                    'circuit_breaker_open' => true
                ];
            }
        );
    }
}