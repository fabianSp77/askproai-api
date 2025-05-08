<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\RetellWebhook;
use Illuminate\Http\Request;

class RetellWebhookController extends Controller
{
    public function __invoke(Request $r)
    {
        $payload    = $r->all();
        $eventType  = $payload['event_type']    ?? 'unknown';
        $callId     = $payload['call_id']       ?? ($payload['conversation_id'] ?? null);

        RetellWebhook::create([
            'event_type' => $eventType,
            'call_id'    => $callId,
            'payload'    => $payload,
        ]);

        return response()->json(['status' => 'ok']);
    }
}
