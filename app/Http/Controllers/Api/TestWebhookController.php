<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TestWebhookController extends Controller
{
    /**
     * Test endpoint to verify webhook connectivity
     */
    public function test(Request $request)
    {
        $timestamp = now()->toISOString();
        
        // Log all incoming data
        Log::info('TEST WEBHOOK RECEIVED', [
            'timestamp' => $timestamp,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        // Return detailed response
        return response()->json([
            'success' => true,
            'message' => 'Test webhook received successfully',
            'timestamp' => $timestamp,
            'server_time' => [
                'utc' => Carbon::now()->toISOString(),
                'berlin' => Carbon::now('Europe/Berlin')->format('Y-m-d H:i:s'),
                'timezone' => 'Europe/Berlin'
            ],
            'request_info' => [
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'has_signature' => $request->hasHeader('X-Retell-Signature'),
                'signature' => $request->header('X-Retell-Signature') ?? null,
                'body_size' => strlen($request->getContent()),
                'has_body' => !empty($request->all())
            ],
            'debug' => [
                'headers_received' => array_keys($request->headers->all()),
                'body_keys' => array_keys($request->all())
            ]
        ], 200);
    }
    
    /**
     * Simulate a Retell webhook for testing
     */
    public function simulateRetellWebhook(Request $request)
    {
        $testData = [
            'event' => $request->input('event', 'call_ended'),
            'call' => [
                'call_id' => 'test_' . uniqid(),
                'agent_id' => $request->input('agent_id', 'agent_9a8202a740cd3120d96fcfda1e'),
                'from_number' => $request->input('from_number', '+491234567890'),
                'to_number' => $request->input('to_number', '+493083793369'),
                'direction' => 'inbound',
                'call_duration' => 120,
                'start_timestamp' => now()->subMinutes(2)->timestamp * 1000,
                'end_timestamp' => now()->timestamp * 1000,
                'disconnection_reason' => 'customer_ended_call',
                'retell_llm_dynamic_variables' => [
                    'appointment_data' => [
                        'datum' => 'morgen',
                        'uhrzeit' => '15:00',
                        'name' => 'Test Kunde',
                        'telefonnummer' => '+491234567890',
                        'dienstleistung' => 'Haarschnitt',
                        'email' => 'test@example.com'
                    ]
                ]
            ]
        ];
        
        // Send to actual webhook endpoint
        $response = app()->handle(
            Request::create(
                '/api/retell/webhook',
                'POST',
                $testData,
                [],
                [],
                [
                    'HTTP_X_RETELL_SIGNATURE' => 'test_signature',
                    'CONTENT_TYPE' => 'application/json'
                ]
            )
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Test webhook sent',
            'test_data' => $testData,
            'response_status' => $response->getStatusCode(),
            'response_body' => json_decode($response->getContent(), true)
        ]);
    }
}