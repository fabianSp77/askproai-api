<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session as Checkout;
use App\Services\WebhookProcessor;
use App\Models\WebhookEvent;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    protected WebhookProcessor $webhookProcessor;
    
    public function __construct(WebhookProcessor $webhookProcessor)
    {
        $this->webhookProcessor = $webhookProcessor;
        // Auth middleware only for checkout, not for webhook
        $this->middleware('auth:sanctum')->except(['webhook']);
    }

    // 3a)  Checkout-Link (GET /billing/checkout)
    public function checkout(Request $r)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Checkout::create([
            'payment_method_types' => ['card'],
            'mode'   => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => 5000, // 50 € in Cent
                    'product_data' => ['name' => 'Prepaid-Guthaben 50 €'],
                ],
                'quantity' => 1,
            ]],
            'success_url' => url('/billing/success'),
            'cancel_url'  => url('/billing/cancel'),
            'metadata'    => ['tenant_id' => $r->user()->tenant_id],
        ]);

        return redirect($session->url);
    }

    // 3b)  Webhook-Endpoint  POST /billing/webhook
    public function webhook(Request $r)
    {
        $correlationId = $r->input('correlation_id') ?? app('correlation_id');
        
        // Parse the raw JSON payload since Stripe sends raw JSON
        $payload = json_decode($r->getContent(), true);
        
        if (!$payload) {
            Log::error('Invalid Stripe billing webhook payload', [
                'correlation_id' => $correlationId
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);
        }
        
        $headers = $r->headers->all();
        
        try {
            // Process webhook through the WebhookProcessor service
            $result = $this->webhookProcessor->process(
                WebhookEvent::PROVIDER_STRIPE,
                $payload,
                $headers,
                $correlationId
            );
            
            // Return appropriate response
            if ($result['duplicate']) {
                return response('OK', 200); // Stripe expects simple OK response
            }
            
            return response('OK', 200);
            
        } catch (\App\Exceptions\WebhookSignatureException $e) {
            Log::error('Stripe billing webhook signature verification failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            
            return response('Invalid', 400);
            
        } catch (\Exception $e) {
            Log::error('Error processing Stripe billing webhook', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return OK to prevent Stripe from retrying
            return response('OK', 200);
        }
    }
}
