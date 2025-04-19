<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Kunde;
use App\Models\Call;

class RetellWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();

        Log::info('🔔 Retell.ai Webhook erhalten', ['payload' => $payload]);

        try {
            $telefonnummer = $payload['call']['to_number'] ?? null;

            if (!$telefonnummer) {
                throw new \Exception('Webhook enthält keine Ziel-Telefonnummer.');
            }

            $kunde = Kunde::where('telefonnummer', $telefonnummer)->first();

            if (!$kunde) {
                throw new \Exception("Kein Kunde gefunden für Telefonnummer: {$telefonnummer}");
            }

            Call::create([
                'kunde_id' => $kunde->id,
                'from_number' => $payload['call']['from_number'],
                'to_number' => $telefonnummer,
                'status' => $payload['call']['status'] ?? 'unbekannt',
                'payload' => json_encode($payload),
            ]);

            Log::info('✅ Webhook erfolgreich verarbeitet', [
                'kunde_id' => $kunde->id,
                'telefonnummer' => $telefonnummer,
            ]);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('❌ Fehler bei Webhook-Verarbeitung', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
