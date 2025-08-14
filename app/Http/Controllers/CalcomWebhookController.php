<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        /* ───── TEMP-DEBUG – Header & Body in calcom-Channel loggen ───── */
        Log::channel('calcom')->info('[Debug] headers', $request->headers->all());
        Log::channel('calcom')->info('[Debug] body', ['raw' => $request->getContent()]);
        /* ──────────────────────────────────────────────────────────────── */

        // TODO: Event-Typen auswerten (booking.created …)
        Log::channel('calcom')->info('[Cal.com] Webhook-Event', $request->all());

        return response()->json(['received' => true]);
    }
}
