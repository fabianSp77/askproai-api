<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RetellWebhookController
{
    public function __invoke(Request $request): Response
    {
        // Log webhook event without sensitive data
        Log::info('Retell Webhook received', [
            'event' => $request->input('event'),
            'call_id' => $request->input('data.call_id'),
            'conversation_id' => $request->input('data.conversation_id'),
            'timestamp' => now()->toISOString()
        ]);

        return response()->noContent();   // 204
    }
}
