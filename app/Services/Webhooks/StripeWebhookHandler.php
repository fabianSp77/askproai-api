<?php

namespace App\Services\Webhooks;

use App\Models\WebhookEvent;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\StripeInvoiceService;
use Carbon\Carbon;

class StripeWebhookHandler extends BaseWebhookHandler
{
    protected StripeInvoiceService $invoiceService;
    
    public function __construct(StripeInvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }
    
    /**
     * Get supported event types
     *
     * @return array
     */
    public function getSupportedEvents(): array
    {
        return [
            'checkout.session.completed',
            'customer.created',
            'customer.updated',
            'customer.deleted',
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'invoice.created',
            'invoice.finalized',
            'invoice.paid',
            'invoice.payment_failed',
            'invoice.payment_succeeded',
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'payment_method.attached',
            'payment_method.detached'
        ];
    }
    
    /**
     * Override handle method to pass event object directly to invoice service
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    public function handle(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $this->logContext = [
            'provider' => $webhookEvent->provider,
            'event_type' => $webhookEvent->event_type,
            'webhook_event_id' => $webhookEvent->id,
            'correlation_id' => $correlationId
        ];
        
        $this->logInfo('Processing Stripe webhook event');
        
        // Check if event type is supported
        if (!$this->supportsEvent($webhookEvent->event_type)) {
            $this->logWarning('Unsupported event type');
            return [
                'success' => true,
                'message' => 'Event type not supported by handler',
                'skipped' => true
            ];
        }
        
        return $this->withCorrelationId($correlationId, function () use ($webhookEvent, $correlationId) {
            // Convert payload to Stripe event object format
            $stripeEvent = (object) $webhookEvent->payload;
            
            // Process through invoice service
            $result = $this->invoiceService->processWebhook($stripeEvent);
            
            // Route to specific handler for additional processing
            $method = $this->getHandlerMethod($webhookEvent->event_type);
            
            if (method_exists($this, $method)) {
                $additionalResult = $this->$method($webhookEvent, $correlationId);
                $result = array_merge($result, $additionalResult);
            }
            
            return $result;
        });
    }
    
    /**
     * Handle checkout.session.completed event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleCheckoutSessionCompleted(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $session = $webhookEvent->payload['data']['object'] ?? [];
        
        $this->logInfo('Processing checkout session completed', [
            'session_id' => $session['id'] ?? null,
            'customer_id' => $session['customer'] ?? null
        ]);
        
        // Find company by Stripe customer ID
        $company = Company::where('stripe_customer_id', $session['customer'] ?? null)->first();
        
        if (!$company) {
            $this->logWarning('Company not found for Stripe customer', [
                'stripe_customer_id' => $session['customer'] ?? null
            ]);
            return [
                'success' => true,
                'message' => 'Company not found'
            ];
        }
        
        // Update subscription status if needed
        if (isset($session['subscription'])) {
            $company->update([
                'stripe_subscription_id' => $session['subscription'],
                'subscription_status' => 'active',
                'subscription_started_at' => now(),
                'metadata' => array_merge($company->metadata ?? [], [
                    'last_checkout_session' => $session['id'],
                    'checkout_completed_at' => now()->toIso8601String()
                ])
            ]);
            
            $this->logInfo('Company subscription updated', [
                'company_id' => $company->id,
                'subscription_id' => $session['subscription']
            ]);
        }
        
        return [
            'success' => true,
            'company_id' => $company->id,
            'message' => 'Checkout session processed'
        ];
    }
    
    /**
     * Handle invoice.paid event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleInvoicePaid(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $invoice = $webhookEvent->payload['data']['object'] ?? [];
        
        $this->logInfo('Processing invoice paid', [
            'invoice_id' => $invoice['id'] ?? null,
            'amount' => $invoice['amount_paid'] ?? 0
        ]);
        
        // Find local invoice record
        $localInvoice = Invoice::where('stripe_invoice_id', $invoice['id'])->first();
        
        if (!$localInvoice) {
            $this->logWarning('Local invoice not found', [
                'stripe_invoice_id' => $invoice['id']
            ]);
            
            // Try to create invoice from Stripe data
            $company = Company::where('stripe_customer_id', $invoice['customer'])->first();
            
            if ($company) {
                $localInvoice = $this->createInvoiceFromStripe($company, $invoice, $correlationId);
            }
        }
        
        if ($localInvoice) {
            // Update invoice status
            $localInvoice->update([
                'status' => 'paid',
                'paid_at' => Carbon::createFromTimestamp($invoice['status_transitions']['paid_at'] ?? time()),
                'payment_method' => $invoice['payment_method'] ?? null,
                'metadata' => array_merge($localInvoice->metadata ?? [], [
                    'stripe_data' => $invoice,
                    'paid_via_webhook' => true,
                    'correlation_id' => $correlationId
                ])
            ]);
            
            // Create payment record
            Payment::create([
                'company_id' => $localInvoice->company_id,
                'invoice_id' => $localInvoice->id,
                'amount' => $invoice['amount_paid'] / 100, // Convert from cents
                'currency' => strtoupper($invoice['currency']),
                'payment_method' => $invoice['payment_method'] ?? 'stripe',
                'stripe_payment_intent_id' => $invoice['payment_intent'] ?? null,
                'status' => 'completed',
                'paid_at' => Carbon::createFromTimestamp($invoice['status_transitions']['paid_at'] ?? time()),
                'metadata' => [
                    'stripe_invoice_id' => $invoice['id'],
                    'correlation_id' => $correlationId
                ]
            ]);
            
            $this->logInfo('Invoice marked as paid', [
                'invoice_id' => $localInvoice->id,
                'amount' => $invoice['amount_paid'] / 100
            ]);
        }
        
        return [
            'success' => true,
            'invoice_id' => $localInvoice->id ?? null,
            'message' => 'Invoice paid event processed'
        ];
    }
    
    /**
     * Handle invoice.payment_failed event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleInvoicePaymentFailed(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $invoice = $webhookEvent->payload['data']['object'] ?? [];
        
        $this->logInfo('Processing invoice payment failed', [
            'invoice_id' => $invoice['id'] ?? null,
            'attempt_count' => $invoice['attempt_count'] ?? 0
        ]);
        
        // Find local invoice
        $localInvoice = Invoice::where('stripe_invoice_id', $invoice['id'])->first();
        
        if ($localInvoice) {
            $localInvoice->update([
                'status' => 'payment_failed',
                'metadata' => array_merge($localInvoice->metadata ?? [], [
                    'payment_failed_at' => now()->toIso8601String(),
                    'attempt_count' => $invoice['attempt_count'] ?? 0,
                    'next_payment_attempt' => $invoice['next_payment_attempt'] 
                        ? Carbon::createFromTimestamp($invoice['next_payment_attempt'])->toIso8601String()
                        : null
                ])
            ]);
            
            // TODO: Send payment failure notification
            $this->logInfo('Invoice payment failed notification would be sent', [
                'invoice_id' => $localInvoice->id,
                'company_id' => $localInvoice->company_id
            ]);
        }
        
        return [
            'success' => true,
            'invoice_id' => $localInvoice->id ?? null,
            'message' => 'Invoice payment failed event processed'
        ];
    }
    
    /**
     * Handle customer.subscription.updated event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleCustomerSubscriptionUpdated(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $subscription = $webhookEvent->payload['data']['object'] ?? [];
        
        $this->logInfo('Processing subscription updated', [
            'subscription_id' => $subscription['id'] ?? null,
            'status' => $subscription['status'] ?? null
        ]);
        
        // Find company by Stripe customer ID
        $company = Company::where('stripe_customer_id', $subscription['customer'])->first();
        
        if ($company) {
            $company->update([
                'stripe_subscription_id' => $subscription['id'],
                'subscription_status' => $subscription['status'],
                'subscription_current_period_end' => isset($subscription['current_period_end'])
                    ? Carbon::createFromTimestamp($subscription['current_period_end'])
                    : null,
                'metadata' => array_merge($company->metadata ?? [], [
                    'subscription_updated_at' => now()->toIso8601String(),
                    'subscription_items' => $subscription['items']['data'] ?? [],
                    'cancel_at_period_end' => $subscription['cancel_at_period_end'] ?? false
                ])
            ]);
            
            $this->logInfo('Company subscription updated', [
                'company_id' => $company->id,
                'new_status' => $subscription['status']
            ]);
            
            // Handle status-specific actions
            switch ($subscription['status']) {
                case 'canceled':
                case 'unpaid':
                    // TODO: Disable company access
                    $this->logWarning('Company subscription cancelled/unpaid', [
                        'company_id' => $company->id
                    ]);
                    break;
                    
                case 'past_due':
                    // TODO: Send payment reminder
                    $this->logWarning('Company subscription past due', [
                        'company_id' => $company->id
                    ]);
                    break;
            }
        }
        
        return [
            'success' => true,
            'company_id' => $company->id ?? null,
            'message' => 'Subscription updated'
        ];
    }
    
    /**
     * Handle customer.subscription.deleted event
     *
     * @param WebhookEvent $webhookEvent
     * @param string $correlationId
     * @return array
     */
    protected function handleCustomerSubscriptionDeleted(WebhookEvent $webhookEvent, string $correlationId): array
    {
        $subscription = $webhookEvent->payload['data']['object'] ?? [];
        
        $this->logInfo('Processing subscription deleted', [
            'subscription_id' => $subscription['id'] ?? null
        ]);
        
        // Find company
        $company = Company::where('stripe_customer_id', $subscription['customer'])->first();
        
        if ($company) {
            $company->update([
                'subscription_status' => 'cancelled',
                'subscription_ended_at' => now(),
                'metadata' => array_merge($company->metadata ?? [], [
                    'subscription_cancelled_at' => now()->toIso8601String(),
                    'cancellation_details' => $subscription['cancellation_details'] ?? []
                ])
            ]);
            
            // TODO: Disable company access after grace period
            $this->logWarning('Company subscription cancelled', [
                'company_id' => $company->id
            ]);
        }
        
        return [
            'success' => true,
            'company_id' => $company->id ?? null,
            'message' => 'Subscription deleted'
        ];
    }
    
    /**
     * Create invoice from Stripe data
     *
     * @param Company $company
     * @param array $stripeInvoice
     * @param string $correlationId
     * @return Invoice
     */
    protected function createInvoiceFromStripe(Company $company, array $stripeInvoice, string $correlationId): Invoice
    {
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'stripe_invoice_id' => $stripeInvoice['id'],
            'number' => $stripeInvoice['number'] ?? 'STRIPE-' . $stripeInvoice['id'],
            'status' => $this->mapStripeStatus($stripeInvoice['status']),
            'amount_due' => $stripeInvoice['amount_due'] / 100,
            'amount_paid' => $stripeInvoice['amount_paid'] / 100,
            'currency' => strtoupper($stripeInvoice['currency']),
            'due_date' => isset($stripeInvoice['due_date']) 
                ? Carbon::createFromTimestamp($stripeInvoice['due_date'])
                : null,
            'paid_at' => isset($stripeInvoice['status_transitions']['paid_at'])
                ? Carbon::createFromTimestamp($stripeInvoice['status_transitions']['paid_at'])
                : null,
            'metadata' => [
                'created_from_webhook' => true,
                'correlation_id' => $correlationId,
                'stripe_data' => $stripeInvoice
            ]
        ]);
        
        // Create invoice items
        foreach ($stripeInvoice['lines']['data'] ?? [] as $line) {
            $invoice->items()->create([
                'description' => $line['description'] ?? 'Subscription',
                'quantity' => $line['quantity'] ?? 1,
                'unit_price' => $line['unit_amount'] / 100,
                'total' => $line['amount'] / 100,
                'metadata' => [
                    'stripe_line_item' => $line
                ]
            ]);
        }
        
        return $invoice;
    }
    
    /**
     * Map Stripe invoice status to internal status
     *
     * @param string $stripeStatus
     * @return string
     */
    protected function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'draft' => 'draft',
            'open' => 'pending',
            'paid' => 'paid',
            'void' => 'cancelled',
            'uncollectible' => 'failed',
            default => 'pending'
        };
    }
}