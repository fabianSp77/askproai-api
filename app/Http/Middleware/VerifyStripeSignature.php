<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class VerifyStripeSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');
        
        if (!$signature) {
            Log::warning('Stripe webhook received without signature', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json(['error' => 'Missing signature'], 400);
        }
        
        if (!$secret) {
            Log::error('Stripe webhook secret not configured');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }
        
        try {
            // Verify the webhook signature
            $event = Webhook::constructEvent($payload, $signature, $secret);
            
            // Add the verified event to the request for use in controllers
            $request->merge(['stripe_event' => $event]);
            
            Log::info('Stripe webhook signature verified', [
                'event_id' => $event->id,
                'event_type' => $event->type,
            ]);
            
            return $next($request);
            
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
                'signature' => $signature,
                'ip' => $request->ip(),
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 400);
            
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook payload parsing failed', [
                'error' => $e->getMessage(),
                'payload_size' => strlen($payload),
            ]);
            
            return response()->json(['error' => 'Invalid payload'], 400);
            
        } catch (\Exception $e) {
            Log::error('Unexpected error in Stripe webhook verification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }
}