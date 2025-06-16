<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Call;

class RetellWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // Logge den kompletten eingehenden Payload immer mit!
        Log::debug('RETELL: RAW-REQUEST', [
            'content_type' => $request->header('content-type'),
            'body'         => $request->getContent(),
            'json()'       => $request->json()->all(),
            'input()'      => $request->input(),
        ]);
        $data    = $request->json()->all();
        $event   = $data['event'] ?? ($data['payload']['event'] ?? null);
        $callId  = $data['call_id'] ?? ($data['payload']['call_id'] ?? null);

        Log::debug('RETELL: EVENT-KEYS', [
            'event'    => $event,
            'call_id'  => $callId,
            'all_keys' => array_keys($data),
        ]);

        switch ($event) {
            case 'call_inbound':
                Log::info('RETELL: call_inbound angekommen', $data);
                return response()->json(['message' => 'call_inbound empfangen'], 200);

            case 'call_started':
                Log::info('RETELL: call_started', $data);
                $call = Call::where('call_id', $callId)->first();
                if (!$call) {
                    Call::create([
                        'call_id'      => $callId,
                        'retell_call_id'=> $data['retell_call_id'] ?? null,
                        'from_number'  => $data['from_number'] ?? null,
                        'to_number'    => $data['to_number'] ?? null,
                        'call_status'  => 'in_progress',
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                    Log::info('RETELL: Call angelegt', ['call_id' => $callId]);
                } else {
                    Log::info('RETELL: Call bereits vorhanden (kein neues Anlegen)', ['call_id' => $callId]);
                }
                return response()->json(['message' => 'call_started verarbeitet'], 200);

            case 'call_ended':
                Log::info('RETELL: call_ended', $data);
                $call = Call::where('call_id', $callId)->first();
                if ($call) {
                    $call->update([
                        'call_status'  => 'completed',
                        'duration_sec' => $data['duration_sec'] ?? null,
                        'cost_cents'   => $data['cost_cents'] ?? null,
                        'updated_at'   => now(),
                    ]);
                    Log::info('RETELL: Call auf completed gesetzt', ['call_id' => $callId]);
                } else {
                    Call::create([
                        'call_id'      => $callId,
                        'from_number'  => $data['from_number'] ?? null,
                        'to_number'    => $data['to_number'] ?? null,
                        'call_status'  => 'completed',
                        'duration_sec' => $data['duration_sec'] ?? null,
                        'cost_cents'   => $data['cost_cents'] ?? null,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);
                    Log::warning('RETELL: Call_ended ohne vorherigen Call, neu angelegt!', ['call_id' => $callId]);
                }
                return response()->json(['message' => 'call_ended verarbeitet'], 200);

            case 'call_analyzed':
                Log::info('RETELL: call_analyzed', $data);
                $call = Call::where('call_id', $callId)->first();
                if ($call) {
                    $call->update([
                        'transcript'   => $data['call_analysis']['transcript'] ?? null,
                        'analysis'     => $data['call_analysis'] ?? null,
                        'call_status'  => 'analyzed',
                        'updated_at'   => now(),
                    ]);
                    Log::info('RETELL: Call mit Transkript/Analyse aktualisiert', ['call_id' => $callId]);
                } else {
                    Log::warning('RETELL: call_analyzed ohne vorherigen Call!', ['call_id' => $callId]);
                }
                return response()->json(['message' => 'call_analyzed verarbeitet'], 200);

            default:
                Log::warning('RETELL: Unbekanntes/unsupported Event', [
                    'event'   => $event,
                    'payload' => $data,
                ]);
                return response()->json(['message' => 'Unbekanntes/unsupported Event', 'event' => $event], 400);
        }
    }
}
