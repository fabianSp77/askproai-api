<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RetellConversationEndedController
{
    public function __invoke(Request $request): Response
    {
        // Log conversation ended event without sensitive data
        Log::info('Retell Conversation Ended', [
            'conversation_id' => $request->input('data.conversation_id'),
            'call_id' => $request->input('data.call_id'),
            'event' => $request->input('event'),
            'timestamp' => now()->toISOString()
        ]);

        return response()->noContent();   // 204
    }
}
