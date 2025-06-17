<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StripeInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    protected StripeInvoiceService $invoiceService;

    public function __construct(StripeInvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Handle Stripe webhook.
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            // Verify webhook signature
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid Stripe webhook payload', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid Stripe webhook signature', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', [
            'type' => $event['type'],
            'id' => $event['id']
        ]);

        // Handle the event
        try {
            $this->invoiceService->processWebhook($event);
            
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error processing Stripe webhook', [
                'type' => $event['type'],
                'error' => $e->getMessage()
            ]);
            
            // Return success to prevent Stripe from retrying
            return response()->json(['status' => 'error logged']);
        }
    }
}