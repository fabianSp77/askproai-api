<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RetellAiController extends Controller
{
    /**
     * Eingehenden Anruf behandeln und an retell.ai übermitteln
     */
    public function handleIncomingCall(Request $request)
    {
        $callerNumber = $request->input('callerNumber');
        $timestamp = $request->input('timestamp');

        $url = config('retellai.base_url');
        $apiKey = config('retellai.api_key');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Accept' => 'application/json',
        ])->post($url, [
            'from_number' => '+493041735870',  // Deine Retell.ai-Nummer
            'to_number' => $callerNumber,
            'override_agent_id' => 'agent_7fa6897c142d3060802ffb3285',
        ]);

        if ($response->successful()) {
            $responseData = $response->json();
            Log::info('✅ Retell.ai Call erfolgreich erstellt:', $responseData);

            return response()->json([
                'message' => 'Anruf erfolgreich an retell.ai übertragen',
                'callAccepted' => true,
                'data' => $responseData,
            ], 200);
        } else {
            Log::error('Retell.ai API Fehler:', ['response' => $response->body()]);

            return response()->json([
                'message' => 'Fehler bei Verbindung zu retell.ai',
                'error' => $response->body(),
            ], 500);
        }
    }

    /**
     * Webhook für eingehende Anrufe von Retell.ai
     */
    public function webhook(Request $request)
    {
        $data = $request->all();
        Log::info('✅ Eingehender Webhook von RetellAI:', $data);

        $responsePayload = [
            'call_inbound' => [
                'override_agent_id' => 'agent_7fa6897c142d3060802ffb3285',
                'dynamic_variables' => [
                    'customer_name' => 'Max Mustermann',
                ],
                'metadata' => [
                    'callHandledBy' => 'AskProAI Laravel Webhook',
                ],
            ],
        ];

        return response()->json($responsePayload, 200);
    }
}
