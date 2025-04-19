<?php

namespace App\Http\Controllers;

use App\Models\Call;
use App\Models\Kunde;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RetellWebhookController extends Controller
{
    /**
     * Verarbeitet eingehende Webhooks von Retell.ai
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request)
    {
        // Log des eingehenden Requests
        Log::info('Retell.ai Webhook empfangen', [
            'call_id' => $request->input('call_id'),
            'phone_number' => $request->input('phone_number')
        ]);

        try {
            // Grundlegende Validierung
            $validatedData = $request->validate([
                'call_id' => 'required|string',
                'phone_number' => 'nullable|string',
                'duration' => 'nullable|integer|min:0',
                'transcript' => 'nullable|string',
                'summary' => 'nullable|string',
                'user_sentiment' => 'nullable|string',
                'disconnect_reason' => 'nullable|string',
            ]);

            // Den kompletten Request als Basis für die Datenerstellung verwenden
            $callData = [
                'call_id' => $request->input('call_id'),
                'call_status' => $request->input('status'),
                'user_sentiment' => $request->input('user_sentiment'),
                'successful' => $request->input('call_successful', true),
                'call_time' => now(),
                'call_duration' => $request->input('duration'),
                'type' => $request->input('type'),
                'cost' => $request->input('cost'),
                'phone_number' => $request->input('phone_number'),
                'name' => $request->input('name') ?? $request->input('_name'),
                'email' => $request->input('email') ?? $request->input('_email'),
                'summary' => $request->input('summary'),
                'transcript' => $request->input('transcript'),
                'disconnect_reason' => $request->input('disconnect_reason'),
                'raw_data' => $request->all(), // Speichert alle Rohdaten für spätere Analyse
            ];

            // Prüfe, ob bereits ein Call mit dieser ID existiert
            $existingCall = Call::where('call_id', $request->input('call_id'))->first();
            
            if ($existingCall) {
                $existingCall->update($callData);
                $call = $existingCall;
                Log::info('Retell.ai Call aktualisiert', ['call_id' => $call->id]);
            } else {
                $call = Call::create($callData);
                Log::info('Retell.ai Call erstellt', ['call_id' => $call->id]);
            }

            // Kunde suchen oder erstellen, wenn Telefonnummer vorhanden
            if ($request->input('phone_number')) {
                $this->associateCustomer($call, $request);
            }

            // Erfolgreiche Antwort zurückgeben
            return response()->json([
                'success' => true,
                'call_id' => $call->id,
                'message' => 'Webhook erfolgreich verarbeitet'
            ]);
        } catch (\Exception $e) {
            // Detailliertes Logging im Fehlerfall
            Log::error('Retell.ai Webhook Fehler: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            // Fehlermeldung zurückgeben
            return response()->json([
                'success' => false, 
                'error' => 'Interner Serverfehler: ' . $e->getMessage()
            ], 500);
        }
    }

private function associateCustomer(Call $call, Request $request)
{
    try {
        // Telefonnummer normalisieren (nur Ziffern)
        $phone = preg_replace('/[^0-9+]/', '', $request->input('phone_number'));

        // Kunden mit dieser Telefonnummer suchen
        $kunde = Kunde::where('telefonnummer', 'like', '%' . $phone . '%')->first();

        // Wenn kein Kunde gefunden, erstelle einen neuen, falls genügend Daten vorhanden
        if (!$kunde && ($request->input('name') || $request->input('_name'))) {
            $kunde = Kunde::create([
                'name' => $request->input('name') ?? $request->input('_name') ?? 'Unbekannt',
                'email' => $request->input('email') ?? $request->input('_email'),
                'telefonnummer' => $phone
            ]);

            Log::info('Neuer Kunde aus Retell.ai Call erstellt', ['kunde_id' => $kunde->id]);
        }

        // Wenn ein Kunde gefunden oder erstellt wurde, verknüpfe ihn mit dem Anruf
        if ($kunde) {
            $call->kunde_id = $kunde->id;  // 🔥 Hier aktivieren
            $call->save();                 // 🔥 Hier aktivieren

            Log::info('Kunde mit Call verknüpft', [
                'call_id' => $call->id,
                'kunde_id' => $kunde->id
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Fehler bei Kundenverknüpfung: ' . $e->getMessage(), [
            'exception' => $e,
            'call_id' => $call->id
        ]);
        // Fehler hier nicht werfen, damit der Hauptprozess fortgesetzt werden kann
    }
}

}
