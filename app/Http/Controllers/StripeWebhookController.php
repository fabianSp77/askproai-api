<?php

namespace App\Http\Controllers;

use App\Services\StripeTopupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    protected StripeTopupService $stripeService;

    public function __construct()
    {
        $this->stripeService = app(StripeTopupService::class);
    }

    /**
     * Handle Stripe webhook
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe webhook: Invalid payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Stripe webhook: Invalid signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->stripeService->handlePaymentSuccess($paymentIntent->id);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->stripeService->handlePaymentFailed(
                    $paymentIntent->id,
                    $paymentIntent->last_payment_error->message ?? 'Unknown error'
                );
                break;

            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->stripeService->handleCheckoutSessionCompleted($session->id);
                break;

            case 'charge.refunded':
                // Handle refunds if needed
                $charge = $event->data->object;
                Log::info('Stripe charge refunded', [
                    'charge_id' => $charge->id,
                    'amount_refunded' => $charge->amount_refunded,
                ]);
                break;

            default:
                // Unexpected event type
                Log::info('Stripe webhook: Unhandled event type', ['type' => $event->type]);
        }

        return response('Webhook handled', 200);
    }
}