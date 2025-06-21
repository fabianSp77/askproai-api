<?php

namespace App\Services\MCP;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\StripeInvoiceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Carbon\Carbon;

class StripeMCPServer
{
    protected StripeClient $stripe;
    protected StripeInvoiceService $stripeInvoiceService;
    
    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
        $this->stripeInvoiceService = app(StripeInvoiceService::class);
    }
    
    /**
     * Get payment overview for a company
     */
    public function getPaymentOverview(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $period = $params['period'] ?? 'month';
        
        if (!$companyId) {
            return ['error' => 'Company ID required'];
        }
        
        $company = Company::find($companyId);
        if (!$company) {
            return ['error' => 'Company not found'];
        }
        
        return Cache::remember("stripe:overview:{$companyId}:{$period}", 300, function () use ($company, $period) {
            $startDate = $this->getStartDate($period);
            
            return [
                'revenue' => $this->calculateRevenue($company, $startDate),
                'subscriptions' => $this->getSubscriptionStats($company),
                'customers' => $this->getCustomerStats($company),
                'recent_charges' => $this->getRecentCharges($company, 10),
                'upcoming_invoices' => $this->getUpcomingInvoices($company),
                'payment_methods' => $this->getPaymentMethodStats($company),
            ];
        });
    }
    
    /**
     * Get customer payment details
     */
    public function getCustomerPayments(array $params): array
    {
        $customerId = $params['customer_id'] ?? null;
        $limit = $params['limit'] ?? 20;
        
        if (!$customerId) {
            return ['error' => 'Customer ID required'];
        }
        
        $customer = Customer::find($customerId);
        if (!$customer || !$customer->stripe_customer_id) {
            return ['error' => 'Customer not found or not connected to Stripe'];
        }
        
        try {
            $charges = $this->stripe->charges->all([
                'customer' => $customer->stripe_customer_id,
                'limit' => $limit,
            ]);
            
            $invoices = $this->stripe->invoices->all([
                'customer' => $customer->stripe_customer_id,
                'limit' => $limit,
            ]);
            
            return [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'stripe_id' => $customer->stripe_customer_id,
                ],
                'charges' => $this->formatCharges($charges->data),
                'invoices' => $this->formatInvoices($invoices->data),
                'payment_methods' => $this->getCustomerPaymentMethods($customer->stripe_customer_id),
                'balance' => $this->getCustomerBalance($customer->stripe_customer_id),
            ];
        } catch (\Exception $e) {
            Log::error('Stripe MCP: Failed to get customer payments', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            
            return ['error' => 'Failed to retrieve payment data'];
        }
    }
    
    /**
     * Create a new invoice
     */
    public function createInvoice(array $params): array
    {
        $customerId = $params['customer_id'] ?? null;
        $items = $params['items'] ?? [];
        $dueDate = $params['due_date'] ?? null;
        $metadata = $params['metadata'] ?? [];
        
        if (!$customerId || empty($items)) {
            return ['error' => 'Customer ID and items required'];
        }
        
        $customer = Customer::find($customerId);
        if (!$customer) {
            return ['error' => 'Customer not found'];
        }
        
        try {
            // Ensure customer exists in Stripe
            if (!$customer->stripe_customer_id) {
                // Create Stripe customer directly since the invoice service method is for companies
                $stripeCustomer = $this->stripe->customers->create([
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'metadata' => [
                        'customer_id' => $customer->id,
                        'company_id' => $customer->company_id,
                    ],
                ]);
                $customer->update(['stripe_customer_id' => $stripeCustomer->id]);
            }
            
            // Create invoice items
            foreach ($items as $item) {
                $this->stripe->invoiceItems->create([
                    'customer' => $customer->stripe_customer_id,
                    'amount' => $item['amount'] * 100, // Convert to cents
                    'currency' => 'eur',
                    'description' => $item['description'],
                    'metadata' => array_merge($metadata, [
                        'askproai_customer_id' => $customer->id,
                        'askproai_company_id' => $customer->company_id,
                    ]),
                ]);
            }
            
            // Create the invoice
            $stripeInvoice = $this->stripe->invoices->create([
                'customer' => $customer->stripe_customer_id,
                'collection_method' => 'send_invoice',
                'days_until_due' => $dueDate ? Carbon::parse($dueDate)->diffInDays(now()) : 30,
                'metadata' => $metadata,
            ]);
            
            // Send the invoice
            $this->stripe->invoices->sendInvoice($stripeInvoice->id);
            
            // Store in local database
            $invoice = Invoice::create([
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'stripe_invoice_id' => $stripeInvoice->id,
                'amount' => $stripeInvoice->amount_due / 100,
                'status' => $stripeInvoice->status,
                'due_date' => Carbon::createFromTimestamp($stripeInvoice->due_date),
                'invoice_pdf' => $stripeInvoice->invoice_pdf,
            ]);
            
            return [
                'success' => true,
                'invoice' => [
                    'id' => $invoice->id,
                    'stripe_id' => $stripeInvoice->id,
                    'amount' => $invoice->amount,
                    'status' => $invoice->status,
                    'due_date' => $invoice->due_date->format('Y-m-d'),
                    'pdf_url' => $stripeInvoice->invoice_pdf,
                    'hosted_url' => $stripeInvoice->hosted_invoice_url,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Stripe MCP: Failed to create invoice', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
            
            return ['error' => 'Failed to create invoice: ' . $e->getMessage()];
        }
    }
    
    /**
     * Process a refund
     */
    public function processRefund(array $params): array
    {
        $chargeId = $params['charge_id'] ?? null;
        $amount = $params['amount'] ?? null;
        $reason = $params['reason'] ?? 'requested_by_customer';
        
        if (!$chargeId) {
            return ['error' => 'Charge ID required'];
        }
        
        try {
            $refundData = [
                'charge' => $chargeId,
                'reason' => $reason,
                'metadata' => [
                    'refunded_by' => auth()->id(),
                    'refunded_at' => now()->toIso8601String(),
                ],
            ];
            
            if ($amount) {
                $refundData['amount'] = $amount * 100; // Convert to cents
            }
            
            $refund = $this->stripe->refunds->create($refundData);
            
            // Log the refund
            Log::info('Stripe MCP: Refund processed', [
                'refund_id' => $refund->id,
                'charge_id' => $chargeId,
                'amount' => $refund->amount / 100,
                'reason' => $reason,
            ]);
            
            return [
                'success' => true,
                'refund' => [
                    'id' => $refund->id,
                    'amount' => $refund->amount / 100,
                    'status' => $refund->status,
                    'reason' => $refund->reason,
                    'created' => Carbon::createFromTimestamp($refund->created)->toDateTimeString(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Stripe MCP: Failed to process refund', [
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);
            
            return ['error' => 'Failed to process refund: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get subscription details
     */
    public function getSubscription(array $params): array
    {
        $subscriptionId = $params['subscription_id'] ?? null;
        
        if (!$subscriptionId) {
            return ['error' => 'Subscription ID required'];
        }
        
        try {
            $subscription = $this->stripe->subscriptions->retrieve($subscriptionId);
            
            return [
                'subscription' => $this->formatSubscription($subscription),
                'upcoming_invoice' => $this->getUpcomingInvoiceForSubscription($subscriptionId),
                'usage' => $this->getSubscriptionUsage($subscription),
            ];
        } catch (\Exception $e) {
            Log::error('Stripe MCP: Failed to get subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            
            return ['error' => 'Failed to retrieve subscription'];
        }
    }
    
    /**
     * Update subscription
     */
    public function updateSubscription(array $params): array
    {
        $subscriptionId = $params['subscription_id'] ?? null;
        $action = $params['action'] ?? null;
        
        if (!$subscriptionId || !$action) {
            return ['error' => 'Subscription ID and action required'];
        }
        
        try {
            switch ($action) {
                case 'cancel':
                    $subscription = $this->stripe->subscriptions->cancel($subscriptionId);
                    break;
                    
                case 'pause':
                    $subscription = $this->stripe->subscriptions->update($subscriptionId, [
                        'pause_collection' => ['behavior' => 'void'],
                    ]);
                    break;
                    
                case 'resume':
                    $subscription = $this->stripe->subscriptions->update($subscriptionId, [
                        'pause_collection' => null,
                    ]);
                    break;
                    
                case 'change_plan':
                    $newPriceId = $params['price_id'] ?? null;
                    if (!$newPriceId) {
                        return ['error' => 'New price ID required for plan change'];
                    }
                    
                    $subscription = $this->stripe->subscriptions->retrieve($subscriptionId);
                    $subscription = $this->stripe->subscriptions->update($subscriptionId, [
                        'items' => [
                            [
                                'id' => $subscription->items->data[0]->id,
                                'price' => $newPriceId,
                            ],
                        ],
                    ]);
                    break;
                    
                default:
                    return ['error' => 'Invalid action'];
            }
            
            return [
                'success' => true,
                'subscription' => $this->formatSubscription($subscription),
            ];
        } catch (\Exception $e) {
            Log::error('Stripe MCP: Failed to update subscription', [
                'subscription_id' => $subscriptionId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            
            return ['error' => 'Failed to update subscription: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generate financial report
     */
    public function generateReport(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        $startDate = $params['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $params['end_date'] ?? now()->endOfDay()->toDateString();
        $type = $params['type'] ?? 'summary';
        
        if (!$companyId) {
            return ['error' => 'Company ID required'];
        }
        
        $company = Company::find($companyId);
        if (!$company) {
            return ['error' => 'Company not found'];
        }
        
        $report = [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'generated_at' => now()->toIso8601String(),
        ];
        
        switch ($type) {
            case 'detailed':
                $report['data'] = $this->generateDetailedReport($company, $startDate, $endDate);
                break;
                
            case 'tax':
                $report['data'] = $this->generateTaxReport($company, $startDate, $endDate);
                break;
                
            default:
                $report['data'] = $this->generateSummaryReport($company, $startDate, $endDate);
        }
        
        return $report;
    }
    
    /**
     * Calculate revenue for a period
     */
    protected function calculateRevenue(Company $company, Carbon $startDate): array
    {
        $revenue = [
            'total' => 0,
            'recurring' => 0,
            'one_time' => 0,
            'by_day' => [],
        ];
        
        // Get all successful charges for the period
        $charges = $this->stripe->charges->all([
            'created' => [
                'gte' => $startDate->timestamp,
            ],
            'limit' => 100,
        ]);
        
        foreach ($charges->data as $charge) {
            if ($charge->status === 'succeeded' && $charge->refunded === false) {
                $amount = $charge->amount / 100;
                $revenue['total'] += $amount;
                
                // Categorize revenue
                if (isset($charge->invoice)) {
                    $revenue['recurring'] += $amount;
                } else {
                    $revenue['one_time'] += $amount;
                }
                
                // Group by day
                $day = Carbon::createFromTimestamp($charge->created)->format('Y-m-d');
                $revenue['by_day'][$day] = ($revenue['by_day'][$day] ?? 0) + $amount;
            }
        }
        
        return $revenue;
    }
    
    /**
     * Get subscription statistics
     */
    protected function getSubscriptionStats(Company $company): array
    {
        $stats = [
            'active' => 0,
            'canceled' => 0,
            'trial' => 0,
            'mrr' => 0, // Monthly Recurring Revenue
        ];
        
        $subscriptions = $this->stripe->subscriptions->all(['limit' => 100]);
        
        foreach ($subscriptions->data as $subscription) {
            switch ($subscription->status) {
                case 'active':
                    $stats['active']++;
                    // Calculate MRR
                    foreach ($subscription->items->data as $item) {
                        $amount = ($item->price->unit_amount / 100);
                        if ($item->price->recurring->interval === 'year') {
                            $amount = $amount / 12;
                        }
                        $stats['mrr'] += $amount;
                    }
                    break;
                    
                case 'canceled':
                    $stats['canceled']++;
                    break;
                    
                case 'trialing':
                    $stats['trial']++;
                    break;
            }
        }
        
        return $stats;
    }
    
    /**
     * Get customer statistics
     */
    protected function getCustomerStats(Company $company): array
    {
        return [
            'total' => Customer::where('company_id', $company->id)
                ->whereNotNull('stripe_customer_id')
                ->count(),
            'new_this_month' => Customer::where('company_id', $company->id)
                ->whereNotNull('stripe_customer_id')
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'with_payment_method' => Customer::where('company_id', $company->id)
                ->whereNotNull('stripe_customer_id')
                ->whereNotNull('default_payment_method')
                ->count(),
        ];
    }
    
    /**
     * Get recent charges
     */
    protected function getRecentCharges(Company $company, int $limit): array
    {
        $charges = $this->stripe->charges->all(['limit' => $limit]);
        
        return $this->formatCharges($charges->data);
    }
    
    /**
     * Format charges for response
     */
    protected function formatCharges(array $charges): array
    {
        return array_map(function ($charge) {
            return [
                'id' => $charge->id,
                'amount' => $charge->amount / 100,
                'currency' => $charge->currency,
                'status' => $charge->status,
                'description' => $charge->description,
                'customer' => $charge->customer,
                'created' => Carbon::createFromTimestamp($charge->created)->toDateTimeString(),
                'refunded' => $charge->refunded,
                'payment_method' => $charge->payment_method_details->type ?? 'unknown',
            ];
        }, $charges);
    }
    
    /**
     * Format invoices for response
     */
    protected function formatInvoices(array $invoices): array
    {
        return array_map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'amount_due' => $invoice->amount_due / 100,
                'amount_paid' => $invoice->amount_paid / 100,
                'currency' => $invoice->currency,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date ? Carbon::createFromTimestamp($invoice->due_date)->format('Y-m-d') : null,
                'paid_at' => $invoice->paid_at ? Carbon::createFromTimestamp($invoice->paid_at)->toDateTimeString() : null,
                'pdf_url' => $invoice->invoice_pdf,
                'hosted_url' => $invoice->hosted_invoice_url,
            ];
        }, $invoices);
    }
    
    /**
     * Format subscription for response
     */
    protected function formatSubscription($subscription): array
    {
        return [
            'id' => $subscription->id,
            'status' => $subscription->status,
            'current_period_start' => Carbon::createFromTimestamp($subscription->current_period_start)->toDateTimeString(),
            'current_period_end' => Carbon::createFromTimestamp($subscription->current_period_end)->toDateTimeString(),
            'cancel_at_period_end' => $subscription->cancel_at_period_end,
            'items' => array_map(function ($item) {
                return [
                    'id' => $item->id,
                    'price_id' => $item->price->id,
                    'product_name' => $item->price->product->name ?? 'Unknown',
                    'amount' => $item->price->unit_amount / 100,
                    'interval' => $item->price->recurring->interval,
                    'quantity' => $item->quantity,
                ];
            }, $subscription->items->data),
        ];
    }
    
    /**
     * Get upcoming invoices
     */
    protected function getUpcomingInvoices(Company $company): array
    {
        $upcoming = [];
        
        // Get all active subscriptions
        $subscriptions = $this->stripe->subscriptions->all([
            'status' => 'active',
            'limit' => 100,
        ]);
        
        foreach ($subscriptions->data as $subscription) {
            try {
                $upcomingInvoice = $this->stripe->invoices->upcoming([
                    'subscription' => $subscription->id,
                ]);
                
                $upcoming[] = [
                    'subscription_id' => $subscription->id,
                    'amount' => $upcomingInvoice->amount_due / 100,
                    'date' => Carbon::createFromTimestamp($upcomingInvoice->period_end)->format('Y-m-d'),
                ];
            } catch (\Exception $e) {
                // Skip if no upcoming invoice
            }
        }
        
        return $upcoming;
    }
    
    /**
     * Get payment method statistics
     */
    protected function getPaymentMethodStats(Company $company): array
    {
        $stats = [
            'card' => 0,
            'sepa_debit' => 0,
            'bank_transfer' => 0,
            'other' => 0,
        ];
        
        // This would analyze payment methods used
        // Simplified for this implementation
        
        return $stats;
    }
    
    /**
     * Get customer payment methods
     */
    protected function getCustomerPaymentMethods(string $stripeCustomerId): array
    {
        try {
            $paymentMethods = $this->stripe->paymentMethods->all([
                'customer' => $stripeCustomerId,
                'type' => 'card',
            ]);
            
            return array_map(function ($method) {
                return [
                    'id' => $method->id,
                    'type' => $method->type,
                    'brand' => $method->card->brand,
                    'last4' => $method->card->last4,
                    'exp_month' => $method->card->exp_month,
                    'exp_year' => $method->card->exp_year,
                ];
            }, $paymentMethods->data);
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get customer balance
     */
    protected function getCustomerBalance(string $stripeCustomerId): float
    {
        try {
            $customer = $this->stripe->customers->retrieve($stripeCustomerId);
            return $customer->balance / 100;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get start date for period
     */
    protected function getStartDate(string $period): Carbon
    {
        switch ($period) {
            case 'week':
                return now()->startOfWeek();
            case 'month':
                return now()->startOfMonth();
            case 'quarter':
                return now()->startOfQuarter();
            case 'year':
                return now()->startOfYear();
            default:
                return now()->startOfMonth();
        }
    }
    
    /**
     * Get upcoming invoice for subscription
     */
    protected function getUpcomingInvoiceForSubscription(string $subscriptionId): ?array
    {
        try {
            $invoice = $this->stripe->invoices->upcoming([
                'subscription' => $subscriptionId,
            ]);
            
            return [
                'amount' => $invoice->amount_due / 100,
                'date' => Carbon::createFromTimestamp($invoice->period_end)->format('Y-m-d'),
                'items' => array_map(function ($item) {
                    return [
                        'description' => $item->description,
                        'amount' => $item->amount / 100,
                    ];
                }, $invoice->lines->data),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Get subscription usage
     */
    protected function getSubscriptionUsage($subscription): array
    {
        // This would get usage for metered billing
        // Simplified for this implementation
        return [
            'api_calls' => 0,
            'appointments' => 0,
            'phone_minutes' => 0,
        ];
    }
    
    /**
     * Generate summary report
     */
    protected function generateSummaryReport(Company $company, string $startDate, string $endDate): array
    {
        return [
            'revenue' => [
                'total' => 0,
                'subscriptions' => 0,
                'one_time' => 0,
            ],
            'customers' => [
                'new' => 0,
                'churned' => 0,
                'total' => 0,
            ],
            'transactions' => [
                'successful' => 0,
                'failed' => 0,
                'refunded' => 0,
            ],
        ];
    }
    
    /**
     * Generate detailed report
     */
    protected function generateDetailedReport(Company $company, string $startDate, string $endDate): array
    {
        // Would include transaction-level details
        return [];
    }
    
    /**
     * Generate tax report
     */
    protected function generateTaxReport(Company $company, string $startDate, string $endDate): array
    {
        // Would calculate tax-relevant data
        return [
            'gross_revenue' => 0,
            'tax_collected' => 0,
            'net_revenue' => 0,
            'by_tax_rate' => [],
        ];
    }
    
    /**
     * Test Stripe connection
     */
    public function testConnection(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        if (!$companyId) {
            return ['error' => 'Company ID is required'];
        }
        
        try {
            $company = Company::find($companyId);
            if (!$company) {
                return ['error' => 'Company not found'];
            }
            
            if (!$company->stripe_customer_id) {
                return [
                    'connected' => false,
                    'message' => 'No Stripe customer ID configured'
                ];
            }
            
            // Test connection by retrieving customer
            $customer = $this->stripe->customers->retrieve($company->stripe_customer_id);
            
            return [
                'connected' => true,
                'message' => 'Stripe connection successful',
                'customer' => [
                    'id' => $customer->id,
                    'email' => $customer->email,
                    'created' => Carbon::createFromTimestamp($customer->created)->toDateTimeString(),
                    'balance' => $customer->balance / 100,
                    'currency' => $customer->currency ?? 'eur',
                ],
                'company' => $company->name,
                'tested_at' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Stripe testConnection error', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'connected' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage()
            ];
        }
    }
}