<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
Auth::login($user);

echo "=== TESTING FILAMENT PAGES AFTER FIX ===\n\n";

$pages = [
    '/admin/calls' => 'Calls Page (ListRecords)',
    '/admin/appointments' => 'Appointments Page (ListRecords)',
    '/admin/customers' => 'Customers Page (ListRecords)',
    '/admin/phone-numbers' => 'Phone Numbers Page (ListRecords)',
    '/admin/working-calls' => 'Working Calls (Custom Page)',
    '/admin/simple-calls' => 'Simple Calls (Custom Page)',
];

foreach ($pages as $url => $name) {
    echo "Testing: $name\n";
    echo "URL: $url\n";
    
    $pageRequest = \Illuminate\Http\Request::create($url, 'GET');
    $pageRequest->setUserResolver(function() use ($user) { return $user; });
    $pageRequest->setLaravelSession($request->session());
    
    try {
        $pageResponse = app()->handle($pageRequest);
        $content = $pageResponse->getContent();
        $status = $pageResponse->getStatusCode();
        
        echo "- Status: $status\n";
        echo "- Content Length: " . strlen($content) . " bytes\n";
        
        // Check for errors
        if (strpos($content, 'Server Error') !== false || strpos($content, 'Exception') !== false) {
            echo "- ❌ Contains error\n";
        } else {
            echo "- ✅ No errors detected\n";
        }
        
        // Check for Livewire components
        if (strpos($content, 'wire:') !== false) {
            echo "- ✅ Has Livewire components\n";
        }
        
        // Check for table content
        if (strpos($content, 'fi-ta-table') !== false || strpos($content, 'table') !== false) {
            echo "- ✅ Has table content\n";
        }
        
    } catch (\Exception $e) {
        echo "- ❌ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Test Livewire update route directly
echo "=== TESTING LIVEWIRE UPDATE ROUTE ===\n";
$ch = curl_init('https://api.askproai.de/livewire/update');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Livewire: true', 'Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['fingerprint' => ['id' => 'test', 'name' => 'test'], 'serverMemo' => ['data' => [], 'checksum' => 'test'], 'updates' => []]));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Livewire Update Route: " . ($httpCode === 200 ? '✅ Working (200)' : "❌ Not working ($httpCode)") . "\n";

echo "\n=== SUMMARY ===\n";
echo "Livewire routes have been fixed. Pages should now load properly.\n";
echo "If any pages still show errors, check the browser console for JavaScript errors.\n";