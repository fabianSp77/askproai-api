<?php

namespace App\Http\Controllers;

use App\Jobs\RefreshCallDataJob;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RetellConversationEndedController
{
    public function __invoke(Request $request): Response
    {
        /** Retell sendet:
         *  {
         *    "event":"call_ended",
         *    "call": { … alle Felder … }
         *  }
         */
        $payload = $request->input('call', []);   //  ➜ nur das call-Objekt
        Log::info('Retell-conversation-payload', $payload);

        // === Datensatz anlegen / aktualisieren ==========================
        $call = Call::updateOrCreate(
            ['call_id' => $payload['call_id'] ?? null],
            [
                'call_status'     => $payload['call_status']    ?? null,
                'duration_sec'    => isset($payload['start_timestamp'], $payload['end_timestamp'])
                    ? intval(($payload['end_timestamp'] - $payload['start_timestamp']) / 1000)
                    : null,
                'call_successful' => $payload['disconnection_reason'] === 'completed',
                // analysis & transcript kommen später
            ],
        );

        // === Analyse & Transkript im Hintergrund nachladen ==============
        RefreshCallDataJob::dispatch($call);

        return response()->noContent();   // 204
    }
}
