<?php

namespace App\Services\Logging;

use Illuminate\Support\Facades\Log;

class StructuredLogger
{
    public function logApiCall($service, $method, $params, $duration = null)
    {
        Log::info('API Call', [
            'service' => $service,
            'method' => $method,
            'params' => $params,
            'duration' => $duration
        ]);
    }
    
    public function logApiResponse($service, $method, $response, $statusCode = 200)
    {
        Log::info('API Response', [
            'service' => $service,
            'method' => $method,
            'response' => $response,
            'status_code' => $statusCode
        ]);
    }
    
    public function logWebhookReceived($type, $payload)
    {
        Log::info('Webhook Received', [
            'type' => $type,
            'payload' => $payload
        ]);
    }
    
    public function logApiError($service, $method, $error)
    {
        Log::error('API Error', [
            'service' => $service,
            'method' => $method,
            'error' => $error
        ]);
    }
}
