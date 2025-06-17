<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Invoice;

class ProcessStripeWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $eventType = $this->payload['type'] ?? null;
        $eventData = $this->payload['data']['object'] ?? [];

        Log::info('Processing Stripe webhook job', [
            'event_type' => $eventType,
            'event_id' => $this->payload['id'] ?? null
        ]);

        try {
            switch ($eventType) {
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($eventData);
                    break;
                    
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($eventData);
                    break;
                    
                case 'invoice.payment_succeeded':
                    $this->handleInvoicePaymentSucceeded($eventData);
                    break;
                    
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                case 'customer.subscription.deleted':
                    $this->handleSubscriptionEvent($eventType, $eventData);
                    break;
                    
                case 'charge.failed':
                    $this->handleChargeFailed($eventData);
                    break;
                    
                default:
                    Log::info('Unhandled Stripe event type', ['type' => $eventType]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Stripe webhook', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Handle checkout session completed events
     */
    private function handleCheckoutSessionCompleted(array $data): void
    {
        $tenantId = $data['metadata']['tenant_id'] ?? null;
        $companyId = $data['metadata']['company_id'] ?? null;
        $amount = $data['amount_total'] ?? 0; // Amount in cents
        
        // Handle legacy tenant-based billing
        if ($tenantId && $tenant = Tenant::find($tenantId)) {
            $tenant->increment('balance_cents', $amount);
            
            Log::info('Tenant balance updated via checkout', [
                'tenant_id' => $tenantId,
                'amount_cents' => $amount
            ]);
        }
        
        // Handle company-based billing
        if ($companyId && $company = Company::find($companyId)) {
            $company->increment('prepaid_balance', $amount / 100); // Convert to euros
            
            // Create payment record
            Payment::create([
                'company_id' => $companyId,
                'amount' => $amount / 100,
                'currency' => $data['currency'] ?? 'eur',
                'status' => 'completed',
                'payment_method' => 'stripe',
                'stripe_payment_intent_id' => $data['payment_intent'] ?? null,
                'stripe_session_id' => $data['id'],
                'metadata' => [
                    'type' => 'prepaid_credit',
                    'description' => 'Prepaid credit purchase'
                ]
            ]);
            
            Log::info('Company prepaid balance updated via checkout', [
                'company_id' => $companyId,
                'amount_euros' => $amount / 100
            ]);
        }
    }

    /**
     * Handle payment intent succeeded events
     */
    private function handlePaymentIntentSucceeded(array $data): void
    {
        $companyId = $data['metadata']['company_id'] ?? null;
        
        if (!$companyId) {
            Log::warning('Payment intent succeeded without company_id', [
                'payment_intent_id' => $data['id']
            ]);
            return;
        }
        
        // Check if payment already processed
        $existingPayment = Payment::where('stripe_payment_intent_id', $data['id'])->first();
        if ($existingPayment) {
            Log::info('Payment already processed', ['payment_intent_id' => $data['id']]);
            return;
        }
        
        // Create payment record
        Payment::create([
            'company_id' => $companyId,
            'amount' => $data['amount'] / 100, // Convert cents to euros
            'currency' => $data['currency'],
            'status' => 'completed',
            'payment_method' => 'stripe',
            'stripe_payment_intent_id' => $data['id'],
            'metadata' => $data['metadata'] ?? []
        ]);
        
        Log::info('Payment recorded', [
            'company_id' => $companyId,
            'amount' => $data['amount'] / 100,
            'payment_intent_id' => $data['id']
        ]);
    }

    /**
     * Handle invoice payment succeeded events
     */
    private function handleInvoicePaymentSucceeded(array $data): void
    {
        $invoiceId = $data['metadata']['invoice_id'] ?? null;
        
        if (!$invoiceId || !$invoice = Invoice::find($invoiceId)) {
            Log::warning('Invoice payment succeeded without valid invoice_id', [
                'stripe_invoice_id' => $data['id']
            ]);
            return;
        }
        
        // Update invoice status
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => 'stripe',
            'stripe_invoice_id' => $data['id']
        ]);
        
        // Create payment record
        Payment::create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'amount' => $data['amount_paid'] / 100,
            'currency' => $data['currency'],
            'status' => 'completed',
            'payment_method' => 'stripe',
            'stripe_charge_id' => $data['charge'],
            'metadata' => [
                'invoice_number' => $invoice->invoice_number,
                'billing_period' => $invoice->billing_period
            ]
        ]);
        
        Log::info('Invoice marked as paid', [
            'invoice_id' => $invoiceId,
            'amount' => $data['amount_paid'] / 100
        ]);
    }

    /**
     * Handle subscription events
     */
    private function handleSubscriptionEvent(string $eventType, array $data): void
    {
        $companyId = $data['metadata']['company_id'] ?? null;
        
        if (!$companyId || !$company = Company::find($companyId)) {
            Log::warning('Subscription event without valid company_id', [
                'event_type' => $eventType,
                'subscription_id' => $data['id']
            ]);
            return;
        }
        
        switch ($eventType) {
            case 'customer.subscription.created':
                $company->update([
                    'subscription_status' => 'active',
                    'stripe_subscription_id' => $data['id'],
                    'subscription_plan' => $data['items']['data'][0]['price']['id'] ?? null
                ]);
                break;
                
            case 'customer.subscription.deleted':
                $company->update([
                    'subscription_status' => 'cancelled',
                    'subscription_cancelled_at' => now()
                ]);
                break;
                
            case 'customer.subscription.updated':
                $status = $data['status'] ?? 'active';
                $company->update([
                    'subscription_status' => $status,
                    'subscription_plan' => $data['items']['data'][0]['price']['id'] ?? null
                ]);
                break;
        }
        
        Log::info('Subscription event processed', [
            'event_type' => $eventType,
            'company_id' => $companyId,
            'subscription_id' => $data['id']
        ]);
    }

    /**
     * Handle charge failed events
     */
    private function handleChargeFailed(array $data): void
    {
        $companyId = $data['metadata']['company_id'] ?? null;
        
        if ($companyId) {
            // Log the failed charge
            Log::warning('Charge failed for company', [
                'company_id' => $companyId,
                'amount' => $data['amount'] / 100,
                'failure_message' => $data['failure_message'] ?? 'Unknown error'
            ]);
            
            // Could trigger notifications here
        }
    }
}