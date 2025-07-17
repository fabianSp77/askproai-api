<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TEST WITH DIFFERENT EMAIL ===\n\n";

// Clear log
shell_exec("echo '' > /var/www/api-gateway/storage/logs/laravel.log");

$callId = 258;
$recipient = 'test-' . time() . '@askproai.de'; // Unique email

echo "Testing with: $recipient\n\n";

// Create request
$request = new \Illuminate\Http\Request();
$request->setMethod('POST');
$request->merge([
    'recipients' => [$recipient],
    'include_transcript' => true,
    'include_csv' => true,
    'message' => 'Neuer Test - ' . now()->format('H:i:s')
]);

// Auth
$portalUser = \App\Models\PortalUser::first();
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);

// Get call
$call = \App\Models\Call::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($callId);
app()->instance('current_company_id', $call->company_id);

// Call controller
$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

try {
    $response = $controller->sendSummary($request, $call);
    echo "Response: " . $response->getContent() . "\n\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Wait
sleep(5);

// Check all logs
echo "SendCallSummaryEmailJob logs:\n";
$jobLogs = shell_exec("grep 'SendCallSummaryEmailJob' /var/www/api-gateway/storage/logs/laravel.log");
echo $jobLogs ?: "No job logs found\n";

echo "\nResendTransport logs:\n";
$resendLogs = shell_exec("grep 'ResendTransport' /var/www/api-gateway/storage/logs/laravel.log");
echo $resendLogs ?: "No ResendTransport logs found\n";

echo "\nAny errors:\n";
$errors = shell_exec("grep -i 'error\\|exception' /var/www/api-gateway/storage/logs/laravel.log | grep -v 'AUTH EVENT'");
echo $errors ?: "No errors found\n";