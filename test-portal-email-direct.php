<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Test Business Portal E-Mail API Direkt ===\n\n";

// Simulate portal user session
$portalUser = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();
if (!$portalUser) {
    echo "❌ Portal User nicht gefunden!\n";
    exit(1);
}

// Set session context
\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);
app()->instance('current_company_id', $portalUser->company_id);

echo "Portal User: {$portalUser->email}\n";
echo "Company: {$portalUser->company->name}\n\n";

// Test the API endpoint directly
$callId = 229;
$url = "/business/api/calls/{$callId}/send-summary";

echo "1. Test der API-Route direkt:\n";

$request = \Illuminate\Http\Request::create($url, 'POST', [
    'recipients' => ['fabianspitzer@icloud.com'],
    'include_transcript' => true,
    'include_csv' => true,
    'message' => 'Test vom Business Portal - Direkter API-Call'
]);

$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

// Get the controller
$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

try {
    $response = $controller->sendSummary($request, \App\Models\Call::find($callId));
    $responseData = json_decode($response->getContent(), true);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->getStatusCode() == 200) {
        echo "✅ E-Mail wurde erfolgreich in die Queue gestellt!\n\n";
        
        // Process queue
        echo "2. Verarbeite Queue:\n";
        \Illuminate\Support\Facades\Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 1
        ]);
        
        $output = \Illuminate\Support\Facades\Artisan::output();
        if ($output) {
            echo $output;
        }
        
        echo "\n3. Prüfe Queue-Status:\n";
        $redis = app('redis');
        $queues = ['default', 'high', 'emails'];
        
        foreach ($queues as $queue) {
            $length = $redis->llen("queues:{$queue}");
            if ($length > 0) {
                echo "   - {$queue}: {$length} Jobs\n";
            }
        }
        
        echo "\n4. Letzte Aktivitäten:\n";
        $activities = \App\Models\CallActivity::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->where('call_id', $callId)
            ->where('activity_type', 'email_sent')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
        
        foreach ($activities as $activity) {
            echo "   - {$activity->created_at->format('H:i:s')}: {$activity->description}\n";
        }
        
    } else {
        echo "❌ Fehler beim E-Mail-Versand!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n5. Test mit CURL (wie Frontend):\n";

// Get CSRF token
$csrfToken = csrf_token();

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost' . $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'recipients' => ['fabianspitzer@icloud.com'],
    'include_transcript' => true,
    'include_csv' => true
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-CSRF-TOKEN: ' . $csrfToken,
    'X-Requested-With: XMLHttpRequest'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIE, 'portal_session=' . session()->getId());

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";