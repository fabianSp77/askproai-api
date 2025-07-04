<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\RetellCustomFunctionsController;

/*
|--------------------------------------------------------------------------
| Retell Test Routes
|--------------------------------------------------------------------------
|
| These routes are for testing Retell webhook integration and data flow.
| They provide detailed logging and monitoring capabilities.
|
*/

// Retell Custom Functions
Route::prefix('retell/functions')->group(function () {
    Route::post('/check-customer', [RetellCustomFunctionsController::class, 'checkCustomer'])
        ->name('retell.functions.check-customer');
    Route::post('/collect-appointment', [RetellCustomFunctionsController::class, 'collectAppointment'])
        ->name('retell.functions.collect-appointment');
    Route::post('/check-availability', [RetellCustomFunctionsController::class, 'checkAvailability'])
        ->name('retell.functions.check-availability');
    Route::post('/book-appointment', [RetellCustomFunctionsController::class, 'bookAppointment'])
        ->name('retell.functions.book-appointment');
    Route::post('/cancel-appointment', [RetellCustomFunctionsController::class, 'cancelAppointment'])
        ->name('retell.functions.cancel-appointment');
    Route::post('/reschedule-appointment', [RetellCustomFunctionsController::class, 'rescheduleAppointment'])
        ->name('retell.functions.reschedule-appointment');
    Route::post('/current-time-berlin', [RetellCustomFunctionsController::class, 'currentTimeBerlin'])
        ->name('retell.functions.current-time-berlin');
    Route::post('/book-simple', [RetellCustomFunctionsController::class, 'bookAppointmentSimple'])
        ->name('retell.functions.book-simple');
});

// Test webhook endpoint with detailed logging
Route::post('/retell/test-webhook', function (Request $request) {
    $timestamp = now()->format('Y-m-d_H-i-s-u');
    $requestId = uniqid('retell_test_', true);
    
    $logData = [
        'request_id' => $requestId,
        'timestamp' => $timestamp,
        'headers' => $request->headers->all(),
        'body' => $request->all(),
        'raw_body' => $request->getContent(),
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'signature' => $request->header('x-retell-signature'),
        'content_type' => $request->header('content-type'),
    ];
    
    // Parse event type if available
    $event = $request->input('event');
    $callData = $request->input('call', []);
    
    // Create structured log entry
    $structuredLog = [
        'request_id' => $requestId,
        'event_type' => $event,
        'call_id' => $callData['call_id'] ?? null,
        'from_number' => $callData['from_number'] ?? null,
        'to_number' => $callData['to_number'] ?? null,
        'direction' => $callData['direction'] ?? null,
        'timestamp' => $timestamp,
    ];
    
    // Save to dedicated test log file
    $logFilePath = "retell-test-webhooks/webhook_{$event}_{$timestamp}.json";
    Storage::put($logFilePath, json_encode($logData, JSON_PRETTY_PRINT));
    
    // Log to Laravel log with structured data
    Log::channel('retell')->info("Test Webhook Received: {$event}", $structuredLog);
    
    // Console output for real-time monitoring
    if (app()->runningInConsole()) {
        echo "\n🔔 Webhook Received: {$event}\n";
        echo "   Request ID: {$requestId}\n";
        echo "   Call ID: " . ($callData['call_id'] ?? 'N/A') . "\n";
        echo "   From: " . ($callData['from_number'] ?? 'N/A') . "\n";
        echo "   To: " . ($callData['to_number'] ?? 'N/A') . "\n";
        echo "   Timestamp: {$timestamp}\n";
        echo "   Log saved to: {$logFilePath}\n";
        echo str_repeat('-', 50) . "\n";
    }
    
    // Extract appointment data if present
    if ($event === 'call_ended') {
        $appointmentData = null;
        
        // Check multiple locations for appointment data
        if (isset($callData['retell_llm_dynamic_variables'])) {
            $appointmentData = $callData['retell_llm_dynamic_variables'];
        } elseif (isset($callData['custom_analysis_data'])) {
            $appointmentData = $callData['custom_analysis_data'];
        }
        
        if ($appointmentData) {
            Log::channel('retell')->info('Appointment data found in webhook', [
                'request_id' => $requestId,
                'appointment_data' => $appointmentData
            ]);
        }
    }
    
    return response()->json([
        'status' => 'logged',
        'request_id' => $requestId,
        'timestamp' => $timestamp,
        'event' => $event,
        'log_file' => $logFilePath
    ]);
})->name('retell.test.webhook');

// Test webhook validation endpoint
Route::post('/retell/test-signature', function (Request $request) {
    $signature = $request->header('x-retell-signature');
    $body = $request->getContent();
    $secret = config('services.retell.webhook_secret');
    
    // Parse signature format
    $isValid = false;
    $details = [];
    
    if (preg_match('/v=(\d+),d=(.+)/', $signature, $matches)) {
        $timestamp = $matches[1];
        $providedSignature = $matches[2];
        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $body, $secret);
        $isValid = hash_equals($expectedSignature, $providedSignature);
        
        $details = [
            'format' => 'v=timestamp,d=signature',
            'timestamp' => $timestamp,
            'timestamp_age_seconds' => time() - $timestamp,
            'provided_signature' => $providedSignature,
            'expected_signature' => $expectedSignature,
            'signatures_match' => $isValid
        ];
    }
    
    Log::channel('retell')->info('Signature validation test', [
        'is_valid' => $isValid,
        'details' => $details
    ]);
    
    return response()->json([
        'valid' => $isValid,
        'details' => $details
    ]);
})->name('retell.test.signature');

// Test custom function endpoint
Route::post('/retell/test-function', function (Request $request) {
    $functionName = $request->input('function_name');
    $arguments = $request->input('arguments');
    $callId = $request->input('call_id');
    
    Log::channel('retell')->info('Test function call received', [
        'function_name' => $functionName,
        'arguments' => $arguments,
        'call_id' => $callId
    ]);
    
    // Simulate different function responses
    switch ($functionName) {
        case 'collect_appointment':
            // Simulate appointment collection
            $date = $arguments['date'] ?? null;
            $time = $arguments['time'] ?? null;
            $service = $arguments['service'] ?? null;
            
            // Convert German date format if needed
            if ($date && preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{4})/', $date, $matches)) {
                $date = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
            }
            
            return response()->json([
                'result' => 'success',
                'message' => "Test: Termin wurde für {$date} um {$time} Uhr erfasst",
                'data' => [
                    'date' => $date,
                    'time' => $time,
                    'service' => $service,
                    'test_mode' => true
                ]
            ]);
            
        case 'check_availability':
            // Simulate availability check
            return response()->json([
                'result' => 'success',
                'available_slots' => ['09:00', '10:00', '14:00', '15:00'],
                'test_mode' => true
            ]);
            
        default:
            return response()->json([
                'result' => 'error',
                'message' => "Unknown function: {$functionName}",
                'test_mode' => true
            ]);
    }
})->name('retell.test.function');

// Test monitoring dashboard data endpoint
Route::get('/retell/test-status', function () {
    // Get recent test webhooks
    $recentWebhooks = collect(Storage::files('retell-test-webhooks'))
        ->map(function ($file) {
            $content = Storage::get($file);
            $data = json_decode($content, true);
            return [
                'file' => basename($file),
                'timestamp' => $data['timestamp'] ?? null,
                'event' => $data['body']['event'] ?? null,
                'call_id' => $data['body']['call']['call_id'] ?? null,
            ];
        })
        ->sortByDesc('timestamp')
        ->take(10)
        ->values();
    
    // Get recent logs
    $logFile = storage_path('logs/retell.log');
    $recentLogs = [];
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recentLogs = array_slice($lines, -20);
    }
    
    return response()->json([
        'status' => 'active',
        'recent_webhooks' => $recentWebhooks,
        'recent_log_entries' => count($recentLogs),
        'storage_path' => storage_path('app/retell-test-webhooks'),
        'test_endpoints' => [
            'webhook' => route('retell.test.webhook'),
            'signature' => route('retell.test.signature'),
            'function' => route('retell.test.function'),
        ]
    ]);
})->name('retell.test.status');