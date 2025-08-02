<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

// Login as admin
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
if (!$user) {
    die("Admin user not found\n");
}
Auth::login($user);

// Set company context
app()->instance('current_company_id', $user->company_id);

echo "=== Testing Direct Access to Calls Page ===\n\n";
echo "Logged in as: " . $user->email . "\n";
echo "Company ID: " . $user->company_id . "\n\n";

// Create request to calls page
$callsRequest = \Illuminate\Http\Request::create('/admin/calls', 'GET');
$callsRequest->setUserResolver(function() use ($user) { return $user; });
$callsRequest->setLaravelSession($request->session());

try {
    $callsResponse = app()->handle($callsRequest);
    $content = $callsResponse->getContent();
    $status = $callsResponse->getStatusCode();
    
    echo "Response Status: $status\n";
    echo "Content Length: " . strlen($content) . " bytes\n";
    
    // Check for specific errors
    if (strpos($content, '500') !== false && strpos($content, 'Server Error') !== false) {
        echo "\n❌ 500 ERROR FOUND!\n";
        
        // Extract error message
        if (preg_match('/<div[^>]*class="[^"]*message[^"]*"[^>]*>(.*?)<\/div>/si', $content, $matches)) {
            echo "Error Message: " . strip_tags($matches[1]) . "\n";
        }
        
        // Check if it's a Livewire error
        if (strpos($content, 'Livewire') !== false) {
            echo "This appears to be a Livewire-related error.\n";
        }
        
        // Save error page for analysis
        file_put_contents('/tmp/calls-error-page.html', $content);
        echo "\nError page saved to: /tmp/calls-error-page.html\n";
    } else {
        echo "\n✅ Page loaded successfully!\n";
        
        // Check for key elements
        if (strpos($content, 'wire:') !== false) {
            echo "✅ Livewire components found\n";
        }
        if (strpos($content, 'fi-ta-table') !== false) {
            echo "✅ Filament table found\n";
        }
    }
    
} catch (\Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

// Check recent log entries
echo "\n=== Recent Laravel Log Entries ===\n";
$logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
if (file_exists($logFile)) {
    $lines = explode("\n", shell_exec("tail -n 20 $logFile | grep -A 5 -B 5 'ERROR\\|Exception'"));
    foreach ($lines as $line) {
        if (trim($line)) {
            echo $line . "\n";
        }
    }
} else {
    echo "Today's log file not found.\n";
}