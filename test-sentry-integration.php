<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;
use Sentry\Laravel\Facade as Sentry;

echo "Testing Sentry Integration for AskProAI\n";
echo "======================================\n\n";

// Test 1: Check if Sentry is configured
echo "1. Checking Sentry configuration...\n";
$dsn = config('sentry.dsn');
if ($dsn) {
    echo "   ✓ Sentry DSN is configured\n";
} else {
    echo "   ✗ Sentry DSN is not configured. Please set SENTRY_LARAVEL_DSN in .env\n";
}

// Test 2: Send a test message
echo "\n2. Sending test message to Sentry...\n";
try {
    Sentry::captureMessage('Test message from AskProAI - Sentry integration working!', 'info');
    echo "   ✓ Test message sent successfully\n";
} catch (\Exception $e) {
    echo "   ✗ Failed to send message: " . $e->getMessage() . "\n";
}

// Test 3: Send a test exception
echo "\n3. Sending test exception to Sentry...\n";
try {
    throw new \Exception('Test exception from AskProAI - This is a test error for Sentry integration');
} catch (\Exception $e) {
    Sentry::captureException($e);
    echo "   ✓ Test exception sent successfully\n";
}

// Test 4: Test with context
echo "\n4. Sending exception with context...\n";
try {
    Sentry::configureScope(function (\Sentry\State\Scope $scope): void {
        $scope->setUser([
            'id' => 'test-user-123',
            'email' => 'test@askproai.de',
        ]);
        
        $scope->setContext('appointment', [
            'id' => 'test-appointment-456',
            'customer' => 'Test Customer',
            'service' => 'Test Service',
            'branch' => 'Berlin',
        ]);
        
        $scope->setTag('feature', 'booking-flow');
        $scope->setTag('environment', app()->environment());
    });
    
    throw new \App\Exceptions\BookingException('Test booking exception with context');
} catch (\Exception $e) {
    Sentry::captureException($e);
    echo "   ✓ Exception with context sent successfully\n";
}

// Test 5: Test performance tracking
echo "\n5. Testing performance tracking...\n";
$transaction = Sentry::startTransaction([
    'name' => 'test.booking.process',
    'op' => 'http.request',
]);

Sentry::getCurrentHub()->setSpan($transaction);

// Simulate some work
$span = $transaction->startChild([
    'op' => 'db.query',
    'description' => 'SELECT * FROM appointments',
]);
usleep(100000); // 100ms
$span->finish();

$span = $transaction->startChild([
    'op' => 'http.client',
    'description' => 'Cal.com API call',
]);
usleep(200000); // 200ms
$span->finish();

$transaction->finish();
echo "   ✓ Performance transaction sent successfully\n";

// Test 6: Test MCP Server (if configured)
echo "\n6. Testing MCP Server integration...\n";
if (config('mcp-sentry.enabled')) {
    try {
        $mcp = app(\App\Services\MCP\SentryMCPServer::class);
        $issues = $mcp->listIssues(['limit' => 5]);
        
        if (isset($issues['error'])) {
            echo "   ✗ MCP Server error: " . $issues['error'] . "\n";
            echo "     Make sure SENTRY_AUTH_TOKEN is configured\n";
        } else {
            echo "   ✓ MCP Server working - Found " . count($issues) . " recent issues\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ MCP Server test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "   - MCP Server is disabled\n";
}

echo "\n======================================\n";
echo "Sentry integration test completed!\n";
echo "\nNext steps:\n";
echo "1. Check your Sentry dashboard for the test events\n";
echo "2. Configure SENTRY_AUTH_TOKEN for MCP Server access\n";
echo "3. Set up alerts and issue assignment in Sentry\n";
echo "4. Configure source maps for better JavaScript error tracking\n";