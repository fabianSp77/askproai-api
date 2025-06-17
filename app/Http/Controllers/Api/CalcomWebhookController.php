<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Appointment;
use App\Jobs\ProcessCalcomWebhookJob;
use Illuminate\Support\Facades\Log;

class CalcomWebhookController extends Controller
{
    /**
     * Handle incoming Cal.com webhook
     */
    public function handle(Request $request)
    {
        Log::info('Cal.com webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all()
        ]);
        
        try {
            $payload = $request->all();
            
            // Cal.com sendet verschiedene Event-Typen
            $triggerEvent = $payload['triggerEvent'] ?? null;
            
            if (!$triggerEvent) {
                Log::warning('Cal.com webhook missing triggerEvent', $payload);
                return response()->json(['error' => 'Missing triggerEvent'], 400);
            }
            
            // Dispatch job for async processing
            ProcessCalcomWebhookJob::dispatch($triggerEvent, $payload);
            
            return response()->json(['status' => 'accepted'], 200);
            
        } catch (\Exception $e) {
            Log::error('Cal.com webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Verify webhook signature
     */
    protected function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Cal-Signature-256');
        
        if (!$signature) {
            return false;
        }
        
        $secret = config('services.calcom.webhook_secret');
        if (!$secret) {
            // If no secret is configured, accept all (not recommended for production)
            Log::warning('Cal.com webhook secret not configured');
            return true;
        }
        
        $payload = $request->getContent();
        $calculated = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($calculated, $signature);
    }
}