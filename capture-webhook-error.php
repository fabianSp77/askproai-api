<?php

// Comprehensive error catching
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set custom error handler
set_error_handler(function($severity, $message, $file, $line) {
    echo "PHP ERROR: $message in $file:$line\n";
});

// Set exception handler
set_exception_handler(function($exception) {
    echo "UNCAUGHT EXCEPTION: " . $exception->getMessage() . "\n";
    echo "File: " . $exception->getFile() . ":" . $exception->getLine() . "\n";
    echo "Trace:\n" . $exception->getTraceAsString() . "\n";
});

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CAPTURING WEBHOOK 500 ERROR ===\n";
echo "Zeit: " . date('Y-m-d H:i:s') . "\n\n";

// Test data similar to what Retell would send
$testData = [
    'event_type' => 'call_started',
    'call_id' => 'error_capture_' . time(),
    'from_number' => '+491604366218',
    'to_number' => '+4930123456',
    'agent_id' => 'agent_9a8202a740cd3120d96fcfda1e',
    'start_timestamp' => date('Y-m-d\TH:i:s.v\Z')
];

echo "📡 Testing with Retell-like data:\n";
print_r($testData);

try {
    echo "\n🔍 Creating HTTP request...\n";
    
    // Create exact request that would come from Retell
    $request = \Illuminate\Http\Request::create(
        '/api/retell/webhook-simple',
        'POST',
        $testData,
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_USER_AGENT' => 'axios/1.7.7',
            'REMOTE_ADDR' => '100.20.5.228'
        ],
        json_encode($testData)
    );
    
    echo "✅ Request created\n";
    echo "🎯 Calling controller...\n";
    
    // Test the controller directly
    $controller = new \App\Http\Controllers\Api\RetellWebhookWorkingController();
    $response = $controller->handle($request);
    
    echo "✅ Controller Response:\n";
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
    
} catch (\App\Exceptions\MissingTenantException $e) {
    echo "❌ TENANT SCOPE EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (\Exception $e) {
    echo "❌ GENERAL EXCEPTION: " . $e->getMessage() . "\n";
    echo "Type: " . get_class($e) . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nFull Trace:\n" . $e->getTraceAsString() . "\n";
    
    // Also check if it's a specific Laravel error
    if (method_exists($e, 'getStatusCode')) {
        echo "HTTP Status: " . $e->getStatusCode() . "\n";
    }
} catch (\Error $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nFull Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== ERROR CAPTURE COMPLETED ===\n";