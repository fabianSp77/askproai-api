<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessRetellCallEndedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RetellConversationEndedController
{
    public function __invoke(Request $request): Response
    {
        Log::info('Retell Conversation Ended webhook received', [
            'event' => $request->input('event'),
            'call_id' => $request->input('call.call_id'),
            'headers' => $request->headers->all()
        ]);

        try {
            // Validate webhook structure
            if (!$request->has('event') || $request->input('event') !== 'call_ended') {
                Log::warning('Invalid Retell webhook event', [
                    'event' => $request->input('event')
                ]);
                return response()->json(['error' => 'Invalid event type'], 400);
            }

            // Dispatch job for async processing
            ProcessRetellCallEndedJob::dispatch($request->all())
                ->onQueue('webhooks')
                ->delay(now()->addSeconds(1));

            return response()->noContent();   // 204
            
        } catch (\Exception $e) {
            Log::error('Failed to process Retell conversation ended webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Still return success to avoid retries
            return response()->noContent();
        }
    }
}
