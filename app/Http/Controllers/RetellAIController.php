<?php

namespace App\Http\Controllers;

use App\Services\RetellAIService;
use Illuminate\Http\Request;

class RetellAIController extends Controller
{
    protected $retellService;

    public function __construct(RetellAIService $retellService)
    {
        $this->retellService = $retellService;
    }

    public function getCalls(Request $request)
    {
        $limit = $request->input('limit', 10);
        $calls = $this->retellService->getCalls($limit);
        
        return response()->json($calls);
    }

    public function getCallDetails($callId)
    {
        $callDetails = $this->retellService->getCallDetails($callId);
        
        return response()->json($callDetails);
    }

    public function getCallTranscript($callId)
    {
        $transcript = $this->retellService->getCallTranscript($callId);
        
        return response()->json($transcript);
    }

    /**
     * Handle incoming call and forward to retell.ai (from RetellAiController)
     */
    public function handleIncomingCall(Request $request)
    {
        $callerNumber = $request->input('callerNumber');
        $timestamp = $request->input('timestamp');

        try {
            $response = $this->retellService->createCall([
                'from_number' => '+493041735870',
                'to_number' => $callerNumber,
                'override_agent_id' => 'agent_7fa6897c142d3060802ffb3285'
            ]);

            return response()->json([
                'message' => 'Anruf erfolgreich an retell.ai übertragen',
                'callAccepted' => true,
                'data' => $response,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler bei Verbindung zu retell.ai',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook for incoming calls from Retell.ai (from RetellAiController)
     */
    public function webhook(Request $request)
    {
        $data = $request->all();
        \Log::info('✅ Eingehender Webhook von RetellAI:', $data);

        $responsePayload = [
            "call_inbound" => [
                "override_agent_id" => "agent_7fa6897c142d3060802ffb3285",
                "dynamic_variables" => [
                    "customer_name" => "Max Mustermann"
                ],
                "metadata" => [
                    "callHandledBy" => "AskProAI Laravel Webhook"
                ]
            ]
        ];

        return response()->json($responsePayload, 200);
    }
}
