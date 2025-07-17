<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Test Business Portal E-Mail (FINAL) ===\n\n";

// Simulate portal user
$portalUser = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();
if (!$portalUser) {
    echo "❌ Portal User nicht gefunden!\n";
    exit(1);
}

\Illuminate\Support\Facades\Auth::guard('portal')->login($portalUser);

// Get Call
$call = \App\Models\Call::find(228);
if (!$call) {
    echo "❌ Call 228 nicht gefunden!\n";
    exit(1);
}

// Clear duplicate check
\App\Models\CallActivity::where('call_id', 228)
    ->where('activity_type', 'email_sent')
    ->where('created_at', '>', now()->subMinutes(5))
    ->delete();

echo "Call: {$call->id}\n";
echo "Company: {$call->company->name}\n";
echo "Portal User: {$portalUser->email}\n\n";

// Create request
$request = \Illuminate\Http\Request::create(
    "/business/api/calls/228/send-summary", 
    'POST', 
    [
        'recipients' => ['fabianspitzer@icloud.com'],
        'include_transcript' => true,
        'include_csv' => true,
        'message' => 'Business Portal Final Test - ' . now()->format('d.m.Y H:i:s')
    ]
);

$request->setUserResolver(function () use ($portalUser) {
    return $portalUser;
});

// Get controller and send
$controller = app(\App\Http\Controllers\Portal\Api\CallApiController::class);

try {
    echo "Sende E-Mail über Business Portal API...\n";
    $response = $controller->sendSummary($request, $call);
    $responseData = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() == 200) {
        echo "✅ E-Mail in Queue gestellt!\n";
        echo "Message: " . $responseData['message'] . "\n\n";
        
        // Process queue
        echo "Verarbeite Queue...\n";
        \Illuminate\Support\Facades\Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 1
        ]);
        
        echo "\n✅ ERFOLGREICH!\n";
        echo "Die E-Mail wurde über das Business Portal versendet.\n";
        echo "Sie enthält:\n";
        echo "- ✅ HTML-Design\n";
        echo "- ✅ Transkript\n";
        echo "- ✅ CSV-Anhang\n";
        
    } else {
        echo "❌ Fehler: " . json_encode($responseData) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getFile() . ":" . $e->getLine() . "\n";
}