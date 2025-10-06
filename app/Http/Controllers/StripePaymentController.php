<?php

namespace App\Http\Controllers;

use App\Models\BalanceTopup;
use App\Models\Tenant;
use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Exception;

class StripePaymentController extends Controller
{
    protected $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Erstelle Payment Intent für Guthaben-Aufladung
     */
    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10|max:10000',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        try {
            $tenant = Tenant::findOrFail($request->tenant_id);
            $amount = $request->amount;

            // Erstelle Payment Intent
            $paymentIntent = $this->balanceService->createStripePaymentIntent($tenant, $amount);

            // Erstelle Topup-Eintrag
            $topup = $this->balanceService->processTopup($tenant, $amount, [
                'payment_intent_id' => $paymentIntent->id,
                'payment_method' => 'stripe',
            ]);

            return response()->json([
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'topup_id' => $topup->id,
                'amount' => $amount,
                'bonus' => $topup->bonus_amount,
                'total' => $topup->total_credited,
            ]);
        } catch (Exception $e) {
            Log::error('Payment Intent creation failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $request->tenant_id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Zahlung konnte nicht initialisiert werden.',
            ], 500);
        }
    }

    /**
     * Handle Stripe Webhook
     * Note: Signature verification is handled by VerifyStripeWebhookSignature middleware
     */
    public function handleWebhook(Request $request)
    {
        // Get the verified event from the middleware
        $event = $request->input('stripe_event');

        if (!$event) {
            Log::error('Stripe event not found in request');
            return response()->json(['error' => 'Invalid request'], 400);
        }

        Log::info('Stripe webhook received', [
            'type' => $event['type'],
            'id' => $event['id'],
        ]);

        try {
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSuccess($event['data']['object']);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event['data']['object']);
                    break;

                case 'charge.refunded':
                    $this->handleRefund($event['data']['object']);
                    break;

                default:
                    Log::info('Unhandled webhook event type', ['type' => $event['type']]);
            }

            return response('Webhook handled', 200);
        } catch (Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'event' => $event['type'],
            ]);

            return response('Webhook processing failed', 500);
        }
    }

    /**
     * Handle successful payment
     */
    protected function handlePaymentSuccess($paymentIntent)
    {
        // Finde Topup anhand Payment Intent ID
        $topup = BalanceTopup::where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if (!$topup) {
            Log::error('Topup not found for payment intent', [
                'payment_intent_id' => $paymentIntent['id'],
            ]);
            return;
        }

        if ($topup->status === 'completed') {
            Log::info('Topup already completed', ['topup_id' => $topup->id]);
            return;
        }

        // Bestätige Zahlung und schreibe Guthaben gut
        $this->balanceService->confirmPayment($topup, [
            'charge_id' => $paymentIntent['latest_charge'],
            'metadata' => $paymentIntent['metadata'] ?? [],
        ]);

        Log::info('Payment confirmed and balance credited', [
            'topup_id' => $topup->id,
            'amount' => $topup->total_credited,
        ]);
    }

    /**
     * Handle failed payment
     */
    protected function handlePaymentFailed($paymentIntent)
    {
        $topup = BalanceTopup::where('stripe_payment_intent_id', $paymentIntent['id'])->first();

        if (!$topup) {
            return;
        }

        $topup->update([
            'status' => 'failed',
            'failure_reason' => $paymentIntent['last_payment_error']['message'] ?? 'Zahlung fehlgeschlagen',
        ]);

        Log::warning('Payment failed', [
            'topup_id' => $topup->id,
            'error' => $paymentIntent['last_payment_error']['message'] ?? 'Unknown',
        ]);
    }

    /**
     * Handle refund
     */
    protected function handleRefund($charge)
    {
        $topup = BalanceTopup::where('stripe_charge_id', $charge['id'])->first();

        if (!$topup) {
            return;
        }

        $refundAmount = $charge['amount_refunded'] / 100; // Convert from cents

        // Prozessiere Rückerstattung
        $this->balanceService->processRefund(
            $topup,
            $refundAmount,
            'Stripe Refund'
        );

        Log::info('Refund processed', [
            'topup_id' => $topup->id,
            'amount' => $refundAmount,
        ]);
    }
}