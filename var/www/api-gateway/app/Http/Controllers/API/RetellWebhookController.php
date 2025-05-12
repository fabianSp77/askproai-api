<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Call;

class RetellWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Validiere Webhook-Anfragen von Retell.ai
        $payload = $request->all();
        
        // Log Webhook-Daten für Debugging
        Log::info('Retell Webhook received', ['payload' => $payload]);
        
        // Überprüfe Event-Typ
        $eventType = $request->input('event_type');
        
        switch ($eventType) {
            case 'call.started':
                return $this->handleCallStarted($payload);
                
            case 'call.ended':
                return $this->handleCallEnded($payload);
                
            case 'call.transcription':
                return $this->handleCallTranscription($payload);
                
            default:
                Log::info('Unbekannter Event-Typ: ' . $eventType);
                return response()->json(['status' => 'acknowledged', 'event' => $eventType]);
        }
    }
    
    private function handleCallStarted($payload)
    {
        try {
            // Call-Record erstellen
            $call = Call::create([
                'call_id' => $payload['call_id'] ?? null,
                'call_status' => 'started',
                'phone_number' => $payload['phone_number'] ?? null,
                'call_time' => now(),
                'type' => 'retell',
                'raw_data' => $payload
            ]);
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Call started event processed',
                'call_id' => $call->id
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler beim Verarbeiten des call.started Events', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    private function handleCallEnded($payload)
    {
        try {
            // Call-Record aktualisieren
            $call = Call::where('call_id', $payload['call_id'] ?? '')->first();
            
            if ($call) {
                $call->update([
                    'call_status' => 'ended',
                    'call_duration' => $payload['duration'] ?? null,
                    'disconnect_reason' => $payload['disconnect_reason'] ?? null,
                    'successful' => isset($payload['successful']) ? (bool)$payload['successful'] : true,
                    'raw_data' => array_merge($call->raw_data ?? [], $payload)
                ]);
            } else {
                // Fall: Call nicht gefunden, neuen Record erstellen
                $call = Call::create([
                    'call_id' => $payload['call_id'] ?? null,
                    'call_status' => 'ended',
                    'phone_number' => $payload['phone_number'] ?? null,
                    'call_time' => now(),
                    'call_duration' => $payload['duration'] ?? null,
                    'type' => 'retell',
                    'disconnect_reason' => $payload['disconnect_reason'] ?? null,
                    'successful' => isset($payload['successful']) ? (bool)$payload['successful'] : true,
                    'raw_data' => $payload
                ]);
            }
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Call ended event processed',
                'call_id' => $call->id
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler beim Verarbeiten des call.ended Events', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    private function handleCallTranscription($payload)
    {
        try {
            // Call-Record aktualisieren
            $call = Call::where('call_id', $payload['call_id'] ?? '')->first();
            
            if ($call) {
                $call->update([
                    'transcript' => $payload['transcript'] ?? null,
                    'summary' => $payload['summary'] ?? null,
                    'user_sentiment' => $payload['user_sentiment'] ?? null,
                    'raw_data' => array_merge($call->raw_data ?? [], $payload)
                ]);
            } else {
                // Fall: Call nicht gefunden, neuen Record erstellen
                $call = Call::create([
                    'call_id' => $payload['call_id'] ?? null,
                    'call_status' => 'transcribed',
                    'phone_number' => $payload['phone_number'] ?? null,
                    'call_time' => now(),
                    'type' => 'retell',
                    'transcript' => $payload['transcript'] ?? null,
                    'summary' => $payload['summary'] ?? null,
                    'user_sentiment' => $payload['user_sentiment'] ?? null,
                    'raw_data' => $payload
                ]);
            }
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Call transcription event processed',
                'call_id' => $call->id
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler beim Verarbeiten des call.transcription Events', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
