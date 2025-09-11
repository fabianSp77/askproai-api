<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CalcomWebhookController extends Controller
{
    /**
     * GET /api/calcom/webhook
     * Cal.com-Ping (keine Signatur-Prüfung).
     */
    public function ping(): JsonResponse
    {
        return response()->json(['ping' => 'ok']);
    }

    /**
     * POST /api/calcom/webhook
     * Wird von Cal.com aufgerufen – Signatur prüft unsere Middleware.
     */
    public function handle(Request $request): JsonResponse
    {
        // Log webhook event without sensitive data
        $eventData = $request->only(['triggerEvent', 'createdAt']);
        if ($request->has('payload')) {
            $eventData['payload_type'] = $request->input('payload.type', 'unknown');
            $eventData['booking_id'] = $request->input('payload.bookingId');
        }
        
        Log::channel('calcom')->info('[Cal.com] Webhook received', $eventData);

        return response()->json(['received' => true]);
    }
}
